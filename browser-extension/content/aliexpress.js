// Content script pour extraire les données produit d'AliExpress
// Version 2.0 - Extraction via JSON embarqué + DOM

// Écouter les messages du popup
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'extractProduct') {
    extractProductData()
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
});

// Fonction principale d'extraction
async function extractProductData() {
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

// Notifier que le content script est chargé
console.log('🐀 LesRats AliExpress Importer v2.0 loaded');
