// Content script pour extraire les données produit d'AliExpress
// Version 2.0 - Extraction via JSON embarqué + DOM

// Écouter les messages du popup
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'extractProduct') {
    extractProductData(request.includeCountryPrices || false)
      .then(data => {
        console.log('🐀 Données extraites:', data);
        sendResponse({ success: true, data });
      })
      .catch(error => {
        console.error('🐀 Erreur extraction:', error);
        sendResponse({ success: false, error: error.message });
      });
    return true; // Indique une réponse asynchrone
  }
  
  // Handle country price scraping separately (for progress updates)
  if (request.action === 'scrapeCountryPrices') {
    scrapeCountryPrices((progress) => {
      // Send progress update back to popup
      chrome.runtime.sendMessage({
        type: 'COUNTRY_PRICE_PROGRESS',
        progress: progress
      });
    })
      .then(countryPrices => {
        sendResponse({ success: true, countryPrices });
      })
      .catch(error => {
        console.error('🐀 Erreur country prices:', error);
        sendResponse({ success: false, error: error.message });
      });
    return true;
  }
});

// Fonction principale d'extraction
async function extractProductData(includeCountryPrices = false) {
  // Attendre que la page soit chargée
  await waitForPageLoad();

  // Essayer d'abord d'extraire depuis les données JSON de la page
  let data = extractFromPageData();

  // Si pas de données JSON, fallback sur le DOM
  if (!data.title) {
    data.title = extractTitleFromDOM();
  }
  if (!data.price) {
    data.price = extractPriceFromDOM();
  }
  if (!data.images || data.images.length === 0) {
    data.images = extractImagesFromDOM();
  }
  if (!data.description) {
    data.description = extractDescriptionFromDOM();
  }

  // Toujours ajouter ces infos
  data.source_url = window.location.href;
  data.aliexpress_product_id = extractProductId();
  data.source_type = 'aliexpress';

  // Optionally scrape country-specific prices
  if (includeCountryPrices) {
    console.log('🐀 Scraping country-specific prices...');
    try {
      data.country_prices = await scrapeCountryPrices();
    } catch (error) {
      console.error('🐀 Failed to scrape country prices:', error);
      data.country_prices = null;
    }
  }

  console.log('🐀 Données finales:', data);
  return data;
}

// Attendre que la page soit chargée
function waitForPageLoad() {
  return new Promise((resolve) => {
    // Attendre un peu que React/Vue rende le contenu
    setTimeout(() => {
      resolve();
    }, 2000);
  });
}

