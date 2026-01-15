# Analyseur de Produits AliExpress

## Vue d'ensemble

Cette fonctionnalité permet d'analyser automatiquement un lien AliExpress et de générer du contenu optimisé pour Etsy.

## Fonctionnement

### 1. Scraping AliExpress

Le service `AliExpressScraperService` extrait les données suivantes:
- Titre du produit
- Prix
- Description
- Images (jusqu'à 10)
- Spécifications techniques

### 2. Optimisation par IA

Le service `ContentOptimizerService` utilise OpenAI (optionnel) pour:

**Titre:**
- Supprime les termes "wholesale", "dropshipping", "China", etc.
- Reformule pour être attractif et SEO-friendly
- Limite à 140 caractères (standard Etsy)

**Description:**
- Réécrit dans un style "fait main" / artisanal
- Élimine les mentions de masse/gros
- Ajoute des bullet points
- Optimisé SEO avec mots-clés naturels
- 200-400 mots

**Prix:**
- Calcule automatiquement avec marge (150% par défaut)
- Applique la tarification psychologique (.99, .95)

### 3. Pré-remplissage automatique

Le JavaScript remplit automatiquement:
- Champ "Titre"
- Champ "Description"
- Champ "Prix"

## Utilisation

### Étape 1: Configuration (Optionnelle)

Pour utiliser l'optimisation OpenAI (recommandé), ajoutez dans `.env`:

```env
OPENAI_API_KEY=sk-votre-cle-api-openai
```

**Sans OpenAI:** Le système utilisera des règles basiques (fallback).

### Étape 2: Créer un produit

1. Allez sur **Produits** → **Nouveau Produit**
2. Collez l'URL AliExpress dans le champ "URL AliExpress"
   - Exemple: `https://www.aliexpress.com/item/1005004567890123.html`
3. Cliquez sur le bouton **"Analyser"**
4. Attendez 10-30 secondes (scraping + IA)
5. Le formulaire se remplit automatiquement avec les données optimisées
6. Vérifiez et ajustez si nécessaire
7. Cliquez sur **"Créer le produit"**

## Avantages

✅ **Gain de temps massif**: 5-10 minutes → 30 secondes
✅ **Contenu optimisé SEO**: Mots-clés Etsy intégrés
✅ **Style adapté à Etsy**: Ton artisanal et authentique
✅ **Prix automatique**: Marge configurée (150% par défaut)
✅ **Conformité**: Supprime termes interdits

## Limitations

⚠️ **Scraping = Non garanti**: AliExpress peut changer sa structure HTML
⚠️ **Détection possible**: Utiliser un proxy si blocage (non implémenté)
⚠️ **Coût OpenAI**: ~0,01-0,05$ par analyse (si activé)
⚠️ **Images**: Non téléchargées automatiquement (URLs seulement)

## Fallback sans OpenAI

Si `OPENAI_API_KEY` n'est pas configurée, le système utilise des **règles basiques**:

**Titre:**
- Suppression termes interdits (regex)
- Capitalisation
- Limite 140 caractères

**Description:**
- Template pré-défini avec emoji
- Insertion specs extraites
- Style générique mais fonctionnel

**Prix:**
- Même calcul de marge
- Tarification psychologique

## Configuration avancée

### Modifier le taux de marge

Dans `ContentOptimizerService::calculatePrice()`:

```php
// Par défaut: 150% (2.5x le prix AliExpress)
$suggestedPrice = $aliexpressPrice * 2.5;

// Changer à 200% (3x):
$suggestedPrice = $aliexpressPrice * 3;
```

### Modifier les prompts OpenAI

Dans `ContentOptimizerService`:
- Méthode `optimizeTitle()`: Ligne 25-30
- Méthode `optimizeDescription()`: Ligne 60-75

### Ajouter plus de patterns de scraping

Dans `AliExpressScraperService`:
- Méthode `extractTitle()`: Ajouter pattern dans `$patterns`
- Méthode `extractPrice()`: Ajouter regex
- etc.

## Troubleshooting

### "Failed to fetch AliExpress page"

**Cause:** AliExpress bloque la requête ou URL invalide.

**Solution:**
1. Vérifier que l'URL est valide (contient `/item/`)
2. Tester dans un navigateur privé
3. Ajouter un proxy (modification code nécessaire)

### "Unable to extract product data"

**Cause:** Structure HTML d'AliExpress a changé.

**Solution:**
1. Mettre à jour les patterns regex dans `AliExpressScraperService`
2. Ou saisir manuellement les données

### Analyse très lente

**Cause:** Scraping + appel OpenAI = 20-30 secondes.

**Solution:**
- Normal pour première utilisation
- Optionnel: Implémenter cache/queue pour traitement background

### Contenu pas optimisé

**Cause:** OpenAI non configuré (fallback actif).

**Solution:**
- Ajouter `OPENAI_API_KEY` dans `.env`
- Redémarrer serveur Laravel

## Prochaines améliorations possibles

### Court terme:
- [ ] Téléchargement automatique des images
- [ ] Extraction variantes (couleurs, tailles)
- [ ] Cache des résultats scraping (24h)

### Moyen terme:
- [ ] Queue job pour analyse background
- [ ] Preview avant application
- [ ] Analyse multiple produits (batch)
- [ ] Support proxies rotatifs

### Long terme:
- [ ] ML pour améliorer extraction
- [ ] Analyse sentiment/avis clients AliExpress
- [ ] Suivi prix AliExpress automatique
- [ ] Intégration CJ Dropshipping API (alternative scraping)

## Sécurité et Légalité

### ⚠️ Considérations importantes:

1. **Scraping:** Techniquement en zone grise. AliExpress peut bloquer.
2. **Propriété intellectuelle:** Vérifier droits sur images/descriptions.
3. **RGPD:** Pas de données personnelles scrapées ici (OK).
4. **Terms of Service:** Lire ToS AliExpress et Etsy.

### 💡 Recommandations:

- **Utiliser avec modération** (pas de scraping massif)
- **Toujours vérifier/modifier** le contenu généré
- **Alternative légale:** APIs officielles (CJ Dropshipping, Spocket)
- **Mention transparence:** Informer clients sur origine produits

---

**Développé avec ❤️ par TheLayns**
**Dernière mise à jour : 15 janvier 2026**
