# LesRats Browser Extension - Technical Specification

## Overview

Build a Chrome extension that extracts product data from AliExpress pages and sends it to the LesRats app for import.

**Why?** AliExpress blocks automated scrapers with CAPTCHA. A browser extension runs in the user's authenticated session, bypassing all anti-bot protection with 100% reliability.

---

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      USER'S BROWSER                         │
│  ┌─────────────────┐    ┌─────────────────────────────────┐ │
│  │ AliExpress Page │───▶│ Content Script (extract data)   │ │
│  └─────────────────┘    └──────────────┬──────────────────┘ │
│                                        │                    │
│  ┌─────────────────┐    ┌──────────────▼──────────────────┐ │
│  │ Extension Popup │◀──▶│ Background Service Worker       │ │
│  │ "Send to LesRats"│    │ (manages state, API calls)     │ │
│  └─────────────────┘    └──────────────┬──────────────────┘ │
└─────────────────────────────────────────┼───────────────────┘
                                          │ HTTPS POST
                                          ▼
┌─────────────────────────────────────────────────────────────┐
│                      LESRATS SERVER                         │
│  POST /api/extension/import                                 │
│  - Validates token                                          │
│  - Stores pending import in session/cache                   │
│  - Returns success                                          │
│                                                             │
│  GET /products/create?import=abc123                         │
│  - Loads pending import data into wizard                    │
└─────────────────────────────────────────────────────────────┘
```

---

## File Structure

```
browser-extension/
├── manifest.json              # Extension config (Manifest V3)
├── popup/
│   ├── popup.html             # UI when clicking extension icon
│   ├── popup.css              # Styles
│   └── popup.js               # Popup logic
├── content/
│   └── aliexpress.js          # Injected into AliExpress pages
├── background/
│   └── service-worker.js      # Background tasks, API calls
├── icons/
│   ├── icon-16.png
│   ├── icon-48.png
│   └── icon-128.png
└── utils/
    └── api.js                 # API helper functions (optional)
```

---

## 1. Manifest File

`manifest.json` - Use Manifest V3 (required for Chrome):

```json
{
  "manifest_version": 3,
  "name": "LesRats - AliExpress Importer",
  "version": "1.0.0",
  "description": "Import AliExpress products to LesRats for Etsy selling",
  
  "permissions": [
    "activeTab",
    "storage"
  ],
  
  "host_permissions": [
    "https://*.aliexpress.com/*",
    "https://*.aliexpress.us/*",
    "https://lesrats.fr/*",
    "http://localhost:8000/*"
  ],
  
  "background": {
    "service_worker": "background/service-worker.js"
  },
  
  "content_scripts": [
    {
      "matches": [
        "https://*.aliexpress.com/item/*",
        "https://*.aliexpress.us/item/*"
      ],
      "js": ["content/aliexpress.js"],
      "run_at": "document_idle"
    }
  ],
  
  "action": {
    "default_popup": "popup/popup.html",
    "default_icon": {
      "16": "icons/icon-16.png",
      "48": "icons/icon-48.png",
      "128": "icons/icon-128.png"
    }
  },
  
  "icons": {
    "16": "icons/icon-16.png",
    "48": "icons/icon-48.png",
    "128": "icons/icon-128.png"
  }
}
```

---

## 2. Content Script

`content/aliexpress.js` - Extracts product data from AliExpress pages.

### Data to Extract

| Field | Required | Notes |
|-------|----------|-------|
| `title` | Yes | Main product title |
| `price` | Yes | Current price (handle ranges, take lowest) |
| `currency` | No | EUR, USD, etc. (detect from page) |
| `images` | Yes | Full-size image URLs (not thumbnails) |
| `description` | No | Product description (often JS-loaded) |
| `url` | Yes | Current page URL |
| `sku` | No | Product ID from URL |

### Selector Strategies

AliExpress changes their DOM frequently. Use multiple fallback selectors:

```javascript
// Title - try multiple selectors
const titleSelectors = [
  'h1[data-pl="product-title"]',
  'h1.product-title-text',
  '.product-title-text',
  'h1'
];