// Extraire depuis les données JSON embarquées dans la page
function extractFromPageData() {
  const data = {
    title: null,
    description: null,
    price: null,
    images: [],
    variants: [],
    specifications: {}
  };

  try {
    // Chercher les scripts contenant les données produit
    const scripts = document.querySelectorAll('script');

    for (const script of scripts) {
      const content = script.textContent || '';

      // Chercher window.runParams ou données similaires
      if (content.includes('runParams') || content.includes('pageData') || content.includes('skuModule')) {

        // Extraire imagePathList
        const imageMatch = content.match(/imagePathList['"]*\s*:\s*\[(.*?)\]/s);
        if (imageMatch) {
          const imgUrls = imageMatch[1].match(/https?:\/\/[^"',\s]+/g);
          if (imgUrls) {
            data.images = imgUrls
              .map(url => url.replace(/\\u002F/g, '/'))
              .filter(url => !url.includes('_80x80') && !url.includes('_220x220'))
              .slice(0, 10);
          }
        }

        // Extraire le titre depuis subject ou title
        const titleMatch = content.match(/["']subject["']\s*:\s*["']([^"']+)["']/);
        if (titleMatch) {
          data.title = decodeUnicode(titleMatch[1]);
        }

        // Extraire le prix
        const priceMatch = content.match(/["']formattedActivityPrice["']\s*:\s*["']([^"']+)["']/);
        if (priceMatch) {
          data.price = parsePrice(priceMatch[1]);
        }
        if (!data.price) {
          const priceMatch2 = content.match(/["']formattedPrice["']\s*:\s*["']([^"']+)["']/);
          if (priceMatch2) {
            data.price = parsePrice(priceMatch2[1]);
          }
        }
        if (!data.price) {
          const priceMatch3 = content.match(/["']minPrice["']\s*:\s*["']?(\d+\.?\d*)["']?/);
          if (priceMatch3) {
            data.price = parseFloat(priceMatch3[1]);
          }
        }
      }
    }

    // Aussi chercher dans window.__INIT_DATA__ ou similaire
    const initDataScript = document.querySelector('script[id*="__INIT"]') ||
                          document.querySelector('script:not([src])');

    if (initDataScript) {
      const content = initDataScript.textContent || '';

      // Pattern pour données React/Next.js
      const jsonMatch = content.match(/window\.__INIT.*?=\s*({.*});?$/ms);
      if (jsonMatch) {
        try {
          // Tenter de parser le JSON (peut échouer si mal formé)
          const jsonData = JSON.parse(jsonMatch[1].replace(/undefined/g, 'null'));
          // Explorer la structure pour trouver les données
          if (jsonData.data?.root?.fields) {
            const fields = jsonData.data.root.fields;
            if (fields.subject) data.title = fields.subject;
            if (fields.price) data.price = parseFloat(fields.price);
          }
        } catch (e) {
          // JSON mal formé, ignorer
        }
      }
    }

  } catch (error) {
    console.error('🐀 Erreur extraction JSON:', error);
  }

  return data;
}

// Extraire le titre depuis le DOM
function extractTitleFromDOM() {
  const selectors = [
    'h1[data-pl="product-title"]',
    '[data-pl="product-title"]',
    '.title--wrap--UUHae_g h1',
    '.product-title-text',
    '.HazeProductTitle h1',
    'h1.pdp-mod-product-badge-title',
    '[class*="ProductTitle"] h1',
    '[class*="title--wrap"] h1',
    'h1'
  ];

  for (const selector of selectors) {
    const element = document.querySelector(selector);
    if (element && element.textContent && element.textContent.trim().length > 5) {
      return element.textContent.trim();
    }
  }

  return 'Produit AliExpress';
}

// Extraire le prix depuis le DOM
function extractPriceFromDOM() {
  const selectors = [
    '[data-pl="product-price"]',
    '.price--currentPriceText--V8_y_b5',
    '.product-price-value',
    '.uniform-banner-box-price',
    '[class*="Price--currentPrice"]',
    '[class*="price--current"]',
    '.es--wrap--erdmPRe span',
    '.pdp-comp-price-current'
  ];

  for (const selector of selectors) {
    const element = document.querySelector(selector);
    if (element) {
      const text = element.textContent.trim();
      const price = parsePrice(text);
      if (price) return price;
    }
  }

  // Chercher n'importe quel élément avec un prix en euros
  const allElements = document.querySelectorAll('*');
  for (const el of allElements) {
    if (el.children.length === 0) { // Seulement les feuilles
      const text = el.textContent.trim();
      if (text.match(/^\d+[,.]?\d*\s*€$/) || text.match(/^€\s*\d+[,.]?\d*$/)) {
        const price = parsePrice(text);
        if (price && price > 0 && price < 10000) return price;
      }
    }
  }

  return null;
}

// Extraire les images depuis le DOM
function extractImagesFromDOM() {
  const images = new Set();

  // Sélecteurs pour les images principales
  const imageSelectors = [
    '[class*="slider--img"] img',
    '[class*="Slider--img"] img',
    '.pdp-main-image img',
    '[data-pl="product-image"] img',
    '.images-view-item img',
    '[class*="gallery"] img',
    '.magnifier-image img',
    'picture source',
    'picture img'
  ];

  for (const selector of imageSelectors) {
    document.querySelectorAll(selector).forEach(el => {
      let src = el.src || el.srcset || el.dataset.src || el.getAttribute('data-lazy-src');
      if (src) {
        // Prendre la première URL si srcset
        src = src.split(',')[0].split(' ')[0];
        if (src.startsWith('http')) {
          // Convertir en haute résolution
          src = convertToHighRes(src);
          images.add(src);
        }
      }
    });
  }

  // Chercher dans les attributs style (background-image)
  document.querySelectorAll('[style*="background-image"]').forEach(el => {
    const style = el.getAttribute('style');
    const match = style.match(/url\(['"]?(https?:\/\/[^'")\s]+)['"]?\)/);
    if (match) {
      const src = convertToHighRes(match[1]);
      images.add(src);
    }
  });

  // Chercher les thumbnails et les convertir en grandes images
  document.querySelectorAll('[class*="thumbnail"] img, [class*="Thumbnail"] img, [class*="nav"] img').forEach(img => {
    let src = img.src || img.dataset.src;
    if (src && src.startsWith('http')) {
      src = convertToHighRes(src);
      images.add(src);
    }
  });

  return Array.from(images).slice(0, 10);
}

// Extraire la description depuis le DOM
function extractDescriptionFromDOM() {
  const selectors = [
    '.product-description',
    '[class*="Description--wrap"]',
    '.detail-desc-decorate-richtext',
    '[data-pl="product-description"]',
    '#product-description',
    '.ProductDescription-module',
    '[class*="desc--wrap"]'
  ];

  for (const selector of selectors) {
    const element = document.querySelector(selector);
    if (element && element.innerText) {
      return element.innerText.trim().substring(0, 5000);
    }
  }

  // Fallback: chercher les spécifications comme description
  const specs = [];
  document.querySelectorAll('[class*="specification"] li, [class*="Specification"] li').forEach(li => {
    if (li.textContent) {
      specs.push(li.textContent.trim());
    }
  });

  if (specs.length > 0) {
    return specs.join('\n');
  }

  return '';
}

// Convertir une URL d'image en haute résolution
function convertToHighRes(url) {
  // Supprimer les suffixes de taille AliExpress
  url = url.replace(/_\d+x\d+[^.]*\.(jpg|jpeg|png|webp|avif)/gi, '.$1');
  url = url.replace(/\.(jpg|jpeg|png|webp|avif)_\d+x\d+[^.]*\.(jpg|jpeg|png|webp|avif)/gi, '.$1');
  // Utiliser HTTPS
  url = url.replace(/^http:/, 'https:');
  return url;
}

// Parser un prix depuis une chaîne
function parsePrice(text) {
  if (!text) return null;

  // Nettoyer le texte
  text = text.replace(/\s+/g, ' ').trim();

  // Extraire le nombre
  // Formats supportés: "12,34 €", "€12.34", "12.34", "1 234,56 €"
  const match = text.match(/([\d\s]+[,.]?\d*)/);
  if (match) {
    let price = match[1]
      .replace(/\s/g, '')  // Supprimer les espaces
      .replace(',', '.');   // Convertir virgule en point

    return parseFloat(price) || null;
  }

  return null;
}

// Décoder les caractères Unicode échappés
function decodeUnicode(str) {
  return str.replace(/\\u[\dA-F]{4}/gi, (match) => {
    return String.fromCharCode(parseInt(match.replace(/\\u/g, ''), 16));
  });
}

// Extraire l'ID produit depuis l'URL
function extractProductId() {
  const match = window.location.href.match(/\/item\/(\d+)\.html/);
  return match ? match[1] : null;
}

// ============================================
// COUNTRY-SPECIFIC PRICING
// ============================================

// Countries to scrape for pricing (code -> name mapping)
const TARGET_COUNTRIES = {
  'US': 'United States',
  'DE': 'Germany',
  'AT': 'Austria',
  'FR': 'France',
  'CA': 'Canada',
  'ES': 'Spain'
};

// Scrape prices for multiple countries
async function scrapeCountryPrices(progressCallback = null) {
  const countryPrices = {};
  const countries = Object.entries(TARGET_COUNTRIES);
  
  console.log('🐀 Starting country price scraping for', countries.length, 'countries');
  
  // Get current selected country to restore later
  const originalCountry = getCurrentSelectedCountry();
  console.log('🐀 Original country:', originalCountry);
  
  for (let i = 0; i < countries.length; i++) {
    const [code, name] = countries[i];
    
    if (progressCallback) {
      progressCallback({
        current: i + 1,
        total: countries.length,
        country: name,
        code: code
      });
    }
    
    try {
      console.log(`🐀 Scraping price for ${name} (${code})...`);
      
      // Select the country
      const selected = await selectCountry(code, name);
      if (!selected) {
        console.warn(`🐀 Could not select ${name}, skipping`);
        continue;
      }
      
      // Wait for page to update prices
      await sleep(2500);
      
      // Scrape the current price and shipping
      const priceData = scrapeCurrentPriceAndShipping();
      
      if (priceData.price !== null) {
        countryPrices[code] = {
          price: priceData.price,
          shipping: priceData.shipping,
          total: round2(priceData.price + priceData.shipping)
        };
        console.log(`🐀 ${name}: Price=${priceData.price}, Shipping=${priceData.shipping}, Total=${countryPrices[code].total}`);
      } else {
        console.warn(`🐀 Could not extract price for ${name}`);
      }
      
    } catch (error) {
      console.error(`🐀 Error scraping ${name}:`, error);
    }
  }
  
  // Restore original country
  if (originalCountry) {
    console.log('🐀 Restoring original country:', originalCountry);
    try {
      await selectCountryByName(originalCountry);
    } catch (e) {
      console.warn('🐀 Could not restore original country');
    }
  }
  
  console.log('🐀 Country prices scraped:', countryPrices);
  return countryPrices;
}

// Get the currently selected country name
function getCurrentSelectedCountry() {
  // Find the country selector - it's the first one with "Ship to" label
  const shipToLabel = document.querySelector('.form-item--title--1ZN23sl');
  if (shipToLabel && shipToLabel.textContent.includes('Ship to')) {
    const selectWrap = shipToLabel.nextElementSibling?.querySelector('.select--text--1b85oDo');
    if (selectWrap) {
      // Get the country name (skip the flag span)
      const spans = selectWrap.querySelectorAll('span[style*="vertical-align"]');
      if (spans.length >= 2) {
        return spans[1].textContent.trim();
      }
    }
  }
  
  // Fallback: look for delivery address
  const deliveryTo = document.querySelector('.delivery-v2--to--Mtweg7y');
  if (deliveryTo) {
    return deliveryTo.textContent.trim();
  }
  
  return null;
}

// Select a country by code and name
async function selectCountry(code, name) {
  // Find and click the country selector dropdown
  const countrySelector = findCountrySelector();
  if (!countrySelector) {
    console.warn('🐀 Country selector not found');
    return false;
  }
  
  // Click to open the dropdown
  countrySelector.click();
  await sleep(500);
  
  // Wait for popup to appear (use partial class match)
  const popup = await waitForElementByPartialClass('select--popup', 2000);
  if (!popup) {
    console.warn('🐀 Dropdown popup did not appear');
    return false;
  }
  
  // Find the country item by flag class or text
  const countryItem = findCountryItem(popup, code, name);
  if (!countryItem) {
    console.warn(`🐀 Country item not found for ${name} (${code})`);
    // Close the popup by clicking elsewhere
    document.body.click();
    await sleep(300);
    return false;
  }
  
  // Click the country
  countryItem.click();
  await sleep(500);
  
  return true;
}

// Select a country by name (for restoring original)
async function selectCountryByName(name) {
  const countrySelector = findCountrySelector();
  if (!countrySelector) return false;
  
  countrySelector.click();
  await sleep(500);
  
  const popup = document.querySelector('[class*="select--popup"]');
  if (!popup) return false;
  
  // Find by text (partial class match)
  const items = popup.querySelectorAll('[class*="select--item"]');
  for (const item of items) {
    if (item.textContent.includes(name)) {
      item.click();
      await sleep(500);
      return true;
    }
  }
  
  document.body.click();
  return false;
}

// Find the country selector element
function findCountrySelector() {
  // Method 1: Look for "Ship to" label using partial class match
  const allElements = document.querySelectorAll('[class*="form-item--title"]');
  for (const label of allElements) {
    if (label.textContent.includes('Ship to')) {
      const content = label.nextElementSibling;
      if (content) {
        const selector = content.querySelector('[class*="select--text"]');
        if (selector) {
          console.log('🐀 Found country selector via Ship to label');
          return selector;
        }
      }
    }
  }
  
  // Method 2: Find by country flag presence (partial class match)
  const selectors = document.querySelectorAll('[class*="select--text"]');
  for (const sel of selectors) {
    if (sel.querySelector('[class*="country-flag"]')) {
      console.log('🐀 Found country selector via flag');
      return sel;
    }
  }
  
  // Method 3: Look inside the shipping/delivery section
  const deliverySection = document.querySelector('[class*="es--contentWrap"], [class*="delivery-v2--wrap"]');
  if (deliverySection) {
    const selector = deliverySection.querySelector('[class*="select--text"]');
    if (selector && selector.querySelector('[class*="country-flag"]')) {
      console.log('🐀 Found country selector via delivery section');
      return selector;
    }
  }
  
  // Method 4: Find any clickable element with a country flag
  const flagElements = document.querySelectorAll('[class*="country-flag"]');
  for (const flag of flagElements) {
    const parent = flag.closest('[class*="select--text"], [class*="select--wrap"]');
    if (parent) {
      console.log('🐀 Found country selector via flag parent');
      return parent.querySelector('[class*="select--text"]') || parent;
    }
  }
  
  console.log('🐀 Could not find country selector with any method');
  return null;
}

// Find a country item in the popup
function findCountryItem(popup, code, name) {
  // Use partial class match for items
  const items = popup.querySelectorAll('[class*="select--item"]');
  
  console.log(`🐀 Looking for ${name} (${code}) in ${items.length} items`);
  
  // First try to find by flag class (most reliable)
  for (const item of items) {
    // Try multiple flag class patterns
    const flag = item.querySelector(`[class*="country-flag"][class*="${code}"], .country-flag-y2023.${code}, [class*="${code}"]`);
    if (flag && flag.className.includes('country-flag')) {
      console.log(`🐀 Found ${name} by flag class`);
      return item;
    }
  }
  
  // Fallback: find by country name text
  for (const item of items) {
    const text = item.textContent.trim();
    if (text.includes(name)) {
      console.log(`🐀 Found ${name} by text`);
      return item;
    }
  }
  
  console.log(`🐀 Could not find ${name} in popup`);
  return null;
}

// Scrape current price and shipping from the page
function scrapeCurrentPriceAndShipping() {
  const result = {
    price: null,
    shipping: 0
  };
  
  // Get product price
  result.price = extractPriceFromDOM();
  
  // Get shipping cost
  const shippingCost = extractShippingCost();
  result.shipping = shippingCost;
  
  return result;
}

// Extract shipping cost from the page
function extractShippingCost() {
  // Look for shipping information in the shipping section
  const shippingItems = document.querySelectorAll('.shipping--item--F04J6q9, .dynamic-shipping');
  
  for (const item of shippingItems) {
    const text = item.textContent || '';
    
    // Check for free shipping
    if (text.match(/free\s*shipping/i) || 
        text.match(/livraison\s*gratuite/i) ||
        text.match(/gratis/i)) {
      return 0;
    }
    
    // Look for shipping cost pattern
    // Patterns: "$2.50", "€2,50", "2.50 €", "2,50€", "+$1.50"
    const costMatch = text.match(/[+]?\s*[\$€]?\s*(\d+[.,]\d{2})\s*[\$€]?/);
    if (costMatch) {
      const cost = parseFloat(costMatch[1].replace(',', '.'));
      // Sanity check - shipping usually under 50
      if (cost > 0 && cost < 50) {
        return cost;
      }
    }
  }
  
  // Also check for shipping in delivery section
  const deliverySection = document.querySelector('[data-pl="product-delivery"]');
  if (deliverySection) {
    const text = deliverySection.textContent || '';
    if (text.match(/free/i) || text.match(/gratuit/i)) {
      return 0;
    }
  }
  
  // Default to free shipping if we can't find cost
  return 0;
}

// Wait for an element to appear
function waitForElement(selector, timeout = 5000) {
  return new Promise((resolve) => {
    const startTime = Date.now();
    
    const check = () => {
      const element = document.querySelector(selector);
      if (element) {
        resolve(element);
        return;
      }
      
      if (Date.now() - startTime > timeout) {
        resolve(null);
        return;
      }
      
      requestAnimationFrame(check);
    };
    
    check();
  });
}

// Wait for an element by partial class name
function waitForElementByPartialClass(partialClass, timeout = 5000) {
  return new Promise((resolve) => {
    const startTime = Date.now();
    
    const check = () => {
      const element = document.querySelector(`[class*="${partialClass}"]`);
      if (element) {
        resolve(element);
        return;
      }
      
      if (Date.now() - startTime > timeout) {
        resolve(null);
        return;
      }
      
      requestAnimationFrame(check);
    };
    
    check();
  });
}

// Sleep helper
function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

// Round to 2 decimal places
function round2(num) {
  return Math.round(num * 100) / 100;
}

// Notifier que le content script est chargé
console.log('🐀 LesRats AliExpress Importer v2.0 loaded');
