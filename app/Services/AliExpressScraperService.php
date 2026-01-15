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
                ])
                ->post(config('services.firecrawl.base_url') . '/scrape', [
                    'url' => $this->normalizeUrl($url),
                    'formats' => ['markdown', 'extract'],
                    'waitFor' => 5000, // Wait 5 seconds for JS to render
                    'extract' => [
                        'prompt' => 'Extract the product title and price from this AliExpress product page. The title is usually in the h1 tag. The price is the current/discounted price shown on the page.',
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'title' => [
                                    'type' => 'string',
                                    'description' => 'The full product title/name from the page',
                                ],
                                'price' => [
                                    'type' => 'number',
                                    'description' => 'Current sale price as a number (without currency symbol)',
                                ],
                                'currency' => [
                                    'type' => 'string',
                                    'description' => 'Currency code (EUR, USD, GBP, etc)',
                                ],
                                'images' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                    'description' => 'Product image URLs',
                                ],
                                'description' => [
                                    'type' => 'string',
                                    'description' => 'Product description if available',
                                ],
                            ],
                            'required' => ['title', 'price'],
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

            // Handle different response structures
            $extract = null;

            if (isset($data['data']['extract'])) {
                $extract = $data['data']['extract'];
            } elseif (isset($data['extract'])) {
                $extract = $data['extract'];
            } elseif (isset($data['data']['llm_extraction'])) {
                $extract = $data['data']['llm_extraction'];
            } elseif (isset($data['data'])) {
                // Maybe the data is directly in data
                $extract = $data['data'];
            }

            // Get markdown content for fallback extraction
            $markdown = $data['data']['markdown'] ?? '';

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
        return preg_match('/aliexpress\.(com|us|ru)\/item\//i', $url) === 1;
    }

    /**
     * Normalize URL to use www subdomain for consistency.
     */
    private function normalizeUrl(string $url): string
    {
        return preg_replace('/\/\/(fr|de|es|it|ru|nl|pl)\./i', '//www.', $url);
    }
}