// Price - look for price containers
const priceSelectors = [
  '.product-price-value',
  '[data-pl="product-price"]',
  '.uniform-banner-box-price',
  '.es--wrap--erdmPRe'  // May change
];

// Images - gallery images
const imageSelectors = [
  '.slider--img--K1Uaz2X img',
  '.images-view-item img',
  '[data-pl="product-image"] img'
];
```

### Image URL Tips

- Thumbnails often have `_50x50.jpg` or similar suffixes
- Replace with `_Q90.jpg_.webp` or remove size suffix for full-size
- Check both `src` and `data-src` attributes (lazy loading)
- Filter out icons, badges, base64 images

### Example Extraction Logic

```javascript
function extractProductData() {
  const data = {
    url: window.location.href,
    sku: extractSkuFromUrl(window.location.href),
    title: null,
    price: null,
    currency: 'EUR',
    images: [],
    description: null
  };
  
  // Extract title
  for (const selector of titleSelectors) {
    const el = document.querySelector(selector);
    if (el && el.textContent.trim().length > 10) {
      data.title = el.textContent.trim();
      break;
    }
  }
  
  // Extract price
  // ... similar pattern
  
  // Extract images
  const images = new Set();
  document.querySelectorAll('img').forEach(img => {
    const src = img.src || img.dataset.src;
    if (src && isProductImage(src)) {
      images.add(getFullSizeUrl(src));
    }
  });
  data.images = [...images].slice(0, 10);
  
  return data;
}

function extractSkuFromUrl(url) {
  const match = url.match(/item\/(\d+)/);
  return match ? match[1] : null;
}

function isProductImage(url) {
  if (!url || url.startsWith('data:')) return false;
  if (url.includes('icon') || url.includes('logo')) return false;
  if (url.includes('ae01.alicdn.com') || url.includes('ae04.alicdn.com')) return true;
  return false;
}

function getFullSizeUrl(url) {
  // Remove size suffixes to get full image
  return url
    .replace(/_\d+x\d+\./, '.')
    .replace(/\.jpg_.*$/, '.jpg')
    .replace(/\.png_.*$/, '.png');
}
```

### Communication with Background

```javascript
// Listen for requests from popup/background
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'extractProduct') {
    const data = extractProductData();
    sendResponse({ success: true, data });
  }
  return true; // Keep channel open for async response
});

