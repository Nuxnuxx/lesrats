# Intégration Alura API — Roadmap LesRats

## Contexte

LesRats génère déjà du contenu SEO via Groq LLM, mais sans données de marché réelles. Les tags sont "inventés" par l'IA sans validation sur les vraies tendances de recherche Etsy. Alura comblerait ce gap en apportant des volumes de recherche réels, scores de compétition, et intelligence marché.

**Statut** : En évaluation. Souscrire au plan Growth (~$19.99/mois) avant d'implémenter.

---

## Ce qu'Alura Growth offre

| Fonctionnalité API | Ce que ça donne | Valeur pour LesRats |
|--------------------|-----------------|---------------------|
| **Keyword Research** | Volume de recherche, score de compétition, engagement par mot-clé Etsy | ★★★★★ Remplace les tags "inventés" par des tags avec vraies données |
| **Product Research** | Bestsellers par catégorie, revenu estimé, nombre de ventes | ★★★★★ Valider un produit AVANT d'investir du temps |
| **Listing Analysis** | Score d'optimisation d'une fiche (title, tags, photos, desc) | ★★★★☆ Évaluer la qualité post-import |
| **Shop Analyzer** | Stats d'un shop concurrent (revenu, top produits) | ★★★☆☆ Veille concurrentielle |
| **Market Trends** | Niches en croissance, saisonnalité | ★★★☆☆ Orientation stratégique |

---

## Analyse ROI

**Ça vaut le coup si** :
- Tu listes 10+ produits/mois (temps économisé sur la recherche de mots-clés > $19.99)
- Tu veux scaler : meilleurs tags = meilleure visibilité organique = plus de ventes sans pub
- Tu opères plusieurs shops (ROI se multiplie par shop)

**Risques** :
- L'API Alura n'est pas publiquement documentée comme API tierce "stable" — peut être scraping-based
- Si listing irrégulier, le coût fixe mensuel pèse plus que la valeur générée

**À vérifier avant de souscrire** :
- [ ] Confirmer avec Alura support que le plan Growth inclut l'accès API (pas seulement l'UI)
- [ ] Demander la documentation des endpoints disponibles
- [ ] Vérifier les rate limits (usage automatisé)

---

## Roadmap d'intégration (4 phases)

### Phase 1 — Enrichissement des tags ⭐ Priorité max

**Impact** : Chaque produit créé aura des tags validés par les vraies données de recherche Etsy.

**Flow** :
```
Groq génère 13 tags candidats
        ↓
Alura valide chaque tag (volume, competition score)
        ↓
App trie par score (haut volume + faible compétition en priorité)
        ↓
UI affiche les tags avec indicateurs visuels (🟢 🟡 🔴)
```

**Fichiers à modifier** :
- `app/Services/ContentOptimizerService.php` — ajouter `enrichTagsWithAlura(array $tags): array`
- `app/Http/Controllers/ProductController.php` — passer tags enrichis au frontend
- `config/services.php` — ajouter config Alura
- `.env` — ajouter `ALURA_API_KEY`

---

### Phase 2 — Viability Score avant import

**Impact** : Éviter d'importer des produits sans demande sur Etsy.

**Flow** :
```
Utilisateur colle URL AliExpress
        ↓
Scraping produit (existant via AliExpressScraperService)
        ↓
Alura product research sur le nom/catégorie
        ↓
Affiche : "Demande estimée : 847 ventes/mois — Compétition : Moyenne"
        ↓
Utilisateur décide d'importer ou non
```

**Fichiers à modifier** :
- `app/Services/AluraService.php` — nouveau service
- `app/Services/AliExpressScraperService.php` — déclencher check après scraping
- `resources/views/products/create.blade.php` — widget viability score

---

### Phase 3 — Listing Score post-création

**Impact** : Feedback immédiat sur la qualité d'optimisation de chaque fiche.

**Fichiers à modifier** :
- `resources/views/products/show.blade.php` — widget score Alura
- `app/Http/Controllers/ProductController.php` — endpoint AJAX pour le score

---

### Phase 4 — Dashboard Market Intelligence

**Impact** : Tendances par niche, top produits du moment directement dans le dashboard.

**Fichiers à modifier** :
- `app/Http/Controllers/DashboardController.php`
- `resources/views/dashboard.blade.php`
- `resources/views/components/market-trends.blade.php` — nouveau composant

---

## Architecture du service Alura

```php
// app/Services/AluraService.php
class AluraService {
    public function getKeywordData(string $keyword): array
    public function validateTags(array $tags): array       // tags triés par score
    public function getProductResearch(string $productName, string $category): array
    public function getListingScore(string $title, array $tags, string $description): int
    public function getMarketTrends(string $category): array
}
```

Config dans `config/services.php` :
```php
'alura' => [
    'api_key'  => env('ALURA_API_KEY'),
    'base_url' => 'https://app.alura.io/api',  // à confirmer avec leur doc
],
```

---

## Tests & Vérification

1. Mocker l'API Alura avec des réponses fixes pour tester le flow sans clé active
2. Tester le tri des tags sur un produit existant (comparer avant/après)
3. S'assurer que les appels Alura sont async (ne bloquent pas le chargement page)
4. Monitorer le nombre d'appels API/mois pour rester dans les limites du plan Growth

---

## Recommandation

**Commencer par la Phase 1 uniquement.** C'est le feature avec le plus d'impact direct sur la visibilité Etsy, pour l'effort le plus faible. Si après 1 mois les produits rankent mieux, le ROI est prouvé — on déroule les phases suivantes.
