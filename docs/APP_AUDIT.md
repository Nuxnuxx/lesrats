# LesRats - Application Audit & Sitemap

> **Date:** February 2026  
> **Purpose:** Technical debt audit for SaaS launch (March 2026 target)

---

## 1. Page Sitemap

```mermaid
graph TD
    subgraph Public
        WELCOME["/  Welcome Page"]
        LOGIN["/login"]
        REGISTER["/register"]
        FORGOT["/forgot-password"]
        RESET["/reset-password/:token"]
    end

    subgraph Authenticated
        DASH["/dashboard"]

        subgraph Shops
            SHOP_LIST["/shops"]
            SHOP_CREATE["/shops/create"]
            SHOP_EDIT["/shops/:id  edit"]
        end

        subgraph Products
            PROD_LIST["/products"]
            PROD_CREATE["/products/create"]
            PROD_EDIT["/products/:id  edit"]
        end

        subgraph Orders
            ORDER_LIST["/orders"]
            ORDER_SHOW["/orders/:id"]
        end

        subgraph Profile
            PROFILE["/profile"]
        end
    end

    subgraph Auth_Only["Auth Flows"]
        VERIFY["/verify-email"]
        CONFIRM_PW["/confirm-password"]
    end

    subgraph Extension_API["API /api/extension"]
        API_PING["GET /ping"]
        API_SHOPS["GET /shops"]
        API_IMPORT["POST /import"]
        API_ETSY["GET /product/:id/etsy-data"]
    end

    %% Public flows
    WELCOME -->|authenticated| DASH
    WELCOME -->|"Log in"| LOGIN
    WELCOME -->|"Register"| REGISTER
    LOGIN -->|success| DASH
    REGISTER -->|success| DASH
    LOGIN -->|"Forgot?"| FORGOT
    FORGOT -->|email sent| RESET
    RESET -->|success| LOGIN

    %% Main navigation (persistent nav bar)
    DASH ---|nav| SHOP_LIST
    DASH ---|nav| PROD_LIST
    DASH ---|nav| ORDER_LIST
    DASH ---|nav| PROFILE

    %% Dashboard links
    DASH -->|"shop card"| SHOP_SHOW
    DASH -->|"+ Add shop"| SHOP_CREATE
    DASH -->|"product attention"| PROD_EDIT

    %% Shop flows
    SHOP_LIST -->|"Gerer"| SHOP_EDIT
    SHOP_CREATE -->|submit| SHOP_EDIT
    SHOP_CREATE -->|cancel| SHOP_LIST
    SHOP_EDIT -->|submit| SHOP_EDIT
    SHOP_EDIT -->|back| DASH

    %% Product flows
    PROD_LIST -->|"New"| PROD_CREATE
    PROD_LIST -->|"card click"| PROD_EDIT
    PROD_CREATE -->|submit| PROD_EDIT
    PROD_CREATE -->|back| PROD_LIST
    PROD_EDIT -->|submit| PROD_EDIT
    PROD_EDIT -->|back| PROD_LIST
    PROD_EDIT -->|"shop backgrounds"| SHOP_EDIT

    %% Order flows
    ORDER_LIST -->|"View"| ORDER_SHOW
    ORDER_SHOW -->|back| ORDER_LIST

    %% Extension API
    API_IMPORT -.->|creates| PROD_EDIT
    API_ETSY -.->|reads| PROD_EDIT
```

---

## 2. Features Per Page

### `/dashboard` - Main Dashboard

| Feature | Status | Notes |
|---------|--------|-------|
| Aggregate stats (products, orders, revenue, profit) | OK | |
| Today's stats (orders, revenue) | OK | |
| Per-shop summary cards | OK | Links to shop detail |
| Products needing attention (low/out of stock) | OK | Excludes digital |
| Orders needing supplier action | OK | AliExpress items with "new" status |
| 30-day revenue chart | OK | ApexCharts |
| Quick shop creation CTA | OK | |

### `/shops` - Shop List

| Feature | Status | Notes |
|---------|--------|-------|
| List all user's shops | OK | |
| Shop stats (products, orders, revenue) | OK | |
| Active shop indicator | OK | |
| Switch active shop | OK | POST form |
| Create / View / Edit / Delete actions | OK | Delete requires owner role |
| Empty state with CTA | OK | |

