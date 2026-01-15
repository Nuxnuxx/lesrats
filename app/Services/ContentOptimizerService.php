<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContentOptimizerService
{
    protected ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    /**
     * Optimize product title for Etsy.
     */
    public function optimizeTitle(string $originalTitle): string
    {
        if (!$this->apiKey) {
            return $this->fallbackOptimizeTitle($originalTitle);
        }

        try {
            $prompt = "Transform this AliExpress product title into an attractive, SEO-optimized Etsy listing title. "
                . "Keep it under 140 characters. Remove any mentions of 'wholesale', 'dropshipping', 'China', or similar terms. "
                . "Make it sound handmade, unique, and appealing to Etsy buyers. "
                . "Original title: {$originalTitle}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert Etsy SEO specialist who creates compelling product titles.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 100,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return trim($result['choices'][0]['message']['content'] ?? $originalTitle);
            }

            return $this->fallbackOptimizeTitle($originalTitle);

        } catch (\Exception $e) {
            Log::error('OpenAI title optimization failed', ['error' => $e->getMessage()]);
            return $this->fallbackOptimizeTitle($originalTitle);
        }
    }

    /**
     * Optimize product description for Etsy.
     */
    public function optimizeDescription(string $originalTitle, ?string $originalDescription = null, array $specs = []): string
    {
        if (!$this->apiKey) {
            return $this->fallbackOptimizeDescription($originalTitle, $originalDescription, $specs);
        }

        try {
            $specsText = '';
            if (!empty($specs)) {
                $specsText = "\n\nProduct specifications:\n";
                foreach ($specs as $key => $value) {
                    $specsText .= "- {$key}: {$value}\n";
                }
            }

            $prompt = "Write an engaging Etsy product description based on this information:\n\n"
                . "Title: {$originalTitle}\n"
                . ($originalDescription ? "Original description: {$originalDescription}\n" : '')
                . $specsText . "\n\n"
                . "Requirements:\n"
                . "- Write in a warm, artisanal style that appeals to Etsy buyers\n"
                . "- Remove any mentions of 'wholesale', 'dropshipping', 'China', 'AliExpress', or bulk ordering\n"
                . "- Highlight quality, uniqueness, and craftsmanship\n"
                . "- Include care instructions if relevant\n"
                . "- Use bullet points for key features\n"
                . "- Keep it between 200-400 words\n"
                . "- Make it SEO-friendly with natural keyword placement";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert Etsy copywriter who creates compelling product descriptions.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 600,
                'temperature' => 0.8,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return trim($result['choices'][0]['message']['content'] ?? $this->fallbackOptimizeDescription($originalTitle, $originalDescription, $specs));
            }

            return $this->fallbackOptimizeDescription($originalTitle, $originalDescription, $specs);

        } catch (\Exception $e) {
            Log::error('OpenAI description optimization failed', ['error' => $e->getMessage()]);
            return $this->fallbackOptimizeDescription($originalTitle, $originalDescription, $specs);
        }
    }

    /**
     * Calculate suggested price with markup.
     */
    public function calculatePrice(float $aliexpressPrice, float $markupPercentage = 150): float
    {
        // Apply markup percentage (default 150% = 2.5x)
        $suggestedPrice = $aliexpressPrice * ($markupPercentage / 100);

        // Round to nearest .99 or .95 for psychological pricing
        $rounded = floor($suggestedPrice);

        if ($rounded < 10) {
            return $rounded + 0.99;
        } elseif ($rounded < 50) {
            return $rounded + 0.95;
        } else {
            return round($rounded / 5) * 5 - 0.01; // Round to nearest 5, then subtract 0.01
        }
    }

    /**
     * Fallback title optimization (rule-based).
     */
    protected function fallbackOptimizeTitle(string $title): string
    {
        // Remove unwanted terms
        $unwanted = ['wholesale', 'dropshipping', 'dropship', 'china', 'chinese', 'bulk', 'lot of', 'pieces'];
        foreach ($unwanted as $term) {
            $title = preg_replace('/\b' . preg_quote($term, '/') . '\b/i', '', $title);
        }

        // Clean up multiple spaces
        $title = preg_replace('/\s+/', ' ', $title);

        // Capitalize first letter of each word
        $title = ucwords(strtolower(trim($title)));

        // Limit to 140 characters
        if (strlen($title) > 140) {
            $title = substr($title, 0, 137) . '...';
        }

        return $title;
    }

    /**
     * Fallback description optimization (rule-based).
     */
    protected function fallbackOptimizeDescription(string $title, ?string $description, array $specs): string
    {
        $optimized = "✨ {$title}\n\n";
        $optimized .= "Discover this unique and beautiful item, carefully selected for its quality and style.\n\n";

        if (!empty($specs)) {
            $optimized .= "📋 Features:\n";
            foreach (array_slice($specs, 0, 5) as $key => $value) {
                $optimized .= "• {$key}: {$value}\n";
            }
            $optimized .= "\n";
        }

        if ($description && strlen($description) > 50) {
            // Clean description
            $cleaned = strip_tags($description);
            $cleaned = preg_replace('/\b(wholesale|dropshipping|china|bulk)\b/i', '', $cleaned);
            $cleaned = preg_replace('/\s+/', ' ', trim($cleaned));

            $optimized .= "📝 Description:\n";
            $optimized .= substr($cleaned, 0, 300) . (strlen($cleaned) > 300 ? '...' : '') . "\n\n";
        }

        $optimized .= "💝 Perfect for gifts or personal use!\n\n";
        $optimized .= "📦 Carefully packaged and shipped with care.";

        return $optimized;
    }
}
