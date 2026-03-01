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

  // Debug log accumulator
  const _debug = [];

  // Essayer d'abord d'extraire depuis les données JSON de la page
  let data = extractFromPageData(_debug);

  // Si pas de données JSON, fallback sur le DOM
  if (!data.title) {
    data.title = extractTitleFromDOM();
    _debug.push({ step: 'title', status: data.title ? 'ok' : 'fail', source: 'dom', data: data.title || null });
  } else {
    _debug.push({ step: 'title', status: 'ok', source: 'json', data: data.title });
  }
  if (!data.price) {
    data.price = extractPriceFromDOM();
    _debug.push({ step: 'price', status: data.price ? 'ok' : 'fail', source: 'dom', data: data.price || null });
  } else {
    _debug.push({ step: 'price', status: 'ok', source: 'json', data: data.price });
  }
  // Toujours essayer le DOM pour les images (le slider contient toutes les images visibles)
  // Déclencher le lazy-load du slider avant d'extraire depuis le DOM
  await triggerSliderLazyLoad();
  const domImages = extractImagesFromDOM();
  if (domImages.length > 0) {
    // Le DOM est la source de vérité: il contient toutes les images du slider
    // Merger avec les images JSON pour ne rien perdre
    const jsonImages = data.images || [];
    const allImages = new Set([...domImages, ...jsonImages].map(url => convertToHighRes(url)));
    data.images = Array.from(allImages).slice(0, 20);
    _debug.push({ step: 'images', status: 'ok', source: jsonImages.length > 0 ? 'dom+json' : 'dom', data: `${data.images.length} found (${domImages.length} dom, ${jsonImages.length} json)` });
  } else if (!data.images || data.images.length === 0) {
    _debug.push({ step: 'images', status: 'fail', source: 'dom+json', data: '0 found' });
  } else {
    _debug.push({ step: 'images', status: 'ok', source: 'json', data: `${data.images.length} found` });
  }
  if (!data.description) {
    data.description = extractDescriptionFromDOM();
  }

  // Fallback: extraire les tailles depuis le DOM si pas trouvées en JSON
  if (!data.variants || data.variants.length === 0) {
    const domSizes = extractSizesFromDOM(_debug);
    if (domSizes.length > 0) {
      data.variants = [{ name: 'Size', values: domSizes }];
      console.log('🐀 AliExpress - Sizes extracted from DOM:', domSizes);
    } else {
      _debug.push({ step: 'sizes_dom', status: 'fail', source: 'dom', data: 'No sizes found in DOM' });
    }
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

  // Attach debug info
  data._debug = _debug;

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

// Scroller le slider AliExpress item par item pour déclencher le lazy-load des images
async function triggerSliderLazyLoad() {
  const sliderContainer = document.querySelector('[class*="slider--slider"]');
  if (!sliderContainer) return;

  const items = sliderContainer.querySelectorAll('[class*="slider--item"]');
  if (items.length === 0) return;

  console.log(`🐀 Lazy-load trigger: scrolling ${items.length} slider items...`);

  for (const item of items) {
    // Scroll cet item dans la zone visible du slider pour déclencher l'IntersectionObserver
    item.scrollIntoView({ behavior: 'instant', block: 'nearest', inline: 'nearest' });
    await new Promise(r => setTimeout(r, 80)); // 80ms entre chaque item
  }

  // Attendre que les dernières images finissent de charger
  await new Promise(r => setTimeout(r, 400));

  // Revenir au début
  sliderContainer.scrollLeft = 0;
  console.log('🐀 Lazy-load trigger: done');
}

// Extraire depuis les données JSON embarquées dans la page
function extractFromPageData(_debug = []) {
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

      // --- Extraction des images : chercher imagePathList dans TOUS les scripts ---
      if (data.images.length === 0 && content.includes('imagePathList')) {
        const imageMatch = content.match(/imagePathList['"]*\s*:\s*\[(.*?)\]/s);
        if (imageMatch) {
          const imgUrls = imageMatch[1].match(/https?:\/\/[^"',\s]+/g);
          if (imgUrls) {
            data.images = [...new Set(
              imgUrls
                .map(url => url.replace(/\\u002F/g, '/'))
                .map(url => convertToHighRes(url))
            )].slice(0, 20);
          }
        }
      }

      // Chercher window.runParams ou données similaires pour le titre/prix/variants
      if (content.includes('runParams') || content.includes('pageData') || content.includes('skuModule')) {

        // Fallback images: chercher toutes les URLs alicdn.com / aliexpress-media.com dans le script
        if (data.images.length === 0) {
          const allAliUrls = content.match(/https?:\\?\/\\?\/(ae\d*\.alicdn\.com|ae-pic[^"'\s,\\]*\.aliexpress-media\.com)\/[^"'\s,\\]+/g);
          if (allAliUrls && allAliUrls.length > 0) {
            data.images = [...new Set(
              allAliUrls
                .map(url => url.replace(/\\u002F/g, '/').replace(/\\\//g, '/'))
                .map(url => convertToHighRes(url))
            )].slice(0, 20);
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

        // Extraire les tailles depuis productSKUPropertyList
        try {
          const skuMatch = content.match(/"productSKUPropertyList"\s*:\s*(\[[\s\S]*?\])\s*[,}]/);
          if (skuMatch) {
            const skuProps = JSON.parse(skuMatch[1]);
            const sizeProp = skuProps.find(p =>
              (p.skuPropertyName || '').toLowerCase().includes('size') ||
              (p.skuPropertyName || '').toLowerCase().includes('taille')
            );
            if (sizeProp && sizeProp.skuPropertyValues) {
              data.variants = [{
                name: 'Size',
                values: sizeProp.skuPropertyValues
                  .map(v => v.propertyValueDisplayName || v.propertyValueName)
                  .filter(Boolean)
              }];
              console.log('🐀 AliExpress - Sizes extracted:', data.variants[0].values);
              _debug.push({ step: 'sizes_json', status: 'ok', source: 'json (productSKUPropertyList)', data: data.variants[0].values });
            } else {
              _debug.push({ step: 'sizes_json', status: 'fail', source: 'json', data: 'No size property in skuPropertyList' });
            }
          } else {
            _debug.push({ step: 'sizes_json', status: 'fail', source: 'json', data: 'productSKUPropertyList not found' });
          }
        } catch (e) {
          console.log('🐀 AliExpress - SKU extraction failed:', e.message);
          _debug.push({ step: 'sizes_json', status: 'fail', source: 'json', data: e.message });
        }
      }
    }

    // Dernier recours: scanner TOUS les scripts pour des URLs AliExpress si rien trouvé
    if (data.images.length === 0) {
      const allUrls = new Set();
      document.querySelectorAll('script:not([src])').forEach(s => {
        const c = s.textContent || '';
        const matches = c.match(/https?:(?:\\\/\\\/|\/\/)[^"'\s,\\]*(?:alicdn\.com|aliexpress-media\.com)\/[^"'\s,\\]+/g);
        if (matches) matches.forEach(u => allUrls.add(u.replace(/\\\//g, '/').replace(/\\u002F/g, '/')));
      });
      if (allUrls.size > 0) {
        data.images = [...new Set(
          [...allUrls].map(url => convertToHighRes(url))
        )].slice(0, 20);
        console.log('🐀 AliExpress - Images from global script scan:', data.images.length);
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
    '[class*="slider--item"] img',     // Layout AliExpress actuel (slider--item--RpyeewA)
    '[class*="slider--img"] img',      // Ancien layout
    '[class*="Slider--img"] img',
    '[class*="image-view"] img',       // image-view-v2--previewWrap
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
      // Exclure les items vidéo (ceux qui contiennent un élément videoIcon)
      const sliderItem = el.closest('[class*="slider--item"]');
      if (sliderItem && sliderItem.querySelector('[class*="videoIcon"]')) {
        return;
      }

      let src = el.src || el.srcset || el.dataset.src || el.getAttribute('data-lazy-src');
      if (src) {
        // Prendre la première URL si srcset
        src = src.split(',')[0].split(' ')[0];
        if (src.startsWith('http') && isAliExpressImage(src)) {
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

  // Fallback ultime: toutes les imgs alicdn.com ou aliexpress-media.com y compris lazy-loadées
  if (images.size === 0) {
    document.querySelectorAll('img').forEach(img => {
      // Exclure les items vidéo
      const sliderItem = img.closest('[class*="slider--item"]');
      if (sliderItem && sliderItem.querySelector('[class*="videoIcon"]')) {
        return;
      }

      const src = img.src || img.dataset.src || img.dataset.lazySrc
        || img.getAttribute('data-lazy-src') || img.getAttribute('data-original');
      if (src && isAliExpressImage(src)) {
        images.add(convertToHighRes(src));
      }
    });
  }

  return Array.from(images).slice(0, 20);
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

// Extraire les tailles depuis le DOM (fallback)
function extractSizesFromDOM(_debug = []) {
  const sizes = [];

  // Méthode 1: éléments SKU avec attribut title (structure sku-item--text--)
  const skuRows = document.querySelectorAll('[class*="sku-item--skus"]');
  for (const row of skuRows) {
    const items = row.querySelectorAll('[class*="sku-item--text"]');
    const values = [];
    for (const item of items) {
      const title = item.getAttribute('title');
      if (title) values.push(title.trim());
    }
    // Vérifier si c'est bien une row de tailles (S, M, L, XL, ring sizes, etc.)
    const sizePatterns = /^(XXS|XS|S|M|L|XL|XXL|XXXL|4XL|5XL|6XL|\d{1,3}([.,]\d)?(cm)?)$/i;
    const looksLikeSizes = values.length > 0 && values.some(v => sizePatterns.test(v));
    if (looksLikeSizes) {
      _debug.push({ step: 'sizes_dom', status: 'ok', source: 'dom (method: sku-item pattern match)', data: values });
      return values;
    }
  }

  // Méthode 2: chercher dans les property items avec label "Size" ou "Taille"
  const propItems = document.querySelectorAll('[class*="sku-item--property"]');
  for (const prop of propItems) {
    const label = prop.querySelector('[class*="sku-item--title"]');
    const labelText = (label?.textContent || '').toLowerCase();
    if (labelText.includes('size') || labelText.includes('taille') || labelText.includes('talla')) {
      const items = prop.querySelectorAll('[class*="sku-item--text"]');
      for (const item of items) {
        const title = item.getAttribute('title') || item.textContent?.trim();
        if (title) sizes.push(title);
      }
      if (sizes.length > 0) {
        _debug.push({ step: 'sizes_dom', status: 'ok', source: `dom (method: label match "${labelText.trim()}")`, data: sizes });
        return sizes;
      }
    }
  }

  return sizes;
}

// Convertir une URL d'image en haute résolution
function convertToHighRes(url) {
  // Supprimer le suffixe de conversion format AliExpress (ex: _.avif, _.webp en fin d'URL)
  // Format actuel: filename.jpg_220x220q75.jpg_.avif → filename.jpg
  url = url.replace(/_\.(avif|webp|jpg|jpeg|png)$/i, '');
  // Supprimer les suffixes de taille AliExpress (ex: _220x220q75.jpg)
  url = url.replace(/_\d+x\d+[^.]*\.(jpg|jpeg|png|webp|avif)/gi, '.$1');
  url = url.replace(/\.(jpg|jpeg|png|webp|avif)_\d+x\d+[^.]*\.(jpg|jpeg|png|webp|avif)/gi, '.$1');
  url = url.replace(/_\d+x\d+[^.]*\.(jpg|jpeg|png|webp|avif)/gi, '.$1');
  // Utiliser HTTPS
  url = url.replace(/^http:/, 'https:');
  return url;
}

// Vérifie si une URL est une image AliExpress (alicdn.com ou aliexpress-media.com)
function isAliExpressImage(url) {
  return url.includes('alicdn.com') || url.includes('aliexpress-media.com');
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
