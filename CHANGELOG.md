# CHANGELOG - Historique des améliorations

## Avril 2026

### IA - Changement de modèle vision
- **Avant** : Llama 4 Scout 17B (Groq)
- **Après** : Gemini Flash 2.0 (Google AI Studio)
- Meilleure détection des couleurs exactes, matières et textures textiles
- Coût : gratuit (1 500 req/jour)
- Fichier : `app/Services/ContentOptimizerService.php`

### Fiche produit - Champs attributs Etsy
- Ajout de 3 nouveaux champs remplis automatiquement par l'IA : **Couleur principale**, **Couleur secondaire**, **Matériaux** (max 5)
- Les valeurs correspondent aux listes fixes acceptées par Etsy (19 couleurs, 33 matières)
- L'extension "Ouvrir Etsy & Remplir" envoie ces champs dans le formulaire Etsy automatiquement
- Fichiers : `app/Services/ContentOptimizerService.php`, `resources/views/products/edit.blade.php`, `browser-extension/content/etsy.js`

### IA - Amélioration du prompt description
- Ajout d'une liste de mots/phrases interdits pour éliminer le contenu générique
- CTA renforcé : désir + urgence douce, tout en gardant le ton humain Etsy
- Fichier : `app/Services/ContentOptimizerService.php`

---

## Coûts mensuels estimés (50 produits/mois)

| Service | Coût |
|---------|------|
| Groq (titres, descriptions, tags) | **0€** |
| Gemini Flash 2.0 (vision) | **0€** |
| Fal.ai (génération images) | **$3 à $9/mois** |

---

## Architecture IA actuelle

| Tâche | Modèle | Provider |
|-------|--------|----------|
| Analyse visuelle image | Gemini 2.0 Flash | Google |
| Optimisation titre | Llama 3.3 70B | Groq |
| Génération description | Llama 3.3 70B | Groq |
| Sélection tags | Llama 3.3 70B | Groq |
| Génération images | Nano Banana + ESRGAN | Fal.ai |