### `/shops/create` - Create Shop

| Feature | Status | Notes |
|---------|--------|-------|
| Name, description, currency inputs | OK | |
| Logo upload | OK | |
| Form validation | OK | |

### `/shops/:id` - Shop Settings

| Feature | Status | Notes |
|---------|--------|-------|
| Basic info (name, description, currency) | OK | |
| Logo upload/change | OK | |
| AI description prompt | OK | Custom system prompt |
| AI title prompt | OK | |
| AI image prompt | OK | |
| AI specific prompts (named presets) | OK | Add/delete |
| AI image generation toggle | OK | |
| AI background images | OK | Upload/delete named backgrounds |
| Etsy categories management | OK | JSON: name, etsy_name, etsy_id, keywords |
| Available tags pool | OK | Comma-separated input |
| Default margins (US / Other) | OK | |
| Discount percentage | OK | |
| Delete shop | OK | Owner only |

### `/products` - Product List

| Feature | Status | Notes |
|---------|--------|-------|
| Paginated grid (24/page) | OK | Card layout |
| Filter by shop | OK | Dropdown |
| Filter by source type | OK | aliexpress/printables/manual |
| Filter by active status | OK | |
| Search by title | OK | |
| Bulk select + delete | OK | Checkbox + JSON API |
| Product cards with hover actions | OK | Edit, source link |
| Source badge (AliExpress/Printables/Manual) | OK | |
| Stock indicator | OK | |
| Empty state | OK | |

### `/products/create` - Create Product

| Feature | Status | Notes |
|---------|--------|-------|
| AliExpress URL import (scrape + AI optimize) | OK | |
| Printables URL import (scrape + AI optimize) | OK | |
| Manual product creation | OK | |
| Real-time URL analysis (AJAX) | OK | |
| AI title/description/tags generation | OK | |
| Category auto-selection from shop pool | OK | |
| Tag selection from shop pool | OK | |
| Preview scraped data before saving | OK | |
| Country-specific pricing from extension | OK | Via API import |
| Error handling (CAPTCHA, invalid URL) | OK | |

### `/products/:id` - Product Edit

| Feature | Status | Notes |
|---------|--------|-------|
| Title, description, tags editing | OK | |
| Copy to clipboard (title, desc, tags) | OK | |
| Etsy category selection | OK | From shop's categories |
| Price US / Price Other with margin calc | OK | Real-time recalculation |
| Cost price | OK | |
| Country prices display | OK | Read-only, set during import |
| Stock quantity + threshold | OK | |
| Digital product flag | OK | |
| Active toggle | OK | |
| Sizes management | OK | Alpine.js add/remove UI |
| Source URL / AliExpress URL | OK | |
| Source images management | OK | View, remove individual |
| AI image generation (batch) | OK | Up to 5 images |
| AI single image transform | OK | Custom prompt + background |
| Background selection for AI | OK | From shop backgrounds |
| Specific prompt selection for AI | OK | From shop presets |
| Logo overlay toggle | OK | |
| Real images management | OK | View, remove individual |
| Download images as ZIP | OK | |
| Open Etsy listing editor | OK | Opens external page |
| AI content re-optimization | OK | Re-run title/desc/tags |
| Delete product | OK | |

### `/orders` - Order List

| Feature | Status | Notes |
|---------|--------|-------|
| Paginated list (20/page) | OK | Table layout |
| Filter by shop | OK | Dropdown |
| Filter by status | OK | |
| Filter by date (today/week/month) | OK | |
| Search (name, email, order number) | OK | |
| Status badges with colors | OK | |
| Order stats (total, new, in-progress) | OK | |
| Today's revenue/profit summary | OK | |
| Empty state | OK | |

### `/orders/:id` - Order Detail

| Feature | Status | Notes |
|---------|--------|-------|
| Order info (number, date, customer) | OK | |
| Customer email (mailto link) | OK | |
| Shipping address (formatted) | OK | |
| Order items list | OK | With source info |
| AliExpress ordering link per item | OK | External |
| Status timeline (new->ordered->shipped->delivered->completed) | OK | With timestamps |
| Status update buttons | OK | Sequential workflow |
| Add notes | OK | Free text |
| Financial summary (total, cost, profit, margin) | OK | |

