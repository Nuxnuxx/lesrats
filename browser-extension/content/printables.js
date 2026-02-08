// Content script pour extraire les données produit de Printables.com
// Version 1.0 - Extraction via DOM

// Écouter les messages du popup
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'extractPrintablesProduct') {
    extractProductData()
      .then(data => {
        console.log('🐀 Printables - Données extraites:', data);
        sendResponse({ success: true, data });
      })
      .catch(error => {
        console.error('🐀 Printables - Erreur extraction:', error);
        sendResponse({ success: false, error: error.message });
      });
    return true; // Indique une réponse asynchrone
  }
});

// Fonction principale d'extraction
async function extractProductData() {
  // Attendre que la page soit chargée
  await waitForPageLoad();

  const data = {
    title: extractTitle(),
    description: extractDescription(),
    price: extractPrice(),
    images: extractImages(),
    source_url: window.location.href,
    source_type: 'printables',
    tags: extractTags(),
    specifications: extractSpecifications()
  };

  console.log('🐀 Printables - Données finales:', data);
  return data;
}

// Attendre que la page soit chargée
function waitForPageLoad() {
  return new Promise((resolve) => {
    // Attendre un peu que React/Vue rende le contenu
    setTimeout(() => {
      resolve();
    }, 1500);
  });
}

// Extraire le titre
function extractTitle() {
  const selectors = [
    'h1.model-name',
    'h1[class*="model-name"]',
    '.print-page h1',
    '.model-detail h1',
    'h1'
  ];

  for (const selector of selectors) {
    const element = document.querySelector(selector);
    if (element && element.textContent && element.textContent.trim().length > 3) {
      return element.textContent.trim();
    }
  }

  return 'Modèle Printables';
}

// Extraire la description
function extractDescription() {
  const selectors = [
    '.model-description',
    '[class*="description"]',
    '.print-page .description',
    '.model-detail .description',
    '.rich-text-content'
  ];

  for (const selector of selectors) {
    const element = document.querySelector(selector);
    if (element && element.innerText) {
      return element.innerText.trim().substring(0, 5000);
    }
  }

  // Fallback: chercher les paragraphes dans la section principale
  const mainContent = document.querySelector('.print-page, .model-detail, main');
  if (mainContent) {
    const paragraphs = mainContent.querySelectorAll('p');
    const texts = [];
    paragraphs.forEach(p => {
      if (p.textContent && p.textContent.trim().length > 20) {
        texts.push(p.textContent.trim());
      }
    });
    if (texts.length > 0) {
      return texts.join('\n\n');
    }
  }

  return '';
}

// Extraire le prix (souvent gratuit sur Printables)
function extractPrice() {
  const selectors = [
    '.price',
    '[class*="price"]',
    '.model-price',
    '.download-price'
  ];

  for (const selector of selectors) {
    const element = document.querySelector(selector);
    if (element) {
      const text = element.textContent.trim().toLowerCase();

      // Vérifier si gratuit
      if (text.includes('free') || text.includes('gratuit') || text === '0') {
        return 0;
      }

      // Extraire le prix numérique
      const priceMatch = text.match(/(\d+[.,]?\d*)/);
      if (priceMatch) {
        return parseFloat(priceMatch[1].replace(',', '.'));
      }
    }
  }

  // Par défaut, les modèles Printables sont souvent gratuits
  return 0;
}

// Extraire un identifiant unique d'une URL pour déduplication
function getImageFingerprint(url) {
  try {
    const urlObj = new URL(url);
    const pathname = urlObj.pathname;

    // Printables: identifiant unique = /images/{id}_{uuid1}_{uuid2}/
    // Ex: images/9652536_7be1b0fd-cc7a-42e5-9455-1ad273596be4_32c5198f-...
    const match = pathname.match(/images\/(\d+_[a-f0-9-]+)/);
    if (match) {
      return match[1].toLowerCase();
    }

    // Fallback: nom de fichier sans extension (.webp et .jpg = même image)
    const parts = pathname.split('/').filter(p => p);
    const filename = parts[parts.length - 1] || '';
    return filename.replace(/\.[^.]+$/, '').toLowerCase();
  } catch {
    return url;
  }
}

