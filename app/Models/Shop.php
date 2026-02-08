<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'description',
        'logo_path',
        'currency',
        'is_active',
        'ai_title_prompt',
        'ai_description_prompt',
        'ai_image_prompt',
        'ai_specific_prompts',
        'ai_image_enabled',
        'ai_backgrounds',
        'etsy_categories',
        'available_tags',
        'default_margin_us',
        'default_margin_other',
        'total_revenue',
        'total_orders',
    ];

    /**
     * Default AI title/description prompt template.
     */
    public const DEFAULT_AI_DESCRIPTION_PROMPT = <<<'PROMPT'
Tu es un expert en SEO Etsy et en rédaction de fiches produits optimisées. J'ai une boutique Etsy en dropshipping spécialisée dans [SHOP_NICHE].

À chaque fois que je te donnerai :
• Un nom de produit AliExpress
• Sa description
• Et éventuellement des mots-clés pertinents ou une fiche concurrent

Ta mission est de générer pour moi une fiche produit Etsy complète comprenant :
1. Un titre optimisé SEO (clairement en rapport avec les mots-clés, sans spam, mais efficace pour le référencement).
2. Une description optimisée SEO, agréable à lire, fluide et vendeuse, avec un style chaleureux et quelques emojis pour le rendre attrayant (mais pas trop).
3. Une liste de 13 tags Etsy, chacun de maximum 20 caractères, séparés par des virgules, optimisés pour mon SEO Etsy afin que je puisse les copier-coller directement.

Règles importantes :
• Mets toujours les tags sur une seule ligne séparés par des virgules.
• Le texte doit être en anglais.
• Tu peux t'inspirer de mes concurrents si je t'en fournis, mais ne copie jamais mot pour mot.
• Le style doit rester naturel et vendeur, pas trop "robotique" ni bourré de mots-clés.
PROMPT;

    /**
     * Default AI image prompt template.
     */
    public const DEFAULT_AI_IMAGE_PROMPT = <<<'PROMPT'
Agir en tant que générateur d'images de produits pour la plateforme ETSY, spécialisé dans la création de 'Fiche produit'.

Objectif et Buts:
* Générer des images de produits de haute qualité, réalistes et d'apparence luxueuse, adaptées à l'e-commerce.
* Respecter strictement les contraintes de format, de fond et d'intégration du logo.
* Fournir différentes vues du produit sur demande (face, dos, côtés, autres angles).

Comportements et Règles:
1) Configuration Initiale et Contexte:
   a) Le style doit être un rendu réaliste, luxueux, de qualité studio professionnelle.
   b) Le fond doit être neutre et épuré (blanc ou gris clair).
   c) Le format de sortie de l'image est toujours carré (ratio 1:1).

2) Génération d'Images et Contraintes Visuelles:
   a) Intégrer le logo de la boutique si fourni.
   b) Le logo doit conserver rigoureusement la même taille sur toutes les photos générées.
   c) Produire la vue demandée (face, dos, côtés, etc.). Si aucune vue n'est spécifiée, proposer la vue de face par défaut.
   d) Exclure strictement tout élément, accessoire, ou détail non explicitement demandé.

Ton Général:
* Professionnel, axé sur la qualité et la précision.
* Visuels de studio haut de gamme.

Boutique: [SHOP_NICHE]
PROMPT;

    protected $casts = [
        'is_active' => 'boolean',
        'ai_image_enabled' => 'boolean',
        'ai_specific_prompts' => 'array',
        'ai_backgrounds' => 'array',
        'etsy_categories' => 'array',
        'available_tags' => 'array',
        'default_margin_us' => 'decimal:2',
        'default_margin_other' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'total_orders' => 'integer',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shop_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function members(): HasMany
    {
        return $this->hasMany(ShopMembership::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ============================================
    // ACCESSORS
    // ============================================

    /**
     * Get products count for this shop.
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Get orders count for this shop.
     */
    public function getOrdersCountAttribute(): int
    {
        return $this->orders()->count();
    }

    /**
     * Get today's orders count.
     */
    public function getTodaysOrdersCountAttribute(): int
    {
        return $this->orders()->whereDate('created_at', today())->count();
    }

    /**
     * Get today's revenue.
     */
    public function getTodaysRevenueAttribute(): float
    {
        return (float) $this->orders()
            ->whereDate('created_at', today())
            ->sum('total_price');
    }

    // ============================================
    // METHODS
    // ============================================

    /**
     * Update cached stats from orders.
     */
    public function updateCachedStats(): void
    {
        $this->update([
            'total_revenue' => $this->orders()->sum('total_price'),
            'total_orders' => $this->orders()->count(),
        ]);
    }

    /**
     * Get best selling product.
     */
    public function getBestSellingProduct(): ?Product
    {
        $bestSellingProductId = $this->orders()
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->whereNotNull('order_items.product_id')
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as total_sold')
            ->groupBy('order_items.product_id')
            ->orderByDesc('total_sold')
            ->value('product_id');

        return $bestSellingProductId ? Product::find($bestSellingProductId) : null;
    }

    /**
     * Get the logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Get the effective AI description prompt with shop niche injected.
     */
    public function getEffectiveAiDescriptionPrompt(): string
    {
        $prompt = $this->ai_description_prompt ?: self::DEFAULT_AI_DESCRIPTION_PROMPT;
        $niche = $this->description ?: 'produits variés';

        return str_replace('[SHOP_NICHE]', $niche, $prompt);
    }

    /**
     * Get the effective AI image prompt with shop niche injected.
     */
    public function getEffectiveAiImagePrompt(): string
    {
        $prompt = $this->ai_image_prompt ?: self::DEFAULT_AI_IMAGE_PROMPT;
        $niche = $this->description ?: 'produits variés';

        return str_replace('[SHOP_NICHE]', $niche, $prompt);
    }

    /**
     * Get revenue data for chart (last N days).
     */
    public function getRevenueChartData(int $days = 30): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();

        $revenues = $this->orders()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(total_price) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('revenue', 'date')
            ->toArray();

        $dates = [];
        $data = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($days - 1 - $i)->format('Y-m-d');
            $dates[] = now()->subDays($days - 1 - $i)->format('d/m');
            $data[] = (float) ($revenues[$date] ?? 0);
        }

        return [
            'dates' => $dates,
            'data' => $data,
        ];
    }
}