### `/profile` - User Profile

| Feature | Status | Notes |
|---------|--------|-------|
| Update name/email | OK | |
| Update password | OK | |
| API keys (Fal.ai) | OK | Encrypted storage |
| Extension tokens (Sanctum) | OK | Create, view, revoke |
| Delete account | OK | Password confirmation |

### Chrome Extension - Popup

| Feature | Status | Notes |
|---------|--------|-------|
| AliExpress tab - auto-detect product page | OK | |
| AliExpress tab - extract product data | OK | JSON + DOM fallback |
| AliExpress tab - country price scraping | OK | 6 countries |
| AliExpress tab - size/variant extraction | OK | |
| AliExpress tab - one-click import | OK | |
| AliExpress tab - keyboard shortcut (Alt+Shift+I) | OK | |
| Printables tab - auto-detect model page | OK | |
| Printables tab - extract product data | OK | __NEXT_DATA__ + DOM |
| Printables tab - license checking | OK | |
| Etsy tab - open listing editor | OK | |
| Etsy tab - category auto-selection | OK | |
| Etsy tab - form auto-fill | OK | Title, desc, price, tags, qty |
| Etsy tab - size variations | OK | |
| Etsy tab - image download + upload helper | OK | |
| Settings - API URL/token config | OK | |
| Settings - dev mode toggle | OK | |
| Settings - connection test | OK | Ping + shops |
| Debug panels | OK | |

---

## 3. Navigation Flow Diagram

```mermaid
graph LR
    subgraph NavBar["Persistent Navigation Bar"]
        N_DASH[Dashboard]
        N_SHOPS[Boutiques]
        N_PRODS[Produits]
        N_ORDERS[Commandes]
        N_PROFILE[Profile]
        N_LOGOUT[Logout]
    end

    subgraph ShopFlow["Shop Flow"]
        S1[Shop List] -->|Gerer| S4[Shop Edit]
        S1 -->|Create| S2[Shop Create]
        S2 -->|submit| S4
        S4 -->|back| DASH2[Dashboard]
    end

    subgraph ProductFlow["Product Flow"]
        P1[Product List] -->|New| P2[Product Create]
        P1 -->|Click card| P4[Product Edit]
        P2 -->|submit| P4
        P4 -->|back| P1
        P4 -->|backgrounds| S4
    end

    subgraph OrderFlow["Order Flow"]
        O1[Order List] -->|View| O2[Order Detail]
        O2 -->|back| O1
    end

    subgraph ExtFlow["Extension Flow"]
        EXT_ALI[AliExpress Page] -->|Import| API[API /import]
        EXT_PRI[Printables Page] -->|Import| API
        API -->|creates| P4
        EXT_ETSY[Etsy Editor] -->|reads| API2[API /etsy-data]
    end
```

---

## 4. Technical Debt & Issues

### CRITICAL (blocks SaaS launch)

| # | Issue | Location | Impact |
|---|-------|----------|--------|
| **D1** | **No order creation flow** | `OrderController` has no `create`/`store` | Orders can only be created via... what? There's no order creation UI and no API endpoint for it. The demo seeder creates them, but real users can't. This is a fundamental gap. |
| **D2** | **No Etsy webhook/sync** | Missing entirely | Orders arrive on Etsy but there's no way to get them into LesRats. No Etsy API integration, no CSV import, no manual creation form. The entire order management feature is unusable without this. |
| **D3** | **Welcome page is Laravel boilerplate** | `welcome.blade.php` | For a SaaS, the landing page IS the product. Currently shows Laravel docs links. |

### HIGH (significant debt)

| # | Issue | Location | Impact |
|---|-------|----------|--------|
| **D4** | **Dead code: ShopMembership roles** | `ShopMembership`, `ShopPolicy` | `owner`/`admin` roles exist but there's no UI to invite users, manage members, or assign roles. The policy checks run but serve a single user. Either implement multi-user or simplify to single-owner. |
| **D5** | **No error monitoring** | Missing | No Sentry, Bugsnag, or similar. A SaaS needs production error tracking. |
| **D6** | **SQLite in production** | `database.sqlite` | Fine for development. Will not scale for a multi-user SaaS. Need PostgreSQL or MySQL migration plan. |
| **D7** | **No rate limiting on scraping endpoints** | `ProductController` | `analyze-aliexpress` and `analyze-printables` call external APIs (Firecrawl) with no rate limiting. A user could exhaust your API quota. |
| **D8** | **No queue for AI operations** | `ProductController` | AI image generation and content optimization run synchronously in HTTP requests. With Fal.ai + upscaling, this can take 30+ seconds. Should be queued jobs. |
| **D9** | **API tokens shown only once** | `ProfileController@createToken` | Sanctum plain-text token flashed to session. If user misses it, they must create a new one. Standard practice but worth noting. |