// Extraire les images depuis __NEXT_DATA__ (source la plus fiable pour Next.js)
function extractImagesFromNextData() {
  try {
    const nextDataScript = document.getElementById('__NEXT_DATA__');
    if (!nextDataScript) return null;

    const jsonStr = nextDataScript.textContent;

    // Chercher toutes les URLs media.printables.com dans le JSON
    const urlMatches = jsonStr.match(/https?:\/\/media\.printables\.com\/media\/prints\/[^"\\]+/g);
    if (!urlMatches || urlMatches.length === 0) return null;

    // Dédupliquer les URLs brutes
    const unique = [...new Set(urlMatches)];
    console.log('🐀 Printables - __NEXT_DATA__: trouvé', unique.length, 'URLs uniques');
    return unique;
  } catch (e) {
    console.log('🐀 Printables - __NEXT_DATA__ extraction failed:', e.message);
    return null;
  }
}

// Extraire les images
function extractImages() {
  const imageMap = new Map(); // fingerprint -> highRes URL

  function addImage(src) {
    if (!src || !src.startsWith('http') || !isValidImageUrl(src)) return;

    const highResSrc = convertToHighRes(src);
    const fingerprint = getImageFingerprint(highResSrc);

    if (!imageMap.has(fingerprint)) {
      imageMap.set(fingerprint, highResSrc);
      console.log('🐀 Printables - Image ajoutée:', fingerprint);
    }
  }

  // Stratégie 1: Extraire depuis __NEXT_DATA__ (meilleure source)
  const nextDataImages = extractImagesFromNextData();
  if (nextDataImages && nextDataImages.length > 0) {
    nextDataImages.forEach(addImage);
  }

  // Stratégie 2: DOM fallback — uniquement les images media.printables.com du modèle actuel
  if (imageMap.size === 0) {
    console.log('🐀 Printables - Fallback DOM extraction');

    document.querySelectorAll('img, picture source').forEach(el => {
      const sources = [
        el.src,
        el.srcset,
        el.dataset?.src,
        el.getAttribute('data-srcset')
      ].filter(Boolean);

      for (const source of sources) {
        // Gérer srcset: prendre la plus grande
        if (source.includes(',')) {
          const parts = source.split(',').map(s => s.trim());
          let bestSrc = null;
          let bestWidth = 0;
          for (const part of parts) {
            const [partUrl, descriptor] = part.split(/\s+/);
            const widthMatch = descriptor ? descriptor.match(/(\d+)w/) : null;
            const width = widthMatch ? parseInt(widthMatch[1]) : 0;
            if (width > bestWidth || !bestSrc) {
              bestWidth = width;
              bestSrc = partUrl;
            }
          }
          if (bestSrc && bestSrc.includes('media.printables.com')) addImage(bestSrc);
        } else {
          const cleanSrc = source.split(' ')[0];
          if (cleanSrc.includes('media.printables.com')) addImage(cleanSrc);
        }
      }
    });
  }

  // Stratégie 3: og:image en dernier recours
  if (imageMap.size === 0) {
    const ogImage = document.querySelector('meta[property="og:image"]');
    if (ogImage && ogImage.content) addImage(ogImage.content);
  }

  const result = Array.from(imageMap.values()).slice(0, 10);
  console.log('🐀 Printables - Total images uniques:', result.length);
  return result;
}

// Vérifier si l'URL est une image valide (pas une icône ou un placeholder)
function isValidImageUrl(url) {
  const invalidPatterns = [
    'icon', 'logo', 'avatar', 'placeholder', 'spinner', 'loading',
    '1x1', '2x2', 'blank', 'empty', 'transparent'
  ];

  const lowercaseUrl = url.toLowerCase();
  return !invalidPatterns.some(pattern => lowercaseUrl.includes(pattern));
}

// Convertir une URL d'image en haute résolution
function convertToHighRes(url) {
  url = url.replace(/^http:/, 'https:');

  if (url.includes('media.printables.com')) {
    // Remplacer toute taille par 1920x1920 en mode inside (fit, pas de crop)
    // /thumbs/inside/640x480/ ou /thumbs/cover/320x240/ -> /thumbs/inside/1920x1920/
    url = url.replace(/\/thumbs\/(inside|cover)\/\d+x\d+\//, '/thumbs/inside/1920x1920/');
  }

  return url;
}

// Extraire les tags
function extractTags() {
  const tags = new Set();

  // Sélecteurs pour les tags
  const tagSelectors = [
    '.tags a',
    '.tag',
    '[class*="tag"]',
    '.keywords a',
    '.categories a',
    '.model-tags a'
  ];

  for (const selector of tagSelectors) {
    document.querySelectorAll(selector).forEach(el => {
      const text = el.textContent.trim();
      if (text && text.length > 1 && text.length < 50) {
        // Nettoyer le tag
        const cleanTag = text.replace(/^#/, '').trim();
        if (cleanTag) {
          tags.add(cleanTag);
        }
      }
    });
  }

  // Extraire aussi depuis les meta keywords
  const metaKeywords = document.querySelector('meta[name="keywords"]');
  if (metaKeywords && metaKeywords.content) {
    metaKeywords.content.split(',').forEach(keyword => {
      const clean = keyword.trim();
      if (clean && clean.length > 1 && clean.length < 50) {
        tags.add(clean);
      }
    });
  }

  return Array.from(tags).slice(0, 13); // Etsy limite à 13 tags
}

// Extraire les spécifications
function extractSpecifications() {
  const specs = {};

  // Auteur/Créateur
  const authorSelectors = [
    '.author-name',
    '.creator-name',
    '.user-name',
    '[class*="author"]',
    '[class*="creator"]',
    '.model-author a'
  ];

  for (const selector of authorSelectors) {
    const el = document.querySelector(selector);
    if (el && el.textContent) {
      specs.author = el.textContent.trim();
      break;
    }
  }

  // Catégorie
  const categorySelectors = [
    '.category',
    '.breadcrumb a',
    '[class*="category"]',
    '.model-category'
  ];

  for (const selector of categorySelectors) {
    const el = document.querySelector(selector);
    if (el && el.textContent) {
      const text = el.textContent.trim();
      if (text && text.length > 2 && !text.includes('>')) {
        specs.category = text;
        break;
      }
    }
  }

  // Licence
  const licenseSelectors = [
    '.license',
    '[class*="license"]',
    '.model-license'
  ];

  for (const selector of licenseSelectors) {
    const el = document.querySelector(selector);
    if (el && el.textContent) {
      specs.license = el.textContent.trim();
      break;
    }
  }

  // Statistiques (downloads, likes, etc.)
  const statsSelectors = [
    '.downloads',
    '.likes',
    '.views',
    '[class*="stats"]'
  ];

  for (const selector of statsSelectors) {
    const el = document.querySelector(selector);
    if (el && el.textContent) {
      const text = el.textContent.trim();
      const numMatch = text.match(/(\d+)/);
      if (numMatch) {
        if (selector.includes('download')) {
          specs.downloads = parseInt(numMatch[1]);
        } else if (selector.includes('like')) {
          specs.likes = parseInt(numMatch[1]);
        } else if (selector.includes('view')) {
          specs.views = parseInt(numMatch[1]);
        }
      }
    }
  }

  return specs;
}

// Extraire l'ID du modèle depuis l'URL
function extractModelId() {
  // URL format: https://www.printables.com/model/123456-model-name
  const match = window.location.href.match(/\/model\/(\d+)/);
  return match ? match[1] : null;
}

// Notifier que le content script est chargé
console.log('🐀 LesRats Printables Importer v1.0 loaded');
