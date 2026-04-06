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
     * Analyze product images using Groq Vision to extract visual details.
     * Tries up to 3 images — stops at first success.
     */
    public function analyzeProductImages(array $imageUrls): ?string
    {
        if (empty($imageUrls) || ! $this->apiKey) {
            return null;
        }

        foreach (array_slice($imageUrls, 0, 3) as $imageUrl) {
            $result = $this->tryAnalyzeSingleImage($imageUrl);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    private function tryAnalyzeSingleImage(string $imageUrl): ?string
    {
        $visionPrompt = 'Analyze this product image and describe ONLY what you visually see. Focus on: '
            .'1) Exact color of the BASE fabric (black, navy, white, red...). '
            .'2) Exact colors of the PATTERNS as they appear on the fabric — CRITICAL: describe the color you actually SEE, not what color that flower or animal is in real life. '
            .'Example: if flowers appear WHITE on black fabric, say "white floral print", NOT "pink sakura". If cranes appear light blue, say "light blue crane print". '
            .'3) Pattern type (floral, crane, dragon, geometric, bamboo, plain...). '
            .'4) Distinctive details (lace trim, embroidery, obi belt, ruffles, buttons...). '
            .'5) Fabric appearance (silky, matte, shiny...). '
            .'Do NOT mention use cases or occasions. Output only 2-3 sentences.';

        try {
            $referer = 'https://www.aliexpress.com/';
            if (str_contains($imageUrl, '1688.com')) {
                $referer = 'https://www.1688.com/';
            } elseif (str_contains($imageUrl, 'printables.com')) {
                $referer = 'https://www.printables.com/';
            }

            $imageResponse = Http::timeout(15)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => $referer,
                'Accept' => 'image/webp,image/jpeg,image/png,image/*,*/*',
            ])->get($imageUrl);

            if (! $imageResponse->successful()) {
                Log::warning('Image download failed for vision', [
                    'url' => $imageUrl,
                    'status' => $imageResponse->status(),
                ]);

                return null;
            }

            // Resize image to max 800px to keep payload small enough for Groq
            $imageData = $imageResponse->body();
            $imageData = $this->resizeImageData($imageData);

            $imageContent = base64_encode($imageData);
            $dataUrl = "data:image/jpeg;base64,{$imageContent}";

            return $this->callGroqVision(['url' => $dataUrl], $visionPrompt);
        } catch (\Exception $e) {
            Log::warning('Vision analysis exception', ['url' => $imageUrl, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Resize image data to max 800px on longest side, output as JPEG.
     * Falls through to original data if GD is not available or image is already small.
     */
    private function resizeImageData(string $imageData): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $imageData;
        }

        $img = @imagecreatefromstring($imageData);
        if (! $img) {
            return $imageData;
        }

        $width = imagesx($img);
        $height = imagesy($img);
        $maxDim = 800;

        if ($width <= $maxDim && $height <= $maxDim) {
            imagedestroy($img);

            return $imageData;
        }

        $ratio = min($maxDim / $width, $maxDim / $height);
        $newWidth = (int) ($width * $ratio);
        $newHeight = (int) ($height * $ratio);

        $resized = imagescale($img, $newWidth, $newHeight);
        imagedestroy($img);

        ob_start();
        imagejpeg($resized, null, 85);
        $output = ob_get_clean();
        imagedestroy($resized);

        return $output ?: $imageData;
    }

    private function callGroqVision(array $imagePayload, string $prompt): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'meta-llama/llama-4-scout-17b-16e-instruct',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'image_url', 'image_url' => $imagePayload],
                            ['type' => 'text', 'text' => $prompt],
                        ],
                    ],
                ],
                'max_tokens' => 150,
                'temperature' => 0.3,
            ]);

            if ($response->successful()) {
                return trim($response->json()['choices'][0]['message']['content'] ?? '') ?: null;
            }

            Log::warning('Groq Vision API error', [
                'url' => $imagePayload['url'] !== null && strlen($imagePayload['url']) < 200 ? $imagePayload['url'] : 'base64_data',
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::warning('Groq Vision call exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Optimize product title for Etsy.
     *
     * @param  string  $originalTitle  The original product title
     * @param  string|null  $context  Context like '3D Print' for STL files
     * @param  string|null  $customPrompt  Custom prompt from shop settings (appended to system prompt)
     */
    public function optimizeTitle(string $originalTitle, ?string $context = null, ?string $customPrompt = null, ?string $visualContext = null): string
    {
        if (! $this->apiKey) {
            return $this->fallbackOptimizeTitle($originalTitle);
        }

        try {
            $is3DPrint = $context === '3D Print';

            if ($is3DPrint) {
                $prompt = "Transform this 3D model name into a high-ranking Etsy title for a DIGITAL STL FILE.\n\n"
                    ."Original: {$originalTitle}\n"
                    .($visualContext ? "Visual details from product image: {$visualContext}\n\n" : "\n")
                    ."RULES:\n"
                    ."1. Create 4-5 distinct keyword phrases separated by commas\n"
                    ."2. Put the MOST IMPORTANT keyword first (first 40 chars = mobile preview)\n"
                    ."3. HARD LIMIT: MAXIMUM 140 characters total — count carefully before outputting\n"
                    ."4. Target 120-140 chars — use 4-5 phrases, stop when you reach 140\n"
                    ."5. Keep the actual model keywords (dragon, planter, organizer... never replace with generic words)\n"
                    ."6. Include ONE of: 'STL File', '3D Print File', 'Digital Download' — ideally in the first 60 chars\n"
                    ."7. Include use case: Home Decor, Tabletop RPG, Miniature, etc. NEVER use 'Gift'\n"
                    ."8. Translate to English if needed\n"
                    ."9. NEVER use filler like 'Amazing 3D Model', 'Unique Print', 'Gift'\n"
                    .($visualContext ? "10. Use the visual details (color, material, finish) to make the title specific and accurate\n" : '')
                    ."\nEXAMPLE FORMAT (133 chars):\n"
                    ."\"Dragon Planter STL File, 3D Print File, Succulent Pot Digital Download, Fantasy Home Decor\"\n"
                    ."(Mobile sees: \"Dragon Planter STL File\" ✓)\n\n"
                    .'Output ONLY the optimized title, nothing else.';

                $systemPrompt = 'You are an Etsy SEO title expert for digital STL files. Your ONLY goal is to maximize search visibility. '
                    .'Etsy titles are stacks of keyword phrases separated by commas — they should look natural to buyers, not robotic. '
                    .'CRITICAL: The first 40-50 characters are what mobile buyers see first. Put the most searched keyword FIRST. '
                    .'Aim for 130-140 total characters. '
                    .'Always write in English. Output only the title, no explanations.';
            } else {
                $prompt = "Transform this product title into a high-ranking Etsy title.\n\n"
                    ."Original: {$originalTitle}\n"
                    .($visualContext ? "Visual details from product image: {$visualContext}\n\n" : "\n")
                    ."RULES:\n"
                    ."1. Create 4-5 distinct keyword phrases separated by commas\n"
                    ."2. Put the MOST IMPORTANT keyword first (first 40 chars = mobile preview)\n"
                    ."3. HARD LIMIT: MAXIMUM 140 characters total — count carefully before outputting\n"
                    ."4. Target 130-140 chars — if under 120, add 1-2 more keyword phrases (occasion, style, audience)\n"
                    ."5. Keep the REAL product keywords (kimono, haori, yukata... never replace with generic words)\n"
                    ."6. Include target audience when relevant: Women, Men, Kids\n"
                    .($visualContext ? "7. MANDATORY: The first 2 phrases MUST include the specific color(s) and pattern from the visual details\n" : "7. Include specific colors and patterns if known\n")
                    .($visualContext ? "   CRITICAL COLOR RULE: Use ONLY the exact colors from the visual context — NEVER infer colors from pattern names. If visual says 'white floral', write 'White Floral', NEVER 'Pink Sakura' even if sakura are typically pink. The visual context is the only truth.\n" : '')
                    ."8. Fill remaining space with occasion/style phrases: Anime Costume, Halloween Outfit, Beach Wear, Lolita Style...\n"
                    ."9. NEVER end with a vague phrase like 'Japanese Kimono' alone — always add a qualifier (Women, Style, Outfit)\n"
                    ."10. NEVER use the word 'Gift' — it wastes space and is too generic\n"
                    ."11. Remove ONLY: wholesale, dropshipping, China, AliExpress, bulk\n"
                    ."12. Translate to English if needed\n"
                    ."13. NEVER use filler like 'Handmade', 'Unique Item', 'Beautiful', 'Asian Style'\n"
                    ."14. Each phrase should be a real search query buyers would type\n"
                    ."\nEXAMPLE (136 chars — ideal):\n"
                    ."\"Black White Crane Kimono Women, Light Blue Floral Haori Robe, Cotton Yukata Dress, Anime Lolita Outfit\"\n"
                    ."(Mobile sees: \"Black White Crane Kimono Women\" ✓)\n\n"
                    .'Output ONLY the optimized title, nothing else.';

                $systemPrompt = 'You are an Etsy SEO title expert. Your ONLY goal is to maximize search visibility on Etsy. '
                    .'Etsy titles are stacks of keyword phrases separated by commas — they should look natural to buyers, not robotic. '
                    .'CRITICAL: The first 40-50 characters are what mobile buyers see first. Put the most important keyword phrase FIRST. '
                    .'The first 60-70 characters are what desktop buyers see before truncation — make them count too. '
                    .'Aim for 130-140 total characters to maximize keyword coverage. '
                    .'Always write in English. Translate if input is in another language. '
                    .'Output only the title, no explanations, no quotes.';
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
                'max_tokens' => 200,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $title = trim($result['choices'][0]['message']['content'] ?? $originalTitle);
                // Remove quotes if present
                $title = trim($title, '"\'');
                // Hard enforce 140 char Etsy limit — cut at last comma before limit
                if (mb_strlen($title) > 140) {
                    $truncated = mb_substr($title, 0, 140);
                    $lastComma = mb_strrpos($truncated, ',');
                    $title = $lastComma !== false ? mb_substr($title, 0, $lastComma) : $truncated;
                    $title = trim($title);
                }
                // If title is too short, make a second call to extend it with more keyword phrases
                if (mb_strlen($title) < 120) {
                    $currentLen = mb_strlen($title);
                    $needed = 135 - $currentLen;
                    $extendResponse = Http::withHeaders([
                        'Authorization' => 'Bearer '.$this->apiKey,
                        'Content-Type' => 'application/json',
                    ])->timeout(20)->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => $this->model,
                        'messages' => [
                            ['role' => 'system', 'content' => 'You are an Etsy SEO expert. Add keyword phrases to extend a title. Output only the extended title, no explanations, no quotes.'],
                            ['role' => 'user', 'content' => "This Etsy title is {$currentLen} characters. Add approximately {$needed} more characters of keyword phrases (comma-separated) to reach ~135 chars total. Keep ALL existing phrases exactly as-is, only append new ones at the end.\n"
                                .($visualContext ? "Product visual context: {$visualContext}\n" : '')
                                ."Pick keyword phrases that MATCH the actual product style and vibe. Think about who really buys this product and why.\n"
                                ."DO NOT add: seasonal keywords (Halloween, Christmas), inappropriate use cases (Beach Wear for a formal garment), or generic fillers.\n"
                                ."Good additions for a dark elegant kimono: 'Cosplay Outfit', 'Japanese Robe', 'Gothic Style', 'Photo Shoot'.\n"
                                ."Good additions for a casual colorful yukata: 'Summer Festival', 'Beach Cover Up', 'Boho Style'.\n"
                                ."NEVER use 'Gift'.\nCurrent title: {$title}"],
                        ],
                        'max_tokens' => 80,
                        'temperature' => 0.7,
                    ]);
                    if ($extendResponse->successful()) {
                        $extended = trim($extendResponse->json()['choices'][0]['message']['content'] ?? '');
                        $extended = trim($extended, '"\'');
                        if (mb_strlen($extended) > mb_strlen($title)) {
                            // Truncate at last comma if over 140
                            if (mb_strlen($extended) > 140) {
                                $truncated = mb_substr($extended, 0, 140);
                                $lastComma = mb_strrpos($truncated, ',');
                                $extended = $lastComma !== false ? trim(mb_substr($extended, 0, $lastComma)) : trim($truncated);
                            }
                            if (mb_strlen($extended) > mb_strlen($title)) {
                                $title = $extended;
                            }
                        }
                    }
                }

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
    public function optimizeDescription(string $originalTitle, ?string $originalDescription = null, array $specs = [], bool $is3DPrint = false, ?string $customPrompt = null, ?string $visualContext = null): string
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
                $prompt = "Write a warm, engaging Etsy description for a DIGITAL STL FILE download.\n\n"
                    ."Product: {$originalTitle}\n"
                    .($visualContext ? "Visual details from product image: {$visualContext}\n" : '')
                    .($originalDescription ? "Original description: {$originalDescription}\n" : '')
                    .$specsText."\n"
                    ."RULES:\n"
                    ."1. 150-250 words MAXIMUM — concise and human\n"
                    ."2. Speak directly to the maker/buyer (\"You'll receive...\", \"Print this yourself...\")\n"
                    ."3. Make it clear upfront: DIGITAL DOWNLOAD — NO physical item shipped\n"
                    ."4. Include: what the model is, what's included (STL files, instant download), printing tips (infill, supports, material)\n"
                    ."5. Touch the buyer: Why is this model cool? What can they make with it?\n"
                    ."6. End with a warm call-to-action\n"
                    ."7. Use 2-3 emojis max (📁, 🖨️, ✨ style)\n\n"
                    .'Output ONLY the description, nothing else.';

                $systemPrompt = 'You are an Etsy copywriter for digital STL files. Write warm, human, maker-friendly descriptions. '
                    .'The buyer is a 3D printing enthusiast — speak their language (FDM, PLA, supports, infill). '
                    .'Use a few emojis. Always write in English. '
                    .'CRITICAL: Keep it 150-250 words maximum. Be clear that this is a DIGITAL FILE, not a physical product. '
                    .'Output only the description, no explanations.';
            } else {
                $prompt = "Write a warm, emotionally engaging Etsy product description.\n\n"
                    ."Product: {$originalTitle}\n"
                    .($visualContext ? "Visual details from product image: {$visualContext}\n" : '')
                    .($originalDescription ? "Original description: {$originalDescription}\n" : '')
                    .$specsText."\n"
                    ."RULES:\n"
                    ."1. 150-250 words MAXIMUM — concise, punchy, human\n"
                    ."2. CRITICAL: This description must be written for THIS SPECIFIC product only — mention the actual colors, pattern and details visible\n"
                    .($visualContext ? "3. MANDATORY: Mention the specific colors and pattern from the visual details in the FIRST sentence — use the EXACT colors from visual context, NEVER invent or assume colors not mentioned (e.g. if visual says 'white floral on black', do NOT write 'cherry blossom' or 'pink sakura')\n" : "3. Mention specific product features in the first sentence\n")
                    ."4. Warm and friendly tone — speak directly to the buyer (\"You'll love...\", \"Perfect for...\")\n"
                    ."5. Touch the buyer emotionally: Why would they love THIS specific item? What makes it unique?\n"
                    ."6. Include a short 'Details' section if specs are available (2-3 bullet points max)\n"
                    ."7. Remove mentions of: wholesale, dropshipping, China, AliExpress\n"
                    ."8. End with ONE direct call-to-action sentence — confident and action-oriented, not hesitant. Good: 'Add to cart and make it yours.' Bad: 'so why not make it yours today'\n"
                    ."9. NEVER write a generic description that could apply to any product\n"
                    ."10. NO emojis — write clean professional copy\n\n"
                    .'Output ONLY the description, nothing else.';

                $systemPrompt = 'You are an Etsy copywriter who writes warm, human, emotionally engaging product descriptions. '
                    .'CRITICAL: Every description must be 100% unique and specific to the exact product — never write generic content that could apply to any similar item. '
                    .'If you have visual details (colors, patterns, materials), use them in the very first sentence and stick strictly to what the visual context says — never invent colors or pattern names. '
                    .'Write like a friendly person talking to the buyer, not like a robot listing features. '
                    .'NO emojis — write clean, professional copy. Always write in English. '
                    .'CRITICAL: Keep it 150-250 words maximum. Buyers don\'t read long descriptions — make every word count. '
                    .'Output only the description, no explanations.';
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
                'max_tokens' => 500,
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
    public function generateTags(string $title, ?string $description = null, bool $is3DPrint = false, ?string $visualContext = null): array
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
                    .($visualContext ? "Visual details from image: {$visualContext}\n" : '')
                    .($description ? 'Description: '.substr($description, 0, 500)."\n" : '')
                    ."\n"
                    ."RULES:\n"
                    ."1. Each tag maximum 20 characters\n"
                    .($visualContext ? "2. MANDATORY: Include tags for the specific colors and pattern visible (e.g. 'pink sakura kimono', 'black floral robe')\n" : "2. Tags must relate to the ACTUAL product\n")
                    ."3. NEVER invent tags unrelated to what's visible (no 'Sashiko', 'Kanzashi', 'Patchwork' unless truly present)\n"
                    ."4. Mix of specific keywords and broader terms\n"
                    ."5. All tags in English\n"
                    ."6. No duplicates\n"
                    ."7. NEVER use generic tags like 'handmade', 'gift for her', 'unique item'\n\n"
                    .'Output ONLY 13 tags separated by commas on a single line, nothing else.';

                $systemPrompt = 'You are an Etsy SEO expert. Generate exactly 13 tags based on the ACTUAL product — only what is truly visible or described. '
                    .'NEVER invent tags that sound related but are not accurate (e.g. do not add Sashiko just because it sounds Japanese). '
                    .'Each tag max 20 characters. Output only the tags separated by commas.';
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
    public function selectRelevantTags(string $title, string $description, array $availableTags = [], bool $is3DPrint = false, ?string $visualContext = null): array
    {
        // If no available tags, generate freely
        if (empty($availableTags)) {
            Log::info('No available tags for shop, generating freely');

            return $this->generateTags($title, $description, $is3DPrint, $visualContext);
        }

        if (! $this->apiKey) {
            return $this->fallbackSelectTags($title, $description, $availableTags);
        }

        try {
            $tagsListString = implode(', ', $availableTags);

            $prompt = "Select the 13 most relevant tags for this product from the available list.\n\n"
                ."Product title: {$title}\n"
                .($visualContext ? "Visual details from image: {$visualContext}\n" : '')
                .'Product description: '.substr($description, 0, 500)."\n\n"
                ."Available tags: {$tagsListString}\n\n"
                ."RULES:\n"
                ."1. Select EXACTLY 13 tags from the list above\n"
                .($visualContext ? "2. PRIORITY: Select tags that match the specific colors, pattern and style visible in the image\n" : "2. Choose tags that best match the product\n")
                ."3. Prioritize specific tags over generic ones\n"
                ."4. Only select tags that exist in the available list\n\n"
                .'Output ONLY 13 tags separated by commas on a single line, nothing else.';

            $systemPrompt = 'You are an Etsy SEO expert. Select exactly 13 tags from the provided list that best match the product. '
                .'If visual details are provided (colors, patterns), prioritize tags that match what is actually visible. '
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

        // Normalize: support both old format [{name, ...}] and new format ["string"]
        $categoryNames = array_map(fn ($cat) => is_array($cat) ? ($cat['name'] ?? '') : (string) $cat, $categories);
        $categoryNames = array_values(array_filter($categoryNames));

        if (empty($categoryNames)) {
            return null;
        }

        if (! $this->apiKey) {
            return $this->fallbackSelectCategory($title, $description, $categoryNames);
        }

        try {
            $categoryListString = implode("\n- ", $categoryNames);

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
                foreach ($categoryNames as $name) {
                    if (strcasecmp($name, $selectedCategory) === 0) {
                        Log::info('AI selected category', ['category' => $name]);

                        return $name;
                    }
                }

                // Fuzzy match if exact match fails
                foreach ($categoryNames as $name) {
                    if (str_contains(strtolower($selectedCategory), strtolower($name)) ||
                        str_contains(strtolower($name), strtolower($selectedCategory))) {
                        Log::info('AI selected category (fuzzy match)', ['category' => $name]);

                        return $name;
                    }
                }
            }

            return $this->fallbackSelectCategory($title, $description, $categoryNames);

        } catch (\Exception $e) {
            Log::error('Groq category selection failed', ['error' => $e->getMessage()]);

            return $this->fallbackSelectCategory($title, $description, $categoryNames);
        }
    }

    /**
     * Fallback category selection (name matching).
     */
    protected function fallbackSelectCategory(string $title, string $description, array $categories): ?string
    {
        $text = strtolower($title.' '.$description);
        $bestCategory = null;
        $bestScore = 0;

        foreach ($categories as $catName) {
            $score = 0;
            $name = strtolower($catName);

            // Check if category name appears in text
            if ($name && str_contains($text, $name)) {
                $score += 10;
            }

            // Also check individual words from the category name
            $words = array_filter(explode(' ', $name), fn ($w) => strlen($w) > 2);
            foreach ($words as $word) {
                if (str_contains($text, $word)) {
                    $score += 3;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCategory = $catName;
            }
        }

        // If no match found, return first category as default
        if (! $bestCategory && ! empty($categories)) {
            $bestCategory = $categories[0] ?? null;
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