### MEDIUM (should fix)

| # | Issue | Location | Impact |
|---|-------|----------|--------|
| **D10** | **No pagination on shop detail products** | N/A (shop show page removed) | Previously showed "recent products" without pagination. Consider adding product summary to shop edit page. |
| **D11** | **Hardcoded pricing formula** | `AliExpressScraperService` | 3x markup with psychological pricing is hardcoded. Should be configurable per shop (partially addressed by margins, but initial import price is fixed). |
| **D12** | **No image cleanup** | Product deletion | When products are deleted, generated images in `public/products/` are not cleaned up. Storage will grow indefinitely. |
| **D13** | **Country prices are read-only after import** | `products/edit.blade.php` | Country-specific prices set during extension import cannot be edited in the web UI. |
| **D14** | **No test coverage for business logic** | `tests/` | Only Breeze auth tests + 1 shop isolation test. No tests for scrapers, AI services, order workflow, product CRUD. |
| **D15** | **Mixed language UI** | Various views | Mix of French ("Boutiques", "Produits", "Commandes") and English. For a SaaS, need to pick one or implement i18n. |
| **D16** | **No CSRF on API routes** | `api.php` | API routes use Sanctum tokens (correct), but CORS config should be reviewed for production. |
| **D17** | **`storage.serve` route serves any file** | `web.php` | Custom storage serving route may bypass access controls. Should scope to specific directories. |
| **D18** | **No email sending in practice** | Config exists | Postmark/Resend configured but no transactional emails beyond auth (no order confirmations, no alerts). |

### LOW (nice to fix)

| # | Issue | Location | Impact |
|---|-------|----------|--------|
| **D19** | **No loading states for AI operations** | `products/edit.blade.php` | AI generation buttons don't show loading spinners during the (long) AJAX calls. |
| **D20** | **No undo for bulk delete** | `products/index.blade.php` | Bulk delete is immediate with only a confirm modal. No soft delete or undo. |
| **D21** | **Console Commands directory empty** | `app/Console/Commands/` | No artisan commands for maintenance tasks (cleanup images, recalculate stats, etc.) |
| **D22** | **No product duplication** | `ProductController` | No way to duplicate a product (common need when creating variants). |
| **D23** | **No export functionality** | Missing | No CSV/Excel export for products or orders. Essential for accounting/reporting. |

---

## 5. Data Flow Diagram

```mermaid
flowchart TD
    subgraph User["User / Browser"]
        WEB[Web App - Blade/Alpine]
        EXT[Chrome Extension]
    end

    subgraph Laravel["Laravel Backend"]
        CTRL[Controllers]
        SCRAPE[Scraper Services]
        AI_CONTENT[Content Optimizer - Groq]
        AI_IMAGE[Image Generator - Fal.ai]
        MODELS[Eloquent Models]
    end

    subgraph External["External Services"]
        FIRECRAWL[Firecrawl API]
        GROQ[Groq API - Llama 3.3]
        FAL[Fal.ai - Gemini + ESRGAN]
        ALIEXPRESS[AliExpress.com]
        PRINTABLES[Printables.com]
        ETSY[Etsy.com]
    end

    subgraph Storage
        SQLITE[(SQLite DB)]
        DISK[Local Disk - images]
    end

    WEB -->|HTTP requests| CTRL
    EXT -->|Sanctum API| CTRL
    EXT -->|content scripts| ALIEXPRESS
    EXT -->|content scripts| PRINTABLES
    EXT -->|form automation| ETSY

    CTRL --> SCRAPE
    CTRL --> AI_CONTENT
    CTRL --> AI_IMAGE
    CTRL --> MODELS

    SCRAPE -->|scrape URL| FIRECRAWL
    FIRECRAWL -->|fetches| ALIEXPRESS
    FIRECRAWL -->|fetches| PRINTABLES

    AI_CONTENT -->|optimize text| GROQ
    AI_IMAGE -->|generate images| FAL
    AI_IMAGE -->|upscale| FAL

    MODELS -->|read/write| SQLITE
    AI_IMAGE -->|save images| DISK
    CTRL -->|serve images| DISK
```

