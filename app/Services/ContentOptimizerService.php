<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContentOptimizerService
{
    protected ?string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key');
        $this->model = config('services.groq.model', 'llama-3.3-70b-versatile');
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
            $prompt = "Transform this AliExpress product title into an SEO-optimized Etsy listing title.\n\n"
                . "Original title: {$originalTitle}\n\n"
                . "RULES:\n"
                . "1. KEEP the actual product keywords (e.g., kimono, cardigan, Mount Fuji, haori, necklace, ring, etc.)\n"
                . "2. Translate to English if the title is in French or another language\n"
                . "3. Remove ONLY these terms: wholesale, dropshipping, China, AliExpress, bulk, lot, pieces\n"
                . "4. Maximum 140 characters\n"
                . "5. Make it readable, natural, and SEO-friendly\n"
                . "6. NEVER use generic terms like 'Handmade Gift', 'Unique Item' - use the REAL product words\n\n"
                . "Output ONLY the optimized title, nothing else.";

            $systemPrompt = "You are an Etsy SEO expert specializing in dropshipping product optimization. "
                . "Your job is to transform AliExpress titles into compelling Etsy titles. "
                . "CRITICAL: You must preserve the actual product keywords - never replace them with generic terms like 'Handmade Gift' or 'Unique Item'. "
                . "Always output in English. Translate if the input is in another language. "
                . "Output only the title, no explanations or quotes.";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 100,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $title = trim($result['choices'][0]['message']['content'] ?? $originalTitle);
                // Remove quotes if present
                $title = trim($title, '"\'');
                return $title;
            }

            return $this->fallbackOptimizeTitle($originalTitle);

        } catch (\Exception $e) {
            Log::error('Groq title optimization failed', ['error' => $e->getMessage()]);
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
                $specsText = "\nProduct specifications:\n";
                foreach ($specs as $key => $value) {
                    $specsText .= "- {$key}: {$value}\n";
                }
            }

            $prompt = "Write an SEO-optimized Etsy product description.\n\n"
                . "Product: {$originalTitle}\n"
                . ($originalDescription ? "Original description: {$originalDescription}\n" : '')
                . $specsText . "\n"
                . "RULES:\n"
                . "1. Write in English (translate if the product info is in French or another language)\n"
                . "2. Warm, friendly, sales-driven tone with a few emojis (not too many)\n"
                . "3. Remove mentions of: wholesale, dropshipping, China, AliExpress\n"
                . "4. Highlight the ACTUAL product features (e.g., for a kimono: Japanese style, Mount Fuji print, beach cardigan, etc.)\n"
                . "5. 200-400 words with bullet points for key features\n"
                . "6. SEO-friendly with natural keyword placement\n"
                . "7. NEVER write generic descriptions - focus on THIS specific product\n"
                . "8. Include care instructions if relevant\n\n"
                . "Output ONLY the description, nothing else.";

            $systemPrompt = "You are an Etsy SEO expert and copywriter. Write product descriptions that are warm, friendly, and convert well. "
                . "Use a few emojis to make it attractive. Always write in English. "
                . "CRITICAL: Base the description on the ACTUAL product - never write generic content. "
                . "Focus on the real product keywords and features.";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 800,
                'temperature' => 0.8,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return trim($result['choices'][0]['message']['content'] ?? $this->fallbackOptimizeDescription($originalTitle, $originalDescription, $specs));
            }

            return $this->fallbackOptimizeDescription($originalTitle, $originalDescription, $specs);

        } catch (\Exception $e) {
            Log::error('Groq description optimization failed', ['error' => $e->getMessage()]);
            return $this->fallbackOptimizeDescription($originalTitle, $originalDescription, $specs);
        }
    }

    /**
     * Calculate suggested price with markup.
     */
    public function calculatePrice(float $aliexpressPrice, float $markupMultiplier = 3): float
    {
        // Apply markup multiplier (default 3x the AliExpress price)
        $suggestedPrice = $aliexpressPrice * $markupMultiplier;

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
     * Generate 13 SEO-optimized Etsy tags.
     */
    public function generateTags(string $title, ?string $description = null): array
    {
        if (!$this->apiKey) {
            return $this->fallbackGenerateTags($title);
        }

        try {
            $prompt = "Generate exactly 13 Etsy SEO tags for this product:\n\n"
                . "Product: {$title}\n"
                . ($description ? "Description: " . substr($description, 0, 500) . "\n" : '')
                . "\n"
                . "RULES:\n"
                . "1. Each tag maximum 20 characters\n"
                . "2. Tags must relate to the ACTUAL product (e.g., 'japanese kimono', 'mount fuji', 'haori jacket', 'beach cardigan')\n"
                . "3. Mix of specific keywords and broader terms\n"
                . "4. All tags in English\n"
                . "5. No duplicates\n"
                . "6. NEVER use generic tags like 'handmade gift' unless truly relevant to the product\n\n"
                . "Output ONLY 13 tags separated by commas on a single line, nothing else.";

            $systemPrompt = "You are an Etsy SEO expert. Generate exactly 13 tags based on the ACTUAL product. "
                . "Each tag max 20 characters. Focus on real product keywords, not generic terms. "
                . "Output only the tags separated by commas.";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 200,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $tagsString = trim($result['choices'][0]['message']['content'] ?? '');

                // Parse tags from comma-separated string
                $tags = array_map('trim', explode(',', $tagsString));

                // Ensure each tag is max 20 characters
                $tags = array_map(function ($tag) {
                    return substr(trim($tag), 0, 20);
                }, $tags);

                // Filter empty tags and limit to 13
                $tags = array_filter($tags, fn($tag) => !empty($tag));
                $tags = array_slice(array_values($tags), 0, 13);

                if (count($tags) >= 10) {
                    return $tags;
                }
            }

            return $this->fallbackGenerateTags($title);

        } catch (\Exception $e) {
            Log::error('Groq tags generation failed', ['error' => $e->getMessage()]);
            return $this->fallbackGenerateTags($title);
        }
    }

    /**
     * Fallback tag generation (rule-based).
     */
    protected function fallbackGenerateTags(string $title): array
    {
        // Extract words from title
        $words = preg_split('/\s+/', strtolower($title));
        $words = array_filter($words, fn($w) => strlen($w) > 2);

        $tags = [];

        // Add words as tags (max 20 chars each)
        foreach ($words as $word) {
            $word = preg_replace('/[^a-z0-9\s]/', '', $word);
            if (strlen($word) > 2 && strlen($word) <= 20) {
                $tags[] = $word;
            }
            if (count($tags) >= 13) break;
        }

        // Pad with generic tags if needed
        $genericTags = ['handmade', 'unique gift', 'gift for her', 'gift for him', 'home decor', 'vintage style', 'boho', 'minimalist', 'custom'];
        while (count($tags) < 13 && !empty($genericTags)) {
            $tags[] = array_shift($genericTags);
        }

        return array_slice($tags, 0, 13);
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