// Notify that content script is ready
chrome.runtime.sendMessage({ action: 'contentScriptReady' });
```

---

## 3. Popup UI

`popup/popup.html`:

```html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="popup.css">
</head>
<body>
  <div class="popup">
    <header class="header">
      <img src="../icons/icon-48.png" alt="LesRats" class="logo">
      <h1>LesRats Import</h1>
    </header>
    
    <!-- State: Not on AliExpress -->
    <div id="state-not-aliexpress" class="state hidden">
      <div class="message warning">
        <p>Ouvrez une page produit AliExpress pour importer.</p>
      </div>
    </div>
    
    <!-- State: Extracting -->
    <div id="state-extracting" class="state hidden">
      <div class="loader"></div>
      <p>Extraction en cours...</p>
    </div>
    
    <!-- State: Ready -->
    <div id="state-ready" class="state hidden">
      <div class="product-preview">
        <img id="preview-image" src="" alt="" class="preview-image">
        <div class="preview-info">
          <h3 id="preview-title"></h3>
          <p id="preview-price" class="price"></p>
          <p id="preview-images" class="meta"></p>
        </div>
      </div>
      <button id="btn-send" class="btn btn-primary">
        Envoyer a LesRats
      </button>
    </div>
    
    <!-- State: Sending -->
    <div id="state-sending" class="state hidden">
      <div class="loader"></div>
      <p>Envoi en cours...</p>
    </div>
    
    <!-- State: Success -->
    <div id="state-success" class="state hidden">
      <div class="message success">
        <p>Produit envoye !</p>
        <p class="small">LesRats va s'ouvrir...</p>
      </div>
    </div>
    
    <!-- State: Error -->
    <div id="state-error" class="state hidden">
      <div class="message error">
        <p id="error-message">Une erreur est survenue.</p>
      </div>
      <button id="btn-retry" class="btn btn-secondary">
        Reessayer
      </button>
    </div>
    
    <!-- State: Not Configured -->
    <div id="state-not-configured" class="state hidden">
      <div class="message warning">
        <p>Configurez votre token API LesRats.</p>
      </div>
      <button id="btn-settings" class="btn btn-secondary">
        Parametres
      </button>
    </div>
    
    <!-- Settings Panel -->
    <div id="settings-panel" class="settings hidden">
      <h2>Parametres</h2>
      <div class="form-group">
        <label for="api-token">Token API</label>
        <input type="password" id="api-token" placeholder="Votre token API LesRats">
        <p class="help">Trouvez votre token dans LesRats > Profil > Token API</p>
      </div>
      <div class="form-group">
        <label for="api-url">URL LesRats</label>
        <input type="text" id="api-url" value="http://localhost:8000">
      </div>
      <button id="btn-save-settings" class="btn btn-primary">
        Sauvegarder
      </button>
      <button id="btn-back" class="btn btn-link">
        Retour
      </button>
    </div>
    
    <footer class="footer">
      <button id="btn-open-settings" class="btn-icon" title="Parametres">
        ⚙️
      </button>
    </footer>
  </div>
  
  <script src="popup.js"></script>
</body>
</html>
```

### Popup Styles

`popup/popup.css`:

```css
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  width: 320px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: 14px;
  color: #1f2937;
}

.popup {
  padding: 16px;
}

.header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e5e7eb;
}

.logo {
  width: 32px;
  height: 32px;
}

.header h1 {
  font-size: 16px;
  font-weight: 600;
}

.state {
  text-align: center;
}

.hidden {
  display: none !important;
}

/* Product Preview */
.product-preview {
  background: #f9fafb;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 16px;
  text-align: left;
}

.preview-image {
  width: 100%;
  height: 120px;
  object-fit: cover;
  border-radius: 6px;
  margin-bottom: 8px;
}

