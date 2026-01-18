# Installation de l'Extension LesRats sur Opera GX

## Prérequis

- Opera GX (navigateur)
- Le serveur LesRats en cours d'exécution (`php artisan serve`)

## Étapes d'Installation

### 1. Ouvrir le Gestionnaire d'Extensions

1. Ouvre **Opera GX**
2. Tape `opera://extensions` dans la barre d'adresse et appuie sur Entrée
3. Ou va dans **Menu (≡)** → **Extensions** → **Extensions**

### 2. Activer le Mode Développeur

1. En haut à droite de la page des extensions, active le switch **"Mode développeur"**
2. De nouveaux boutons vont apparaître

### 3. Charger l'Extension

1. Clique sur le bouton **"Charger l'extension non empaquetée"**
2. Navigue vers le dossier de ton projet:
   ```
   C:\Users\Laylay\Documents\LA_MAGIE1\browser-extension
   ```
3. Sélectionne ce dossier et clique sur **"Sélectionner un dossier"**

### 4. Vérifier l'Installation

- L'extension **"LesRats - AliExpress Importer"** devrait maintenant apparaître dans la liste
- Une icône orange avec un "R" devrait apparaître dans la barre d'outils

### 5. Épingler l'Extension (Optionnel)

1. Clique sur l'icône **puzzle** (🧩) dans la barre d'outils
2. Trouve **"LesRats - AliExpress Importer"**
3. Clique sur l'icône **épingle** pour garder l'extension visible

## Utilisation

### Importer un Produit AliExpress

1. **Démarre ton serveur LesRats** :
   ```bash
   php artisan serve
   ```

2. **Va sur AliExpress** et ouvre une page produit
   - Ex: `https://fr.aliexpress.com/item/123456789.html`

3. **Clique sur l'icône LesRats** dans la barre d'outils

4. L'extension va automatiquement :
   - Détecter que tu es sur une page produit AliExpress
   - Extraire les données du produit (titre, prix, images...)

5. **Configure l'URL du serveur** (première fois uniquement) :
   - Par défaut: `http://localhost:8000`
   - Modifie si ton serveur tourne sur un autre port

6. **Clique sur "🚀 Importer vers LesRats"**

7. Le produit sera créé dans ta boutique avec :
   - Les images importées
   - Le prix de revient (prix AliExpress)
   - Un prix de vente calculé (×2.5)
   - Stock illimité (999 par défaut pour dropshipping)

## Dépannage

### L'extension ne détecte pas le produit

- Assure-toi d'être sur une page produit AliExpress (URL contenant `/item/`)
- Recharge la page (F5)
- Ferme et rouvre le popup de l'extension

### Erreur de connexion au serveur

- Vérifie que `php artisan serve` est en cours d'exécution
- Vérifie l'URL dans les paramètres de l'extension
- Assure-toi qu'il n'y a pas de pare-feu bloquant localhost

### Le produit existe déjà

- L'extension détecte les doublons automatiquement
- Tu seras redirigé vers le produit existant

### Erreur CORS

Si tu as des erreurs CORS, assure-toi que le serveur Laravel autorise les requêtes cross-origin. Le fichier `config/cors.php` doit permettre les requêtes depuis l'extension.

## Structure des Fichiers

```
browser-extension/
├── manifest.json          # Configuration de l'extension
├── popup/
│   ├── popup.html        # Interface utilisateur
│   ├── popup.css         # Styles
│   └── popup.js          # Logique du popup
├── content/
│   └── aliexpress.js     # Script d'extraction AliExpress
├── background/
│   └── service-worker.js # Tâches en arrière-plan
└── icons/
    ├── icon16.png
    ├── icon32.png
    ├── icon48.png
    └── icon128.png
```

## Mettre à jour l'Extension

Après avoir modifié le code de l'extension :

1. Va dans `opera://extensions`
2. Trouve l'extension **LesRats**
3. Clique sur le bouton **🔄 (Recharger)** ou appuie sur la touche de raccourci

## Désinstaller

1. Va dans `opera://extensions`
2. Trouve l'extension **LesRats**
3. Clique sur **"Supprimer"**