---

## 6. Missing Features for SaaS Launch

Based on the audit, here's what's missing for a viable SaaS:

### Must Have (March 2026)

| # | Feature | Why |
|---|---------|-----|
| **M1** | Order creation (manual or import) | Core feature is broken - orders exist in DB but can't be created by users |
| **M2** | Etsy order sync (CSV import at minimum) | Without this, order management is useless |
| **M3** | Landing page | Can't launch a SaaS with Laravel boilerplate welcome page |
| **M4** | Billing/subscription system (Stripe) | SaaS needs payment |
| **M5** | Background job processing for AI | Current sync approach will timeout and block users |
| **M6** | PostgreSQL/MySQL migration | SQLite won't handle concurrent SaaS users |

### Should Have (Q2 2026)

| # | Feature | Why |
|---|---------|-----|
| **S1** | Error monitoring (Sentry) | Can't debug production issues without it |
| **S2** | Test suite for business logic | Can't ship confidently without tests |
| **S3** | CSV/Excel export | Sellers need this for accounting |
| **S4** | Email notifications (order alerts, etc.) | Expected SaaS feature |
| **S5** | Rate limiting on API/scraping | Protect your external API quotas |
| **S6** | i18n (pick EN or FR, implement properly) | Mixed language is unprofessional |

### Could Have (Q3 2026)

| # | Feature | Why |
|---|---------|-----|
| **C1** | Multi-user collaboration | Grow into team plans |
| **C2** | Direct Etsy API integration | Remove extension dependency (if needed) |
| **C3** | Product duplication | Power user feature |
| **C4** | Analytics dashboard (per product ROI) | Value-add for sellers |
| **C5** | Automated image cleanup | Prevent storage bloat |

---

## 7. Database Entity Relationship

```mermaid
erDiagram
    User ||--o{ ShopMembership : "has many"
    Shop ||--o{ ShopMembership : "has many"
    Shop ||--o{ Product : "has many"
    Shop ||--o{ Order : "has many"
    Order ||--o{ OrderItem : "has many"
    Product ||--o{ OrderItem : "referenced by"
    User ||--o{ PersonalAccessToken : "has many"

    User {
        int id PK
        string name
        string email
        string password
        string fal_api_key
    }

    Shop {
        int id PK
        string name
        string description
        string logo_path
        string currency
        json etsy_categories
        json available_tags
        json ai_backgrounds
        json ai_specific_prompts
        text ai_description_prompt
        text ai_title_prompt
        text ai_image_prompt
        boolean ai_image_enabled
        decimal default_margin_us
        decimal default_margin_other
        decimal discount_percentage
        decimal total_revenue
        int total_orders
        boolean is_active
    }

    ShopMembership {
        int id PK
        int user_id FK
        int shop_id FK
        string role
    }

    Product {
        int id PK
        int shop_id FK
        string title
        text description
        json tags
        json sizes
        string etsy_category
        decimal price
        decimal cost_price
        decimal price_us
        decimal price_other
        decimal margin_us
        decimal margin_other
        json country_prices
        json images
        json real_images
        string source_type
        string source_url
        string aliexpress_url
        string aliexpress_product_id
        int quantity
        int low_stock_threshold
        boolean is_digital
        boolean is_active
        boolean apply_logo
    }

    Order {
        int id PK
        int shop_id FK
        string order_number
        string customer_name
        string customer_email
        decimal total_price
        decimal total_cost
        decimal total_profit
        string currency
        string status
        json shipping_address
        text notes
        datetime ordered_at
        datetime shipped_at
        datetime delivered_at
        datetime completed_at
    }

    OrderItem {
        int id PK
        int order_id FK
        int product_id FK
        string title
        int quantity
        decimal price
        decimal cost
        decimal profit
        string source_type
        string source_url
        boolean is_digital
    }
```
