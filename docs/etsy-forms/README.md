# Référence des formulaires Etsy par catégorie

Ce dossier contient des snapshots HTML des formulaires de création de fiches Etsy, organisés par catégorie. Claude les utilise pour identifier les sélecteurs CSS et implémenter le remplissage automatique dans `browser-extension/content/etsy.js`.

---

## Pourquoi plusieurs fichiers par catégorie ?

Le formulaire Etsy est **dynamique** : certains champs n'apparaissent qu'après avoir interagi avec d'autres (sélectionner une option, cocher une case...). Un seul snapshot HTML ne suffit pas — il faut capturer chaque état significatif.

---

## Structure d'un dossier catégorie

```
docs/etsy-forms/
└── {categorie-parente}-{sous-categorie}/
    ├── flow.json                    ← arbre d'interactions (OBLIGATOIRE)
    ├── 01_base.html                 ← état initial après sélection de la catégorie
    ├── 02_{description}.html        ← état après une interaction
    └── 03_{description}.html        ← état après une autre interaction
```

### Convention de nommage des dossiers

Format : `{categorie-parente}-{sous-categorie}` — kebab-case, minuscules, sans accents

| Catégorie Etsy | Dossier |
|---|---|
| Bijoux & Accessoires > Bagues | `bijoux-bagues/` |
| Bijoux & Accessoires > Colliers | `bijoux-colliers/` |
| Vêtements > Femme | `vetements-femme/` |
| Maison & Jardin > Décoration | `maison-decoration/` |
| Art & Objets de collection | `art-collection/` |

### Convention de nommage des fichiers HTML

- `01_base.html` → toujours l'état initial
- `02_`, `03_`... → états déclenchés par des interactions, décrits en kebab-case

Exemples : `02_metal-or-selectionne.html`, `03_pierre-activee.html`, `04_modal-tailles.html`

---

## Format du flow.json

```json
{
  "category": "Nom affiché dans Etsy",
  "etsy_category_id": null,
  "states": [
    {
      "id": "base",
      "file": "01_base.html",
      "description": "Formulaire après sélection de la catégorie",
      "fields": []
    },
    {
      "id": "metal-or",
      "file": "02_metal-or-selectionne.html",
      "description": "Après sélection 'Or' dans le champ Métal principal",
      "trigger": {
        "field": "Métal principal",
        "action": "select",
        "value": "Or"
      },
      "fields": []
    },
    {
      "id": "pierre-activee",
      "file": "03_pierre-activee.html",
      "description": "Après activation de l'option Pierre précieuse",
      "trigger": {
        "field": "Pierre précieuse",
        "action": "checkbox",
        "value": true
      },
      "fields": []
    }
  ]
}
```

**Types d'action dans `trigger`** :
- `"select"` — menu déroulant ou radio button
- `"checkbox"` — case à cocher
- `"click"` — bouton qui ouvre un modal ou un panneau
- `"input"` — saisie de texte qui révèle des options

Le tableau `fields` est laissé vide — Claude le remplit après analyse du HTML.

---

## Comment capturer les états

### Étape 1 — État de base
1. Ouvrir Etsy → **Créer une fiche** (ou en modifier une existante)
2. Sélectionner la catégorie cible dans le formulaire
3. Attendre que les champs spécifiques à la catégorie apparaissent
4. **Clic droit → Enregistrer sous → "Page web, HTML uniquement"**
5. Nommer le fichier `01_base.html` et le placer dans le bon sous-dossier

### Étape 2 — États déclenchés
6. Interagir avec un champ (sélectionner une valeur, cocher une case, cliquer un bouton...)
7. Attendre que le sous-formulaire ou les nouveaux champs apparaissent
8. **Clic droit → Enregistrer sous** → nommer `02_{description}.html`
9. Répéter pour chaque interaction qui révèle de nouveaux champs

### Astuce : état d'un modal
Si un modal s'ouvre (ex: sélecteur de tailles), sauvegarder la page pendant que le modal est visible — il sera inclus dans le HTML.

---

## Workflow avec Claude

Une fois les fichiers déposés :

1. **Analyser** : demander à Claude de lire le dossier et d'identifier les champs
   > "Analyse `docs/etsy-forms/bijoux-bagues/` et liste les champs spécifiques à cette catégorie"

2. **Implémenter** : Claude met à jour `etsy.js` avec la logique conditionnelle
   > "Implémente le remplissage automatique pour la catégorie bijoux-bagues"

3. **Documenter** : Claude complète les tableaux `fields` dans `flow.json`

---

## Catégories déjà documentées

_(aucune pour l'instant — ajouter les dossiers au fur et à mesure)_
