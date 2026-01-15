# LA MAGIE - Plateforme Dropshipping Etsy/AliExpress

## 🎉 Phase 1 : Foundation & Multi-Tenancy - TERMINÉE ✅

**Date de complétion:** 15 janvier 2026
**Stack:** Laravel 12.47, PHP 8.3, MySQL 8.4, Tailwind CSS, Laravel Breeze

---

## 📋 Résumé de la Phase 1

Cette phase établit les fondations solides de l'application avec une architecture multi-tenancy complète permettant à plusieurs utilisateurs de gérer plusieurs boutiques Etsy en toute sécurité.

### ✅ Accomplissements (12/12 tâches)

1. ✅ Projet Laravel 12 créé et configuré
2. ✅ Base de données MySQL configurée (`la_magie_db`)
3. ✅ Environnement de développement opérationnel
4. ✅ Dépendances installées (Composer + NPM)
5. ✅ Architecture multi-tenancy implémentée
6. ✅ Models et migrations créés (User, Shop, ShopMembership)
7. ✅ Relations Eloquent configurées
8. ✅ Système d'authentification (Laravel Breeze)
9. ✅ Policies d'autorisation (ShopPolicy)
10. ✅ Middleware de contexte boutique (SetActiveShop)
11. ✅ Tests d'isolation passés (6 tests, 11 assertions)
12. ✅ Interface CRUD boutiques fonctionnelle

---

## 🏗️ Architecture Technique

### Base de Données

#### **Table: `users`**
Utilisateurs de l'application (fournie par Laravel Breeze)

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| name | string | Nom de l'utilisateur |
| email | string | Email unique |
| password | string | Mot de passe hashé |
| created_at | timestamp | Date de création |
| updated_at | timestamp | Date de modification |

#### **Table: `shops`**
Boutiques Etsy (gérées par les utilisateurs)

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| name | string | Nom de la boutique |
| etsy_shop_id | string | ID Etsy unique (nullable) |
| etsy_access_token | text | Token OAuth Etsy (nullable, hidden) |
| etsy_refresh_token | text | Refresh token Etsy (nullable, hidden) |
| etsy_token_expires_at | timestamp | Expiration token (nullable) |
| currency | string(3) | Devise (EUR, USD, GBP, CAD) |
| is_active | boolean | Statut actif/inactif |
| created_at | timestamp | Date de création |
| updated_at | timestamp | Date de modification |

#### **Table: `shop_memberships`**
Relation many-to-many entre users et shops avec rôles

| Colonne | Type | Description |
|---------|------|-------------|
| id | bigint | Clé primaire |
| user_id | bigint | FK vers users (cascade) |
| shop_id | bigint | FK vers shops (cascade) |
| role | enum | owner, admin, member |
| created_at | timestamp | Date d'ajout |
| updated_at | timestamp | Date de modification |

**Contrainte:** Unique sur (user_id, shop_id)

---

### Models Eloquent

#### **User Model**
```php
// Relations
$user->shops() // Toutes les boutiques de l'utilisateur
$user->ownedShops() // Boutiques où l'utilisateur est owner

// Méthodes
$user->hasAccessToShop(Shop $shop) // Vérifie l'accès à une boutique
```

#### **Shop Model**
```php
// Relations
$shop->users() // Tous les utilisateurs de la boutique
$shop->members() // Détails des membres avec rôles

// Attributs cachés
- etsy_access_token
- etsy_refresh_token
```

#### **ShopMembership Model**
```php
// Relations
$membership->user() // L'utilisateur
$membership->shop() // La boutique
```

---

### Système de Permissions

#### **Rôles disponibles:**

| Rôle | Permissions |
|------|-------------|
| **owner** | Tout (modifier, supprimer, gérer membres) |
| **admin** | Modifier la boutique, gérer membres |
| **member** | Accès lecture seule |

#### **ShopPolicy (app/Policies/ShopPolicy.php)**

| Méthode | Description | Autorisé pour |
|---------|-------------|---------------|
| `viewAny` | Voir toutes ses boutiques | Tous |
| `view` | Voir une boutique | Membres de la boutique |
| `create` | Créer une boutique | Tous les users |
| `update` | Modifier une boutique | Owner, Admin |
| `delete` | Supprimer une boutique | Owner uniquement |
| `manageMembers` | Gérer les membres | Owner, Admin |

---

### Middleware

#### **SetActiveShop** (`app/Http/Middleware/SetActiveShop.php`)

**Fonction:** Définit automatiquement la boutique active pour l'utilisateur connecté

**Logique:**
1. Récupère `shop_id` depuis la session ou request
2. Si aucune boutique active, sélectionne la première disponible
3. Vérifie que l'utilisateur a accès à la boutique
4. Partage `$activeShop` avec toutes les vues
5. Stocke la boutique dans les attributs de la requête

**Enregistré dans:** `bootstrap/app.php` (web middleware group)

---

## 🧪 Tests

### **ShopIsolationTest** (`tests/Feature/ShopIsolationTest.php`)

6 tests passés avec succès :

```bash
✓ test_user_can_access_own_shop
✓ test_user_cannot_access_other_users_shop
✓ test_user_can_view_authorized_shop
✓ test_user_cannot_view_unauthorized_shop
✓ test_only_owner_can_delete_shop
✓ test_owner_and_admin_can_update_shop
```

**Commande:** `php artisan test --filter=ShopIsolationTest`

---

## 🌐 Interface Utilisateur

### Routes disponibles

