# LesRats - Etsy Shop Management

A Laravel application for managing Etsy shops with dropshipping (AliExpress) and digital products (Printables STL files).

## Features

- **Multi-shop management** - Connect and manage multiple Etsy shops
- **Product import** - Import from AliExpress or Printables with AI optimization
- **Order tracking** - Track orders with status workflow (new → ordered → shipped → delivered)
- **Profit tracking** - Automatic profit/margin calculations per product and order
- **Dashboard** - Overview of all shops with stats and revenue charts

## Tech Stack

- **Backend:** Laravel 12, PHP 8.3+
- **Frontend:** Blade, Tailwind CSS, Alpine.js
- **Database:** SQLite
- **Charts:** ApexCharts
- **AI:** OpenAI API (optional, for content optimization)
- **Scraping:** Firecrawl API (for product import)

## Installation

```bash
# Clone the repository
git clone https://github.com/yourusername/lesrats.git
cd lesrats

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations and seed demo data
php artisan migrate
php artisan db:seed --class=DemoDataSeeder

# Build assets
npm run build

# Start the server
php artisan serve
```

## Demo Login

```
Email: demo@lesrats.fr
Password: password
```

## Testing with Mock Data

**Prompt Claude:** `Lis docs/TESTING.md et setup le projet pour que je puisse tester`

Voir [docs/TESTING.md](docs/TESTING.md) pour la documentation complete.

## Configuration

### Required

```env
APP_URL=http://localhost:8000
```

### OpenAI (optional, for AI content optimization)

```env
OPENAI_API_KEY=sk-your-api-key
```

### Firecrawl (optional, for AliExpress/Printables scraping)

```env
FIRECRAWL_API_KEY=your-firecrawl-key
FIRECRAWL_BASE_URL=https://api.firecrawl.dev/v1
```

## Project Structure

```
app/
├── Http/Controllers/
│   ├── DashboardController.php
│   ├── ShopController.php
│   ├── ProductController.php
│   └── OrderController.php
├── Models/
│   ├── Shop.php
│   ├── Product.php
│   ├── Order.php
│   └── OrderItem.php
└── Services/
    ├── AliExpressScraperService.php
    ├── PrintablesScraperService.php
    └── ContentOptimizerService.php

resources/views/
├── dashboard.blade.php
├── shops/
├── products/
├── orders/
└── components/
    └── ui/           # Reusable UI components
```

## Browser Extension

For reliable AliExpress product import, a Chrome extension is available.
See [docs/BROWSER_EXTENSION.md](docs/BROWSER_EXTENSION.md) for setup instructions.

## Development

```bash
# Run dev server with hot reload
npm run dev
php artisan serve

# Clear caches
php artisan view:clear
php artisan cache:clear

# Run tests
php artisan test
```

## License

Proprietary - All rights reserved
