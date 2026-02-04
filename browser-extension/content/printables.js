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

// Extraire le nom de fichier unique d'une URL pour déduplication
function getImageFingerprint(url) {
  try {
    // Extraire juste le nom du fichier sans le chemin et les paramètres
    const urlObj = new URL(url);
    const pathname = urlObj.pathname;
    // Prendre les 2 derniers segments du chemin (format + fichier)
    const parts = pathname.split('/').filter(p => p);
    const filename = parts.slice(-2).join('/');
    return filename.toLowerCase();
  } catch {
    return url;
  }
}

// Extraire les images
function extractImages() {
  const imageMap = new Map(); // fingerprint -> highRes URL

  // Sélecteurs pour les images principales (ordre de priorité)
  const imageSelectors = [
    // Printables specific - gallery images (priorité haute)
    '[data-testid="gallery-image"] img',
    '.print-images img',
    '.model-images img',
    // Generic gallery selectors
    '.gallery img',
    '.image-gallery img',
    '.swiper-slide img',
    '.carousel img',
    'picture source',
    'picture img'
  ];

  function addImage(src) {
    if (!src || !src.startsWith('http') || !isValidImageUrl(src)) return;

    // Convertir en haute résolution
    const highResSrc = convertToHighRes(src);
    const fingerprint = getImageFingerprint(highResSrc);

    // Ne pas ajouter si on a déjà cette image
    if (!imageMap.has(fingerprint)) {
      imageMap.set(fingerprint, highResSrc);
      console.log('🐀 Printables - Image ajoutée:', fingerprint);
    }
  }

  for (const selector of imageSelectors) {
    document.querySelectorAll(selector).forEach(el => {
      // Chercher l'URL dans plusieurs attributs (priorité: srcset > data-src > src)
      let srcset = el.srcset || el.getAttribute('data-srcset');
      let src = el.dataset.src || el.getAttribute('data-lazy-src') || el.src;

      // Si srcset existe, prendre la plus grande image
      if (srcset) {
        const srcsetParts = srcset.split(',').map(s => s.trim());
        let bestSrc = null;
        let bestWidth = 0;
        for (const part of srcsetParts) {
          const [url, descriptor] = part.split(/\s+/);
          const widthMatch = descriptor ? descriptor.match(/(\d+)w/) : null;
          const width = widthMatch ? parseInt(widthMatch[1]) : 0;
          if (width > bestWidth || !bestSrc) {
            bestWidth = width;
            bestSrc = url;
          }
        }
        if (bestSrc) {
          src = bestSrc;
        }
      }

      if (src) {
        src = src.split(' ')[0]; // Nettoyer
        addImage(src);
      }
    });
  }

  // Chercher dans les background-image (éviter les doublons)
  document.querySelectorAll('[style*="background-image"]').forEach(el => {
    const style = el.getAttribute('style');
    const match = style.match(/url\(['"]?(https?:\/\/[^'")\s]+)['"]?\)/);
    if (match) {
      addImage(match[1]);
    }
  });

  // OpenGraph image en dernier (souvent la même que la première)
  const ogImage = document.querySelector('meta[property="og:image"]');
  if (ogImage && ogImage.content) {
    addImage(ogImage.content);
  }

  const result = Array.from(imageMap.values()).slice(0, 10);
  console.log('🐀 Printables - Total images uniques:', result.length, result);
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
  // Utiliser HTTPS
  url = url.replace(/^http:/, 'https:');

  // Pattern spécifique Printables
  if (url.includes('media.printables.com')) {
    // Stratégie 1: Augmenter la taille dans l'URL thumbnail
    // Format: /thumbs/inside/1280x960/png/ -> /thumbs/inside/3840x2160/png/
    // Ou: /thumbs/cover/320x240/png/ -> /thumbs/cover/3840x2160/png/
    url = url.replace(/\/thumbs\/([^/]+)\/\d+x\d+\//, '/thumbs/$1/3840x2160/');

    // Stratégie 2: Si c'est déjà un chemin /images/, garder tel quel
    // Les images originales sont souvent dans /images/

    console.log('🐀 Printables - Image haute résolution:', url);
  }

  // Patterns génériques pour autres sites
  url = url.replace(/_\d+x\d+[^.]*\.(jpg|jpeg|png|webp|avif)/gi, '.$1');
  url = url.replace(/\/thumb\//, '/');
  url = url.replace(/\/small\//, '/');
  url = url.replace(/\/medium\//, '/');

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
