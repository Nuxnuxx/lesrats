<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AliExpressScraperService
{
    /**
     * Extract product data from AliExpress URL.
     */
    public function scrapeProduct(string $url): array
    {
        try {
            // Fetch the page
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->get($url);

            if ($response->failed()) {
                throw new \Exception('Failed to fetch AliExpress page');
            }

            $html = $response->body();

            // Extract product data using regex patterns
            $data = [
                'title' => $this->extractTitle($html),
                'price' => $this->extractPrice($html),
                'description' => $this->extractDescription($html),
                'images' => $this->extractImages($html),
                'specs' => $this->extractSpecs($html),
            ];

            return $data;

        } catch (\Exception $e) {
            Log::error('AliExpress scraping failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Unable to extract product data from AliExpress. Please enter the details manually.');
        }
    }

    /**
     * Extract product title.
     */
    protected function extractTitle(string $html): ?string
    {
        // Try multiple patterns for title extraction
        $patterns = [
            '/<h1[^>]*class="[^"]*product-title[^"]*"[^>]*>(.*?)<\/h1>/is',
            '/<h1[^>]*>(.*?)<\/h1>/is',
            '/"subject":"([^"]+)"/i',
            '/"title":"([^"]+)"/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $title = strip_tags($matches[1]);
                $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
                $title = trim($title);

                if (!empty($title) && strlen($title) > 5) {
                    return $title;
                }
            }
        }

        return null;
    }

    /**
     * Extract product price.
     */
    protected function extractPrice(string $html): ?float
    {
        $patterns = [
            '/"price":"([0-9.]+)"/i',
            '/"minPrice":"([0-9.]+)"/i',
            '/data-spm-anchor-id="[^"]*"[^>]*>[$€£¥]?([0-9.]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $price = floatval($matches[1]);
                if ($price > 0) {
                    return $price;
                }
            }
        }

        return null;
    }

    /**
     * Extract product description.
     */
    protected function extractDescription(string $html): ?string
    {
        $patterns = [
            '/<div[^>]*class="[^"]*product-description[^"]*"[^>]*>(.*?)<\/div>/is',
            '/"description":"([^"]+)"/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $description = strip_tags($matches[1]);
                $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
                $description = trim($description);

                if (!empty($description) && strlen($description) > 20) {
                    return $description;
                }
            }
        }

        return null;
    }

    /**
     * Extract product images.
     */
    protected function extractImages(string $html): array
    {
        $images = [];

        // Try to extract images from JSON data
        if (preg_match('/"imageModule":\{[^}]*"imagePathList":\[(.*?)\]/is', $html, $matches)) {
            preg_match_all('/"([^"]+\.jpg[^"]*)"/i', $matches[1], $imageMatches);
            foreach ($imageMatches[1] as $image) {
                // Make sure URL is complete
                if (!str_starts_with($image, 'http')) {
                    $image = 'https:' . $image;
                }
                $images[] = $image;
            }
        }

        // Fallback: extract from img tags
        if (empty($images)) {
            preg_match_all('/<img[^>]+src="([^"]+ae01\.alicdn\.com[^"]+)"/i', $html, $imgMatches);
            foreach ($imgMatches[1] as $image) {
                if (!str_starts_with($image, 'http')) {
                    $image = 'https:' . $image;
                }
                $images[] = $image;
            }
        }

        // Remove duplicates and limit to 10 images
        $images = array_unique($images);
        return array_slice($images, 0, 10);
    }

    /**
     * Extract product specifications.
     */
    protected function extractSpecs(string $html): array
    {
        $specs = [];

        // Try to extract specs from JSON data
        if (preg_match('/"productProp":\[(.*?)\]/is', $html, $matches)) {
            preg_match_all('/\{"attrName":"([^"]+)","attrValue":"([^"]+)"/i', $matches[1], $specMatches, PREG_SET_ORDER);

            foreach ($specMatches as $match) {
                $specs[$match[1]] = $match[2];
            }
        }

        return $specs;
    }

    /**
     * Validate if URL is a valid AliExpress product URL.
     */
    public function isValidAliExpressUrl(string $url): bool
    {
        return preg_match('/aliexpress\.(com|us|ru)\/item\//i', $url) === 1;
    }
}
