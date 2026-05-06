# Référence des formulaires Etsy par catégorie

Ce dossier contient la cartographie des formulaires Etsy par catégorie, utilisée par Claude pour implémenter le remplissage automatique dans `browser-extension/content/etsy.js`.

---

## Stratégie : JSON léger uniquement

Plus de fichiers HTML (trop lourds, trop de tokens). Chaque catégorie est documentée dans un seul `flow.json` capturé directement depuis la console DevTools. Claude reçoit uniquement les IDs, sélecteurs et valeurs — suffisant pour générer le code.

---

## Structure

```
docs/etsy-forms/
└── {categorie}/
    └── flow.json    ← unique fichier, contient tous les états et champs
```

---

## Script de capture — à coller dans la console DevTools

Sur la page Etsy (formulaire de création de fiche, catégorie sélectionnée) :

```js
copy(JSON.stringify({
  url: location.href,
  fields: [
    ...document.querySelectorAll('[id^="attribute-"], [id^="field-attributes-"], select, input[type="radio"], input[type="checkbox"]')
  ].map(el => {
    const label = document.querySelector(`label[for="${el.id}"]`)?.textContent?.trim()
      || el.closest('[data-attribute-wrapper]')?.querySelector('p')?.textContent?.trim()
      || null;
    const opts = el.tagName === 'SELECT'
      ? Array.from(el.options).map(o => ({ value: o.value, label: o.text.trim() }))
      : undefined;
    return {
      id: el.id || undefined,
      name: el.name || undefined,
      type: el.type || el.tagName.toLowerCase(),
      label,
      options: opts
    };
  }).filter(f => f.id || f.name),
  buttons: [...document.querySelectorAll('button[role="menuitemradio"], button[data-variations-overlay-trigger]')]
    .map(b => ({
      text: b.textContent?.trim(),
      role: b.getAttribute('role'),
      container: b.closest('[id]')?.id || null
    }))
}, null, 2))
```

Colle le résultat JSON ici — Claude génère le `flow.json` complet et le bloc `etsy.js`.

---

## Format du flow.json

```json
{
  "category": "Nom affiché dans Etsy",
  "states": [
    {
      "id": "base",
      "description": "État initial après sélection de la catégorie",
      "fields": [
        {
          "label": "Saison",
          "selector": "select#attribute-475",
          "type": "select",
          "options": [
            { "value": "462", "label": "Printemps" },
            { "value": "466", "label": "Été" }
          ]
        },
        {
          "label": "Matériaux",
          "selector": "#field-attributes-attribute-357",
          "type": "typeahead"
        }
      ]
    },
    {
      "id": "sous-etat",
      "description": "Champs apparus après une interaction",
      "trigger": {
        "field": "Nom du champ déclencheur",
        "action": "select|click|checkbox",
        "value": "valeur sélectionnée"
      },
      "fields": []
    }
  ]
}
```

**Types de champ :**
| type | description |
|---|---|
| `select` | `<select>` contrôlé par React — nécessite `nativeSet` |
| `typeahead` | Input + menu déroulant filtrable |
| `typeahead_menu` | Typeahead avec sélection multiple via boutons `menuitemradio` |
| `radio` | Groupe de `<input type="radio">` |
| `checkbox` | `<input type="checkbox">` |

**Types d'action dans `trigger` :**
| action | quand |
|---|---|
| `select` | sélection dans un `<select>` |
| `click` | clic sur un bouton qui révèle des champs |
| `checkbox` | activation d'une case à cocher |

---

## Workflow avec Claude

1. Ouvrir Etsy → Créer une fiche → sélectionner la catégorie cible
2. Coller le script de capture dans la console DevTools → `copy(...)` met le JSON dans le presse-papier
3. Si des champs apparaissent après interaction (ex: sélectionner une valeur ouvre un sous-formulaire) : interagir, puis relancer le script et coller le second JSON
4. Partager les JSONs ici avec : *"Génère le flow.json et le code etsy.js pour la catégorie X"*

Claude génère :
- Le `flow.json` complet avec sélecteurs et valeurs
- Le bloc de code à ajouter dans `fillCategoryAttributes()` et la fonction `fill{Categorie}()`

---

## Catégories documentées

| Catégorie | Dossier | Statut |
|---|---|---|
| Vestes et manteaux | `Vestes et manteaux/` | ✅ Implémenté |
| Pantalons | `vetements-pantalons/` | ✅ Implémenté |
