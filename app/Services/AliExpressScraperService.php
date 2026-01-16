<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AliExpressScraperService
{
    /**
     * Extract product data from AliExpress URL using Firecrawl.
     */
    public function scrapeProduct(string $url): array
    {
        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.firecrawl.api_key'),
                    'Content-Type' => 'application/json',
                ])
                ->post(config('services.firecrawl.base_url') . '/scrape', [
                    'url' => $this->normalizeUrl($url),
                    'onlyMainContent' => false,
                    'maxAge' => 172800000, // Cache for 48 hours
                    'parsers' => ['pdf'],
                    'formats' => [
                        [
                            'type' => 'json',
                            'schema' => [
                                'type' => 'object',
                                'required' => [],
                                'properties' => [
                                    'title' => [
                                        'type' => 'string',
                                    ],
                                    'price' => [
                                        'type' => 'number',
                                    ],
                                    'currency' => [
                                        'type' => 'string',
                                    ],
                                    'images' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                    'description' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Firecrawl scraping failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception('Failed to scrape AliExpress page. Please try again or enter details manually.');
            }

            $data = $response->json();

            Log::info('Firecrawl raw response', [
                'url' => $url,
                'response' => $data,
            ]);

            // Handle v2 API response structure
            $extract = null;

            // V2 format: data.json contains the extracted data
            if (isset($data['data']['json'])) {
                $extract = $data['data']['json'];
            } elseif (isset($data['data']['extract'])) {
                $extract = $data['data']['extract'];
            } elseif (isset($data['extract'])) {
                $extract = $data['extract'];
            } elseif (isset($data['data']['llm_extraction'])) {
                $extract = $data['data']['llm_extraction'];
            } elseif (isset($data['data']) && is_array($data['data'])) {
                // Maybe the data is directly in data
                $extract = $data['data'];
            }

            // Get markdown content for fallback extraction
            $markdown = $data['data']['markdown'] ?? $data['data']['content'] ?? '';

            // Check if title looks like a placeholder
            $title = $extract['title'] ?? null;
            $isPlaceholder = !$title ||
                stripos($title, 'placeholder') !== false ||
                stripos($title, 'loading') !== false ||
                strlen($title) < 10;

            // Fallback: extract title from markdown
            if ($isPlaceholder && $markdown) {
                Log::info('Attempting markdown fallback extraction');

                // Try to find first h1 heading
                if (preg_match('/^#\s+(.+)$/m', $markdown, $matches)) {
                    $title = trim($matches[1]);
                }

                // Or find a line that looks like a product title (long text early in document)
                if (!$title || strlen($title) < 10) {
                    $lines = explode("\n", $markdown);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        // Skip short lines, links, prices, navigation
                        if (strlen($line) > 30 && strlen($line) < 300 &&
                            !preg_match('/^[\[\(#*€$\d]/', $line) &&
                            !preg_match('/aliexpress|sign in|cart|ship to/i', $line)) {
                            $title = $line;
                            break;
                        }
                    }
                }
            }

            // Fallback: extract price from markdown
            $price = $extract['price'] ?? null;
            if (!$price && $markdown) {
                // Look for price patterns: €12.99, $12.99, 12,99€
                if (preg_match('/[€$]\s*(\d+[.,]\d{2})/', $markdown, $matches)) {
                    $price = (float) str_replace(',', '.', $matches[1]);
                } elseif (preg_match('/(\d+[.,]\d{2})\s*[€$]/', $markdown, $matches)) {
                    $price = (float) str_replace(',', '.', $matches[1]);
                }
            }

            if (!$title || strlen($title) < 10) {
                Log::error('Could not extract title from page', [
                    'url' => $url,
                    'extract' => $extract,
                    'markdown_preview' => substr($markdown, 0, 500),
                ]);

                throw new \Exception('No data extracted from page');
            }

            return [
                'title' => $title,
                'price' => $price,
                'description' => $extract['description'] ?? null,
                'images' => $extract['images'] ?? [],
                'specs' => [],
            ];

        } catch (\Exception $e) {
            Log::error('AliExpress scraping failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Unable to extract product data from AliExpress: ' . $e->getMessage());
        }
    }

    /**
     * Validate if URL is a valid AliExpress product URL.
     */
    public function isValidAliExpressUrl(string $url): bool
    {
        return preg_match('/(fr\.|de\.|es\.|it\.|nl\.|pl\.|www\.)?aliexpress\.(com|us|ru)\/item\//i', $url) === 1;
    }

    /**
     * Normalize URL to use www subdomain for consistency.
     */
    private function normalizeUrl(string $url): string
    {
        return preg_replace('/\/\/(fr|de|es|it|ru|nl|pl)\./i', '//www.', $url);
    }
}