.preview-info h3 {
  font-size: 13px;
  font-weight: 500;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.price {
  font-size: 16px;
  font-weight: 600;
  color: #ea580c;
  margin-top: 4px;
}

.meta {
  font-size: 12px;
  color: #6b7280;
  margin-top: 4px;
}

/* Buttons */
.btn {
  display: block;
  width: 100%;
  padding: 12px 16px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-primary {
  background: #ea580c;
  color: white;
}

.btn-primary:hover {
  background: #c2410c;
}

.btn-secondary {
  background: #e5e7eb;
  color: #374151;
}

.btn-secondary:hover {
  background: #d1d5db;
}

.btn-link {
  background: none;
  color: #6b7280;
}

.btn-icon {
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  padding: 4px;
}

/* Messages */
.message {
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 12px;
}

.message.success {
  background: #dcfce7;
  color: #166534;
}

.message.error {
  background: #fee2e2;
  color: #991b1b;
}

.message.warning {
  background: #fef3c7;
  color: #92400e;
}

.small {
  font-size: 12px;
  margin-top: 4px;
}

/* Loader */
.loader {
  width: 32px;
  height: 32px;
  border: 3px solid #e5e7eb;
  border-top-color: #ea580c;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 12px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Settings */
.settings {
  text-align: left;
}

.settings h2 {
  font-size: 16px;
  margin-bottom: 16px;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  margin-bottom: 6px;
}

.form-group input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.form-group input:focus {
  outline: none;
  border-color: #ea580c;
  box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
}

.form-group .help {
  font-size: 11px;
  color: #6b7280;
  margin-top: 4px;
}

.footer {
  margin-top: 16px;
  padding-top: 12px;
  border-top: 1px solid #e5e7eb;
  text-align: right;
}
```

### Popup Logic

`popup/popup.js`:

```javascript
// State management
let currentState = 'extracting';
let productData = null;
let settings = {
  apiToken: '',
  apiUrl: 'http://localhost:8000'
};

// DOM elements
const states = {
  notAliexpress: document.getElementById('state-not-aliexpress'),
  extracting: document.getElementById('state-extracting'),
  ready: document.getElementById('state-ready'),
  sending: document.getElementById('state-sending'),
  success: document.getElementById('state-success'),
  error: document.getElementById('state-error'),
  notConfigured: document.getElementById('state-not-configured')
};

const settingsPanel = document.getElementById('settings-panel');
const mainContent = document.querySelector('.popup');

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
  await loadSettings();
  
  // Check if on AliExpress
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  
  if (!isAliExpressProductPage(tab.url)) {
    setState('notAliexpress');
    return;
  }
  
  if (!settings.apiToken) {
    setState('notConfigured');
    return;
  }
  
  // Extract product data
  extractProduct(tab.id);
});

function isAliExpressProductPage(url) {
  return url && /aliexpress\.(com|us)\/item\//.test(url);
}

async function loadSettings() {
  const stored = await chrome.storage.sync.get(['apiToken', 'apiUrl']);
  settings.apiToken = stored.apiToken || '';
  settings.apiUrl = stored.apiUrl || 'http://localhost:8000';
  
  document.getElementById('api-token').value = settings.apiToken;
  document.getElementById('api-url').value = settings.apiUrl;
}

async function saveSettings() {
  settings.apiToken = document.getElementById('api-token').value.trim();
  settings.apiUrl = document.getElementById('api-url').value.trim();
  
  await chrome.storage.sync.set({
    apiToken: settings.apiToken,
    apiUrl: settings.apiUrl
  });
  
  hideSettings();
  
  // Re-check state
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  if (isAliExpressProductPage(tab.url) && settings.apiToken) {
    extractProduct(tab.id);
  }
}

function setState(state) {
  currentState = state;
  
  // Hide all states
  Object.values(states).forEach(el => el.classList.add('hidden'));
  
  // Show current state
  const stateEl = states[state];
  if (stateEl) {
    stateEl.classList.remove('hidden');
  }
}

async function extractProduct(tabId) {
  setState('extracting');
  
  try {
    const response = await chrome.tabs.sendMessage(tabId, { action: 'extractProduct' });
    
    if (response.success && response.data.title) {
      productData = response.data;
      showProductPreview();
      setState('ready');
    } else {
      showError('Impossible d\'extraire les donnees du produit.');
    }
  } catch (error) {
    showError('Erreur de communication avec la page.');
  }
}

function showProductPreview() {
  document.getElementById('preview-title').textContent = productData.title;
  document.getElementById('preview-price').textContent = 
    productData.price ? `${productData.price} ${productData.currency || 'EUR'}` : 'Prix non detecte';
  document.getElementById('preview-images').textContent = 
    `${productData.images.length} image(s) trouvee(s)`;
  
  if (productData.images.length > 0) {
    document.getElementById('preview-image').src = productData.images[0];
  }
}

async function sendToLesRats() {
  setState('sending');
  
  try {
    const response = await fetch(`${settings.apiUrl}/api/extension/import`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${settings.apiToken}`,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        source: 'aliexpress',
        ...productData
      })
    });
    
    const result = await response.json();
    
    if (response.ok && result.success) {
      setState('success');
      
      // Open LesRats in new tab
      setTimeout(() => {
        chrome.tabs.create({ url: result.redirect_url });
      }, 1000);
    } else {
      showError(result.message || 'Erreur lors de l\'envoi.');
    }
  } catch (error) {
    showError('Impossible de contacter le serveur LesRats.');
  }
}

function showError(message) {
  document.getElementById('error-message').textContent = message;
  setState('error');
}

function showSettings() {
  settingsPanel.classList.remove('hidden');
  Object.values(states).forEach(el => el.classList.add('hidden'));
}

function hideSettings() {
  settingsPanel.classList.add('hidden');
  setState(currentState);
}

// Event listeners
document.getElementById('btn-send').addEventListener('click', sendToLesRats);
document.getElementById('btn-retry').addEventListener('click', async () => {
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  extractProduct(tab.id);
});
document.getElementById('btn-settings').addEventListener('click', showSettings);
document.getElementById('btn-open-settings').addEventListener('click', showSettings);
document.getElementById('btn-save-settings').addEventListener('click', saveSettings);
document.getElementById('btn-back').addEventListener('click', hideSettings);
```

---

## 4. Background Service Worker

`background/service-worker.js`:

```javascript
// Listen for content script ready
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'contentScriptReady') {
    // Could update badge or icon here
    console.log('Content script ready on:', sender.tab.url);
  }
  return true;
});

// Update extension icon when on AliExpress
chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
  if (changeInfo.status === 'complete' && tab.url) {
    const isAliExpress = /aliexpress\.(com|us)\/item\//.test(tab.url);
    
    // Could change icon color or add badge for AliExpress pages
    if (isAliExpress) {
      chrome.action.setBadgeText({ tabId, text: '!' });
      chrome.action.setBadgeBackgroundColor({ tabId, color: '#ea580c' });
    } else {
      chrome.action.setBadgeText({ tabId, text: '' });
    }
  }
});
```

---

## 5. API Endpoint (Backend)

I'll create this endpoint in Laravel:

```
POST /api/extension/import
```

**Request:**
```json
{
  "source": "aliexpress",
  "url": "https://fr.aliexpress.com/item/123456.html",
  "title": "Product Title Here",
  "price": 12.99,
  "currency": "EUR",
  "images": [
    "https://ae01.alicdn.com/...",
    "https://ae01.alicdn.com/..."
  ],
  "description": "Optional description",
  "sku": "123456"
}
```

**Response (success):**
```json
{
  "success": true,
  "import_id": "abc123def456",
  "redirect_url": "http://localhost:8000/products/create?import=abc123def456"
}
```

**Response (error):**
```json
{
  "success": false,
  "message": "Invalid or expired token"
}
```

---

## 6. Testing

### Manual Testing Checklist

- [ ] Load extension in Chrome (`chrome://extensions` > Developer mode > Load unpacked)
- [ ] Visit AliExpress product page
- [ ] Verify badge shows on extension icon
- [ ] Click extension, verify product data extracted
- [ ] Configure API token in settings
- [ ] Click "Send to LesRats"
- [ ] Verify redirect to product wizard with data pre-filled

### Test URLs

```
https://fr.aliexpress.com/item/1005004617598662.html
https://www.aliexpress.com/item/1005006789012345.html
https://aliexpress.us/item/3256801234567890.html
```

---

## 7. Icons

Create simple icons at these sizes:
- `icon-16.png` (16x16) - Toolbar
- `icon-48.png` (48x48) - Extension management
- `icon-128.png` (128x128) - Chrome Web Store

You can use the LesRats logo/rat icon or create a simple "LR" badge.

---

## 8. Deployment

### Local Development
1. Go to `chrome://extensions`
2. Enable "Developer mode"
3. Click "Load unpacked"
4. Select `browser-extension` folder

### Distribution Options
1. **Manual ZIP** - Share `.zip` file, users install via drag & drop
2. **Chrome Web Store** - Requires $5 developer fee, review process
3. **Self-hosted** - Host `.crx` file on your server

---

## Questions?

Ping me when:
- You need the backend API endpoint ready
- You want to add Printables support
- You run into DOM extraction issues
- You want to add Firefox support
