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
     *
     * @param  string  $originalTitle  The original product title
     * @param  string|null  $context  Context like '3D Print' for STL files
     * @param  string|null  $customPrompt  Custom prompt from shop settings (appended to system prompt)
     */
    public function optimizeTitle(string $originalTitle, ?string $context = null, ?string $customPrompt = null): string
    {
        if (! $this->apiKey) {
            return $this->fallbackOptimizeTitle($originalTitle);
        }

        try {
            $is3DPrint = $context === '3D Print';

            if ($is3DPrint) {
                $prompt = "Transform this 3D model title into an SEO-optimized Etsy listing title for a DIGITAL STL FILE download.\n\n"
                    ."Original title: {$originalTitle}\n\n"
                    ."RULES:\n"
                    ."1. KEEP the actual product keywords (e.g., dragon, planter, figurine, organizer, etc.)\n"
                    ."2. Add 'STL File' or '3D Print File' or 'Digital Download' in the title\n"
                    ."3. Translate to English if the title is in another language\n"
                    ."4. Maximum 140 characters\n"
                    ."5. Make it readable, natural, and SEO-friendly\n"
                    ."6. Include keywords like: STL, 3D print file, digital download, printable\n"
                    ."7. NEVER use generic terms - use the REAL product words\n\n"
                    .'Output ONLY the optimized title, nothing else.';

                $systemPrompt = 'You are an Etsy SEO expert specializing in digital 3D model files (STL). '
                    .'Your job is to transform 3D model names into compelling Etsy titles for DIGITAL FILE downloads. '
                    ."CRITICAL: You must preserve the actual product keywords and emphasize it's a digital STL file. "
                    .'Always output in English. Translate if the input is in another language. '
                    .'Output only the title, no explanations or quotes.';
            } else {
                $prompt = "Transform this AliExpress product title into an SEO-optimized Etsy listing title.\n\n"
                    ."Original title: {$originalTitle}\n\n"
                    ."RULES:\n"
                    ."1. KEEP the actual product keywords (e.g., kimono, cardigan, Mount Fuji, haori, necklace, ring, etc.)\n"
                    ."2. Translate to English if the title is in French or another language\n"
                    ."3. Remove ONLY these terms: wholesale, dropshipping, China, AliExpress, bulk, lot, pieces\n"
                    ."4. Maximum 140 characters\n"
                    ."5. Make it readable, natural, and SEO-friendly\n"
                    ."6. NEVER use generic terms like 'Handmade Gift', 'Unique Item' - use the REAL product words\n\n"
                    .'Output ONLY the optimized title, nothing else.';

                $systemPrompt = 'You are an Etsy SEO expert specializing in dropshipping product optimization. '
                    .'Your job is to transform AliExpress titles into compelling Etsy titles. '
                    ."CRITICAL: You must preserve the actual product keywords - never replace them with generic terms like 'Handmade Gift' or 'Unique Item'. "
                    .'Always output in English. Translate if the input is in another language. '
                    .'Output only the title, no explanations or quotes.';
            }

            // Append custom prompt if provided
            if (! empty($customPrompt)) {
                $systemPrompt .= "\n\nADDITIONAL SHOP INSTRUCTIONS:\n".$customPrompt;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
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
     *
     * @param  string  $originalTitle  The original product title
     * @param  string|null  $originalDescription  The original description (if any)
     * @param  array  $specs  Product specifications
     * @param  bool  $is3DPrint  Whether this is a 3D print/STL file
     * @param  string|null  $customPrompt  Custom prompt from shop settings (appended to system prompt)
     */
    public function optimizeDescription(string $originalTitle, ?string $originalDescription = null, array $specs = [], bool $is3DPrint = false, ?string $customPrompt = null): string
    {
        if (! $this->apiKey) {
            return $this->fallbackOptimizeDescription($originalTitle, $originalDescription, $specs, $is3DPrint);
        }

        try {
            $specsText = '';
            if (! empty($specs)) {
                $specsText = "\nProduct specifications:\n";
                foreach ($specs as $key => $value) {
                    $specsText .= "- {$key}: {$value}\n";
                }
            }

            if ($is3DPrint) {
                $prompt = "Write an SEO-optimized Etsy product description for a DIGITAL STL FILE download.\n\n"
                    ."Product: {$originalTitle}\n"
                    .($originalDescription ? "Original description: {$originalDescription}\n" : '')
                    .$specsText."\n"
                    ."RULES:\n"
                    ."1. Write in English (translate if needed)\n"
                    ."2. Warm, friendly tone with a few emojis\n"
                    ."3. Emphasize this is a DIGITAL DOWNLOAD - customer receives STL files, NOT a physical product\n"
                    ."4. Include these sections:\n"
                    ."   - Introduction (what the 3D model is)\n"
                    ."   - What's included (STL file(s), instant download after purchase)\n"
                    ."   - Printing recommendations (suggested infill, supports, layer height)\n"
                    ."   - Compatible with most FDM/SLA printers\n"
                    ."   - No physical item shipped - digital product only\n"
                    ."   - Personal/hobby use license\n"
                    ."5. 200-400 words with bullet points\n"
                    ."6. SEO-friendly with keywords: STL file, 3D print file, digital download, printable\n"
                    ."7. Focus on THIS specific product\n"
                    ."8. IMPORTANT: Clearly state NO PHYSICAL ITEM WILL BE SHIPPED\n\n"
                    .'Output ONLY the description, nothing else.';

                $systemPrompt = 'You are an Etsy SEO expert specializing in digital 3D model files (STL). Write descriptions for DIGITAL DOWNLOADS. '
                    .'Use a few emojis. Always write in English. '
                    .'CRITICAL: Make it VERY CLEAR this is a digital file download, NOT a physical product. The customer prints it themselves.';
            } else {
                $prompt = "Write an SEO-optimized Etsy product description.\n\n"
                    ."Product: {$originalTitle}\n"
                    .($originalDescription ? "Original description: {$originalDescription}\n" : '')
                    .$specsText."\n"
                    ."RULES:\n"
                    ."1. Write in English (translate if the product info is in French or another language)\n"
                    ."2. Warm, friendly, sales-driven tone with a few emojis (not too many)\n"
                    ."3. Remove mentions of: wholesale, dropshipping, China, AliExpress\n"
                    ."4. Highlight the ACTUAL product features (e.g., for a kimono: Japanese style, Mount Fuji print, beach cardigan, etc.)\n"
                    ."5. 200-400 words with bullet points for key features\n"
                    ."6. SEO-friendly with natural keyword placement\n"
                    ."7. NEVER write generic descriptions - focus on THIS specific product\n"
                    ."8. Include care instructions if relevant\n\n"
                    .'Output ONLY the description, nothing else.';

                $systemPrompt = 'You are an Etsy SEO expert and copywriter. Write product descriptions that are warm, friendly, and convert well. '
                    .'Use a few emojis to make it attractive. Always write in English. '
                    .'CRITICAL: Base the description on the ACTUAL product - never write generic content. '
                    .'Focus on the real product keywords and features.';
            }

            // Append custom prompt if provided
            if (! empty($customPrompt)) {
                $systemPrompt .= "\n\nADDITIONAL SHOP INSTRUCTIONS:\n".$customPrompt;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
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

                return trim($result['choices'][0]['message']['content'] ?? $this->fallbackOptimizeDescription($originalTitle, $originalDescription, $specs, $is3DPrint));
            }

            return $this->fallbackOptimizeDescription($originalTitle, $originalDescription, $specs, $is3DPrint);

        } catch (\Exception $e) {
            Log::error('Groq description optimization failed', ['error' => $e->getMessage()]);

            return $this->fallbackOptimizeDescription($originalTitle, $originalDescription, $specs, $is3DPrint);
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
    public function generateTags(string $title, ?string $description = null, bool $is3DPrint = false): array
    {
        if (! $this->apiKey) {
            return $this->fallbackGenerateTags($title, $is3DPrint);
        }

        try {
            if ($is3DPrint) {
                $prompt = "Generate exactly 13 Etsy SEO tags for this DIGITAL STL FILE:\n\n"
                    ."Product: {$title}\n"
                    .($description ? 'Description: '.substr($description, 0, 500)."\n" : '')
                    ."\n"
                    ."RULES:\n"
                    ."1. Each tag maximum 20 characters\n"
                    ."2. Include digital file tags: 'stl file', '3d print file', 'digital download', 'printable'\n"
                    ."3. Include product-specific tags based on what the 3D model actually is\n"
                    ."4. Mix of digital/STL terms and product-specific keywords\n"
                    ."5. All tags in English\n"
                    ."6. No duplicates\n"
                    ."7. Focus on digital download keywords\n\n"
                    .'Output ONLY 13 tags separated by commas on a single line, nothing else.';

                $systemPrompt = 'You are an Etsy SEO expert for digital STL files. Generate exactly 13 tags. '
                    .'Each tag max 20 characters. Mix digital download keywords with product-specific terms. '
                    .'Output only the tags separated by commas.';
            } else {
                $prompt = "Generate exactly 13 Etsy SEO tags for this product:\n\n"
                    ."Product: {$title}\n"
                    .($description ? 'Description: '.substr($description, 0, 500)."\n" : '')
                    ."\n"
                    ."RULES:\n"
                    ."1. Each tag maximum 20 characters\n"
                    ."2. Tags must relate to the ACTUAL product (e.g., 'japanese kimono', 'mount fuji', 'haori jacket', 'beach cardigan')\n"
                    ."3. Mix of specific keywords and broader terms\n"
                    ."4. All tags in English\n"
                    ."5. No duplicates\n"
                    ."6. NEVER use generic tags like 'handmade gift' unless truly relevant to the product\n\n"
                    .'Output ONLY 13 tags separated by commas on a single line, nothing else.';

                $systemPrompt = 'You are an Etsy SEO expert. Generate exactly 13 tags based on the ACTUAL product. '
                    .'Each tag max 20 characters. Focus on real product keywords, not generic terms. '
                    .'Output only the tags separated by commas.';
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
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
                $tags = array_filter($tags, fn ($tag) => ! empty($tag));
                $tags = array_slice(array_values($tags), 0, 13);

                if (count($tags) >= 10) {
                    return $tags;
                }
            }

            return $this->fallbackGenerateTags($title, $is3DPrint);

        } catch (\Exception $e) {
            Log::error('Groq tags generation failed', ['error' => $e->getMessage()]);

            return $this->fallbackGenerateTags($title, $is3DPrint);
        }
    }

    /**
     * Select relevant tags from shop's available tags list, or generate freely if list is empty.
     *
     * @param  string  $title  Product title
     * @param  string  $description  Product description
     * @param  array  $availableTags  Shop's available tags list (empty = free generation)
     * @param  bool  $is3DPrint  Whether this is a 3D print/STL file
     * @return array Array of 13 tags
     */
    public function selectRelevantTags(string $title, string $description, array $availableTags = [], bool $is3DPrint = false): array
    {
        // If no available tags, generate freely
        if (empty($availableTags)) {
            Log::info('No available tags for shop, generating freely');

            return $this->generateTags($title, $description, $is3DPrint);
        }

        if (! $this->apiKey) {
            return $this->fallbackSelectTags($title, $description, $availableTags);
        }

        try {
            $tagsListString = implode(', ', $availableTags);

            $prompt = "Select the 13 most relevant tags for this product from the available list.\n\n"
                ."Product title: {$title}\n"
                .'Product description: '.substr($description, 0, 500)."\n\n"
                ."Available tags: {$tagsListString}\n\n"
                ."RULES:\n"
                ."1. Select EXACTLY 13 tags from the list above\n"
                ."2. Choose tags that best match the product\n"
                ."3. Prioritize specific tags over generic ones\n"
                ."4. Only select tags that exist in the available list\n\n"
                .'Output ONLY 13 tags separated by commas on a single line, nothing else.';

            $systemPrompt = 'You are an Etsy SEO expert. Select exactly 13 tags from the provided list that best match the product. '
                .'Only output tags that exist in the available list. Output only the tags separated by commas.';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 200,
                'temperature' => 0.5,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $tagsString = trim($result['choices'][0]['message']['content'] ?? '');

                // Parse tags from comma-separated string
                $selectedTags = array_map(fn ($tag) => strtolower(trim($tag)), explode(',', $tagsString));

                // Filter: only keep tags that exist in available list
                $availableTagsLower = array_map('strtolower', $availableTags);
                $validTags = array_filter($selectedTags, fn ($tag) => in_array($tag, $availableTagsLower));

                // Remove duplicates and limit to 13
                $validTags = array_unique($validTags);
                $validTags = array_slice(array_values($validTags), 0, 13);

                Log::info('AI selected tags', ['count' => count($validTags), 'tags' => $validTags]);

                // If we got at least 10 valid tags, return them
                if (count($validTags) >= 10) {
                    // Pad to 13 if needed
                    while (count($validTags) < 13 && count($validTags) < count($availableTagsLower)) {
                        foreach ($availableTagsLower as $tag) {
                            if (! in_array($tag, $validTags)) {
                                $validTags[] = $tag;
                                break;
                            }
                        }
                    }

                    return array_slice($validTags, 0, 13);
                }
            }

            return $this->fallbackSelectTags($title, $description, $availableTags);

        } catch (\Exception $e) {
            Log::error('Groq tag selection failed', ['error' => $e->getMessage()]);

            return $this->fallbackSelectTags($title, $description, $availableTags);
        }
    }

    /**
     * Select the best Etsy category for a product from shop's available categories.
     *
     * @param  string  $title  Product title
     * @param  string  $description  Product description
     * @param  array  $categories  Shop's etsy_categories array
     * @return string|null Category name or null if no match
     */
    public function selectCategory(string $title, string $description, array $categories): ?string
    {
        if (empty($categories)) {
            return null;
        }

        // Build category names list
        $categoryNames = array_map(fn ($cat) => $cat['name'] ?? '', $categories);
        $categoryNames = array_filter($categoryNames);

        if (empty($categoryNames)) {
            return null;
        }

        if (! $this->apiKey) {
            return $this->fallbackSelectCategory($title, $description, $categories);
        }

        try {
            // Build category info for AI
            $categoryInfo = [];
            foreach ($categories as $cat) {
                $info = $cat['name'];
                if (! empty($cat['keywords'])) {
                    $info .= ' (keywords: '.$cat['keywords'].')';
                }
                $categoryInfo[] = $info;
            }
            $categoryListString = implode("\n- ", $categoryInfo);

            $prompt = "Select the SINGLE most appropriate category for this product.\n\n"
                ."Product title: {$title}\n"
                .'Product description: '.substr($description, 0, 500)."\n\n"
                ."Available categories:\n- {$categoryListString}\n\n"
                ."RULES:\n"
                ."1. Select ONLY ONE category name from the list\n"
                ."2. Match based on product type and keywords\n"
                ."3. Output ONLY the category name, nothing else\n"
                .'4. If no good match, output the most generic/default category';

            $systemPrompt = 'You are an Etsy category expert. Select exactly one category name from the provided list. '
                .'Output only the category name, no explanations.';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(20)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 50,
                'temperature' => 0.3,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $selectedCategory = trim($result['choices'][0]['message']['content'] ?? '');

                // Validate that the selected category exists in our list
                foreach ($categories as $cat) {
                    if (strcasecmp($cat['name'], $selectedCategory) === 0) {
                        Log::info('AI selected category', ['category' => $cat['name']]);

                        return $cat['name'];
                    }
                }

                // Fuzzy match if exact match fails
                foreach ($categories as $cat) {
                    if (str_contains(strtolower($selectedCategory), strtolower($cat['name'])) ||
                        str_contains(strtolower($cat['name']), strtolower($selectedCategory))) {
                        Log::info('AI selected category (fuzzy match)', ['category' => $cat['name']]);

                        return $cat['name'];
                    }
                }
            }

            return $this->fallbackSelectCategory($title, $description, $categories);

        } catch (\Exception $e) {
            Log::error('Groq category selection failed', ['error' => $e->getMessage()]);

            return $this->fallbackSelectCategory($title, $description, $categories);
        }
    }

    /**
     * Fallback category selection (keyword matching).
     */
    protected function fallbackSelectCategory(string $title, string $description, array $categories): ?string
    {
        $text = strtolower($title.' '.$description);
        $bestCategory = null;
        $bestScore = 0;

        foreach ($categories as $cat) {
            $score = 0;
            $catName = strtolower($cat['name'] ?? '');
            $keywords = strtolower($cat['keywords'] ?? '');

            // Check if category name appears in text
            if ($catName && str_contains($text, $catName)) {
                $score += 10;
            }

            // Check keywords
            if ($keywords) {
                $keywordList = array_map('trim', explode(',', $keywords));
                foreach ($keywordList as $keyword) {
                    if ($keyword && str_contains($text, $keyword)) {
                        $score += 5;
                    }
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCategory = $cat['name'];
            }
        }

        // If no match found, return first category as default
        if (! $bestCategory && ! empty($categories)) {
            $bestCategory = $categories[0]['name'] ?? null;
        }

        Log::info('Fallback selected category', ['category' => $bestCategory, 'score' => $bestScore]);

        return $bestCategory;
    }

    /**
     * Fallback tag selection (keyword matching).
     */
    protected function fallbackSelectTags(string $title, string $description, array $availableTags): array
    {
        $text = strtolower($title.' '.$description);
        $selectedTags = [];

        // Score each available tag by how well it matches the product
        $tagScores = [];
        foreach ($availableTags as $tag) {
            $tagLower = strtolower($tag);
            $score = 0;

            // Check if tag appears in title (higher weight)
            if (str_contains(strtolower($title), $tagLower)) {
                $score += 10;
            }

            // Check if tag appears in description
            if (str_contains($text, $tagLower)) {
                $score += 5;
            }

            // Check if any word in the tag appears in the text
            $tagWords = explode(' ', $tagLower);
            foreach ($tagWords as $word) {
                if (strlen($word) > 2 && str_contains($text, $word)) {
                    $score += 2;
                }
            }

            $tagScores[$tagLower] = $score;
        }

        // Sort by score descending
        arsort($tagScores);

        // Take top 13 tags
        $selectedTags = array_slice(array_keys($tagScores), 0, 13);

        Log::info('Fallback selected tags', ['count' => count($selectedTags), 'tags' => $selectedTags]);

        return $selectedTags;
    }

    /**
     * Fallback tag generation (rule-based).
     */
    protected function fallbackGenerateTags(string $title, bool $is3DPrint = false): array
    {
        // Extract words from title
        $words = preg_split('/\s+/', strtolower($title));
        $words = array_filter($words, fn ($w) => strlen($w) > 2);

        $tags = [];

        // Add STL/digital file tags first if applicable
        if ($is3DPrint) {
            $tags = ['stl file', '3d print file', 'digital download', 'printable', 'stl download'];
        }

        // Add words as tags (max 20 chars each)
        foreach ($words as $word) {
            $word = preg_replace('/[^a-z0-9\s]/', '', $word);
            if (strlen($word) > 2 && strlen($word) <= 20 && ! in_array($word, $tags)) {
                $tags[] = $word;
            }
            if (count($tags) >= 13) {
                break;
            }
        }

        // Pad with generic tags if needed
        if ($is3DPrint) {
            $genericTags = ['3d model', 'instant download', 'diy print', 'maker gift', 'stl', '3d design', 'print file', 'digital file'];
        } else {
            $genericTags = ['handmade', 'unique gift', 'gift for her', 'gift for him', 'home decor', 'vintage style', 'boho', 'minimalist', 'custom'];
        }
        while (count($tags) < 13 && ! empty($genericTags)) {
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
            $title = preg_replace('/\b'.preg_quote($term, '/').'\b/i', '', $title);
        }

        // Clean up multiple spaces
        $title = preg_replace('/\s+/', ' ', $title);

        // Capitalize first letter of each word
        $title = ucwords(strtolower(trim($title)));

        // Limit to 140 characters
        if (strlen($title) > 140) {
            $title = substr($title, 0, 137).'...';
        }

        return $title;
    }

    /**
     * Fallback description optimization (rule-based).
     */
    protected function fallbackOptimizeDescription(string $title, ?string $description, array $specs, bool $is3DPrint = false): string
    {
        if ($is3DPrint) {
            $optimized = "📁 {$title} - STL File | Digital Download\n\n";
            $optimized .= "⚠️ THIS IS A DIGITAL PRODUCT - NO PHYSICAL ITEM WILL BE SHIPPED ⚠️\n\n";
            $optimized .= "Print this amazing 3D model on your own printer!\n\n";

            $optimized .= "📥 What You'll Receive:\n";
            $optimized .= "• STL file(s) - Instant download after purchase\n";
            $optimized .= "• Ready to print on any FDM or SLA 3D printer\n";
            $optimized .= "• High-quality, tested model\n\n";

            if ($description && strlen($description) > 50) {
                $cleaned = strip_tags($description);
                $cleaned = preg_replace('/\s+/', ' ', trim($cleaned));
                $optimized .= "📝 About This Model:\n";
                $optimized .= substr($cleaned, 0, 300).(strlen($cleaned) > 300 ? '...' : '')."\n\n";
            }

            $optimized .= "🖨️ Printing Recommendations:\n";
            $optimized .= "• Layer height: 0.2mm for speed, 0.12mm for detail\n";
            $optimized .= "• Infill: 15-20% for most models\n";
            $optimized .= "• Supports: Check preview images\n";
            $optimized .= "• Material: PLA, PETG, or ABS recommended\n\n";

            $optimized .= "📋 Important Notes:\n";
            $optimized .= "• This is a DIGITAL FILE - you print it yourself\n";
            $optimized .= "• No refunds on digital products\n";
            $optimized .= "• For personal use only\n\n";

            $optimized .= '💡 Perfect for makers, hobbyists, and 3D printing enthusiasts!';
        } else {
            $optimized = "✨ {$title}\n\n";
            $optimized .= "Discover this unique and beautiful item, carefully selected for its quality and style.\n\n";

            if (! empty($specs)) {
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
                $optimized .= substr($cleaned, 0, 300).(strlen($cleaned) > 300 ? '...' : '')."\n\n";
            }

            $optimized .= "💝 Perfect for gifts or personal use!\n\n";
            $optimized .= '📦 Carefully packaged and shipped with care.';
        }

        return $optimized;
    }
}
