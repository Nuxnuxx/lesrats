<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PrintablesScraperService
{
    /**
     * Extract product data from Printables URL using Firecrawl.
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
                    'url' => $url,
                    'onlyMainContent' => false,
                    'maxAge' => 172800000, // Cache for 48 hours
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
                                    'description' => [
                                        'type' => 'string',
                                    ],
                                    'author' => [
                                        'type' => 'string',
                                    ],
                                    'license' => [
                                        'type' => 'string',
                                    ],
                                    'images' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                    'tags' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                    'category' => [
                                        'type' => 'string',
                                    ],
                                    'downloads' => [
                                        'type' => 'number',
                                    ],
                                    'likes' => [
                                        'type' => 'number',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Firecrawl Printables scraping failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception('Failed to scrape Printables page. Please try again or enter details manually.');
            }

            $data = $response->json();

            Log::info('Firecrawl Printables raw response', [
                'url' => $url,
                'response' => $data,
            ]);

            // Handle v2 API response structure
            $extract = null;

            if (isset($data['data']['json'])) {
                $extract = $data['data']['json'];
            } elseif (isset($data['data']['extract'])) {
                $extract = $data['data']['extract'];
            } elseif (isset($data['extract'])) {
                $extract = $data['extract'];
            } elseif (isset($data['data']['llm_extraction'])) {
                $extract = $data['data']['llm_extraction'];
            } elseif (isset($data['data']) && is_array($data['data'])) {
                $extract = $data['data'];
            }

            // Get markdown content for fallback extraction
            $markdown = $data['data']['markdown'] ?? $data['data']['content'] ?? '';

            // Extract title
            $title = $extract['title'] ?? null;

            // Fallback: extract title from markdown
            if (!$title || strlen($title) < 5) {
                if (preg_match('/^#\s+(.+)$/m', $markdown, $matches)) {
                    $title = trim($matches[1]);
                }
            }

            // Extract description
            $description = $extract['description'] ?? null;

            // Fallback: extract description from markdown (first paragraph after title)
            if (!$description && $markdown) {
                $lines = explode("\n", $markdown);
                $foundTitle = false;
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($foundTitle && strlen($line) > 50 && !preg_match('/^[#\[\*]/', $line)) {
                        $description = $line;
                        break;
                    }
                    if (preg_match('/^#/', $line)) {
                        $foundTitle = true;
                    }
                }
            }

            // Extract author
            $author = $extract['author'] ?? null;
            if (!$author && $markdown) {
                if (preg_match('/by\s+([A-Za-z0-9_-]+)/i', $markdown, $matches)) {
                    $author = trim($matches[1]);
                }
            }

            // Extract license
            $license = $extract['license'] ?? 'Unknown';
            if ($license === 'Unknown' && $markdown) {
                if (preg_match('/(CC[- ]BY[- ]?(?:SA|NC|ND)?(?:[- ]\d\.\d)?|Creative Commons|Public Domain|GPL|MIT)/i', $markdown, $matches)) {
                    $license = trim($matches[1]);
                }
            }

            // Extract images
            $images = $extract['images'] ?? [];
            if (empty($images) && $markdown) {
                preg_match_all('/!\[.*?\]\((https?:\/\/[^\)]+)\)/', $markdown, $imageMatches);
                if (!empty($imageMatches[1])) {
                    $images = $imageMatches[1];
                }
            }

            // Deduplicate images based on base filename (ignore size parameters)
            $images = $this->deduplicateImages($images);

            // Extract tags
            $tags = $extract['tags'] ?? [];

            if (!$title || strlen($title) < 5) {
                Log::error('Could not extract title from Printables page', [
                    'url' => $url,
                    'extract' => $extract,
                    'markdown_preview' => substr($markdown, 0, 500),
                ]);

                throw new \Exception('No data extracted from Printables page');
            }

            return [
                'title' => $title,
                'description' => $description,
                'author' => $author,
                'license' => $license,
                'images' => $images,
                'tags' => $tags,
                'category' => $extract['category'] ?? null,
                'downloads' => $extract['downloads'] ?? null,
                'likes' => $extract['likes'] ?? null,
                'source_url' => $url,
            ];

        } catch (\Exception $e) {
            Log::error('Printables scraping failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Unable to extract product data from Printables: ' . $e->getMessage());
        }
    }

    /**
     * Validate if URL is a valid Printables product URL.
     */
    public function isValidPrintablesUrl(string $url): bool
    {
        return preg_match('/printables\.com\/model\/\d+/i', $url) === 1;
    }

    /**
     * Check if the license allows commercial use.
     */
    public function isCommercialLicenseAllowed(string $license): bool
    {
        $allowedLicenses = [
            'CC0',
            'CC BY',
            'CC BY 4.0',
            'CC BY-SA',
            'CC BY-SA 4.0',
            'Public Domain',
            'MIT',
            'GPL',
        ];

        $forbiddenPatterns = [
            '/NC/i', // Non-Commercial
            '/Non-?Commercial/i',
        ];

        // Check if license contains forbidden patterns
        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $license)) {
                return false;
            }
        }

        // Check if license is in allowed list
        foreach ($allowedLicenses as $allowed) {
            if (stripos($license, $allowed) !== false) {
                return true;
            }
        }

        // Unknown license - warn but allow (user responsibility)
        return true;
    }

    /**
     * Generate attribution text for the license.
     */
    public function generateAttribution(array $productData): string
    {
        $author = $productData['author'] ?? 'Unknown';
        $license = $productData['license'] ?? 'Unknown';
        $url = $productData['source_url'] ?? '';

        return "Design by {$author}. Licensed under {$license}. Original: {$url}";
    }

    /**
     * Deduplicate images based on base filename.
     * Printables often returns the same image in different sizes (thumbnails, etc.)
     */
    protected function deduplicateImages(array $images): array
    {
        $seen = [];
        $unique = [];

        foreach ($images as $imageUrl) {
            // Extract the base filename without size parameters
            // Printables URLs often look like: https://media.printables.com/media/prints/123456/images/1234567_12345678-xxxx-xxxx-xxxx-xxxxxxxxxxxx/thumbs/cover/320x240/png/image.png
            // We want to identify unique images by their core identifier

            // Get the path without query string
            $path = parse_url($imageUrl, PHP_URL_PATH);
            if (!$path) {
                continue;
            }

            // Try to extract a unique identifier from the path
            // Remove size indicators like /320x240/, /640x480/, /thumbs/, /cover/, etc.
            $normalized = preg_replace('/\/\d+x\d+\//', '/', $path);
            $normalized = preg_replace('/\/thumbs\//', '/', $normalized);
            $normalized = preg_replace('/\/cover\//', '/', $normalized);
            $normalized = preg_replace('/\/inside\//', '/', $normalized);

            // Extract the image identifier (usually a UUID or numeric ID)
            if (preg_match('/images\/(\d+_[a-f0-9-]+)/', $path, $matches)) {
                $identifier = $matches[1];
            } else {
                // Fallback: use the filename
                $identifier = basename($path);
            }

            if (!isset($seen[$identifier])) {
                $seen[$identifier] = true;
                // Prefer larger images - try to get the original/largest version
                $unique[] = $imageUrl;
            }
        }

        // Limit to 10 images
        return array_slice($unique, 0, 10);
    }
}
