# Testing with Mock Data

Pour tester l'application sans configurer les APIs externes (Etsy, OpenAI, Firecrawl), utilisez le mode mock.

## Setup rapide

```bash
cp .env.example .env && php artisan key:generate && sed -i 's/ETSY_MOCK_ENABLED=false/ETSY_MOCK_ENABLED=true/' .env && php artisan migrate:fresh && php artisan db:seed --class=DemoDataSeeder && php artisan serve
```

Puis ouvrir http://localhost:8000 avec `demo@lesrats.fr` / `password`

---

## Setup detaille

### 1. Activer le mode mock Etsy

Dans `.env`:
```env
ETSY_MOCK_ENABLED=true
```

Cela permet de:
- Simuler la connexion OAuth Etsy sans vraies credentials
- Tester le flow de connexion boutique
- Importer des produits/commandes fictifs

### 2. Charger les donnees de demo

```bash
php artisan migrate:fresh
php artisan db:seed --class=DemoDataSeeder
```

Cela cree:
- 1 utilisateur demo (`demo@lesrats.fr` / `password`)
- 3 boutiques avec des donnees differentes
- ~36 produits (mix AliExpress dropship + Printables digital)
- ~69 commandes avec differents statuts
- Donnees de profit/marge calculees

### 3. Lancer les serveurs

```bash
# Terminal 1 - Backend
php artisan serve

# Terminal 2 - Frontend (hot reload)
npm run dev
```

---

## Comportement sans APIs externes

| Fonctionnalite | Sans API | Comportement |
|----------------|----------|--------------|
| Connexion Etsy | `ETSY_MOCK_ENABLED=true` | Simule OAuth, cree faux tokens |
| Import AliExpress | Aucune config | Retourne erreur, fallback manuel |
| Import Printables | Aucune config | Retourne erreur, fallback manuel |
| Optimisation IA | Sans `GROQ_API_KEY` | Utilise regles basiques (pas d'IA) |

---

## Ce que vous pouvez tester

### Dashboard (`/dashboard`)
- Stats agregees de toutes les boutiques
- Graphiques revenus
- Commandes du jour
- Produits necessitant attention

### Boutiques (`/shops`)
- Liste des boutiques
- Detail avec stats et graphique revenus
- Connexion Etsy (mockee)
- Edition parametres

### Produits (`/products`)
- Liste avec filtres (source, sync status)
- Creation manuelle
- Import wizard (scraping echouera, mais fallback manuel fonctionne)
- Edition avec calcul profit/marge
- Suppression

### Commandes (`/orders`)
- Liste avec filtres (status, date)
- Detail avec workflow statut
- Bouton "Commander sur AliExpress" (lien externe)
- Notes internes
- Calcul profit par commande

---

## Donnees de demo

Le seeder cree 3 boutiques avec des profils differents:

| Boutique | Type | Produits | Commandes |
|----------|------|----------|-----------|
| RatCraft Creations | Mix dropship + digital | ~15 | ~25 |
| PrintRat 3D | Digital (STL) | ~12 | ~22 |
| RatDrop Express | Dropship AliExpress | ~9 | ~22 |

Les commandes ont des statuts varies: `new`, `ordered`, `shipped`, `delivered`, `completed`.

---

## Troubleshooting

### "SQLSTATE: no such table"
```bash
php artisan migrate:fresh
```

### Assets non charges (styles manquants)
```bash
npm run build
# ou pour dev avec hot reload:
npm run dev
```

### Erreur 500
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Verifier les logs
```bash
tail -f storage/logs/laravel.log
```