| Route | Méthode | Action | Permission |
|-------|---------|--------|------------|
| `/shops` | GET | Liste des boutiques | Auth |
| `/shops/create` | GET | Formulaire création | Auth |
| `/shops` | POST | Créer boutique | Auth |
| `/shops/{shop}` | GET | Détails boutique | view |
| `/shops/{shop}/edit` | GET | Formulaire édition | update |
| `/shops/{shop}` | PATCH | Modifier boutique | update |
| `/shops/{shop}` | DELETE | Supprimer boutique | delete |
| `/shops/{shop}/switch` | POST | Changer boutique active | view |

### Vues créées

- `resources/views/shops/index.blade.php` - Liste des boutiques
- `resources/views/shops/create.blade.php` - Créer une boutique
- `resources/views/shops/show.blade.php` - Détails boutique

### Controller

- `app/Http/Controllers/ShopController.php` - Gestion CRUD + switch

---

## 🚀 Installation & Lancement

### Prérequis

- PHP 8.3+
- Composer 2.8+
- MySQL 8.0+
- Node.js 20+
- NPM 10+

### Configuration

1. **Cloner le projet:**
```bash
git clone https://github.com/Nuxnuxx/lesrats.git
cd lesrats
```

2. **Installer les dépendances:**
```bash
composer install
npm install
```

3. **Configuration environnement:**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configurer .env:**
```env
APP_NAME="LA MAGIE - Etsy Dropshipping"
APP_LOCALE=fr

DB_CONNECTION=mysql
DB_DATABASE=la_magie_db
DB_USERNAME=root
DB_PASSWORD=
```

5. **Créer la base de données:**
```bash
mysql -u root -e "CREATE DATABASE la_magie_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

6. **Exécuter les migrations:**
```bash
php artisan migrate
```

7. **Compiler les assets:**
```bash
npm run build
```

8. **Lancer le serveur:**
```bash
php artisan serve
```

### Accès à l'application

**URL:** http://127.0.0.1:8000

**Pages principales:**
- `/register` - Inscription
- `/login` - Connexion
- `/dashboard` - Tableau de bord
- `/shops` - Gestion boutiques

---

## 📁 Structure du Projet

```
LA_MAGIE1/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── ShopController.php
│   │   └── Middleware/
│   │       └── SetActiveShop.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Shop.php
│   │   └── ShopMembership.php
│   └── Policies/
│       └── ShopPolicy.php
├── database/
│   └── migrations/
│       ├── 2026_01_15_144204_create_shops_table.php
│       └── 2026_01_15_144209_create_shop_memberships_table.php
├── resources/
│   └── views/
│       ├── shops/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   └── show.blade.php
│       └── layouts/
│           └── navigation.blade.php
├── routes/
│   └── web.php
├── tests/
│   └── Feature/
│       └── ShopIsolationTest.php
├── .env
├── composer.json
├── package.json
├── README.md
├── TODOLIST.md
└── README_PHASE1.md (ce fichier)
```

---

## 🔐 Sécurité

### Mesures implémentées

✅ **Isolation des données:**
- Policies empêchent l'accès aux boutiques non autorisées
- Middleware vérifie les permissions à chaque requête
- Tests garantissent l'isolation complète

✅ **Protection des tokens:**
- Tokens Etsy stockés cryptés en base
- Attributs `hidden` empêchent exposition dans JSON
- Pas de tokens en logs

✅ **Validation:**
- Validation stricte des inputs (FormRequest)
- Protection CSRF (Laravel par défaut)
- Sanitization automatique

✅ **Authentification:**
- Laravel Breeze (sécurisé par défaut)
- Passwords hashés avec bcrypt
- Sessions sécurisées

---

## 📊 Statistiques Phase 1

- **Lignes de code:** ~1500
- **Fichiers créés:** 15
- **Tests:** 6 passés
- **Migrations:** 5 tables
- **Temps de développement:** ~3 heures
- **Commits Git:** 2

---

## 🎯 Prochaines Étapes - Phase 2

### Intégration Etsy API (4 semaines estimées)

1. **OAuth 2.0 Etsy**
   - Flow d'authentification
   - Gestion tokens et refresh
   - Connexion boutiques Etsy

2. **Service EtsyApiClient**
   - Client HTTP avec Guzzle
   - Rate limiting (10 req/sec)
   - Error handling et retry logic

3. **CRUD Listings Etsy**
   - Import listings existants
   - Création nouveaux produits
   - Upload images
   - Gestion tags SEO et variations

4. **Webhooks Etsy**
   - Réception événements (commandes, messages)
   - Queue jobs asynchrones
   - Notifications temps réel

---

## 🐛 Problèmes Connus

Aucun problème connu pour le moment. Tous les tests passent.

---

## 📝 Notes Techniques

### Conventions de code

- **PSR-12** pour le code PHP
- **Camel case** pour variables/méthodes
- **Pascal case** pour classes
- **Snake case** pour noms de tables/colonnes
- **Français** pour les messages utilisateurs
- **Anglais** pour le code et commentaires

### Performance

- **Eager loading** utilisé pour éviter N+1 queries
- **Index** sur colonnes de recherche (email, etsy_shop_id)
- **Cache** prévu pour Phase 5

---

## 🤝 Contribution

Projet personnel en développement.

---

## 📄 Licence

Propriétaire - Tous droits réservés

---

**Développé avec ❤️ par TheLayns**
**Assisté par Claude Sonnet 4.5 (Anthropic)**

---

## 📞 Contact

- **GitHub:** https://github.com/Nuxnuxx
- **Email:** laywens.feriaux@gmail.com
- **Repository:** https://github.com/Nuxnuxx/lesrats

---

**Dernière mise à jour:** 15 janvier 2026
