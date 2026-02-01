// Service Worker pour l'extension LesRats

// Import secure storage utilities
importScripts('../lib/secure-storage.js');

// Écouter l'installation de l'extension
chrome.runtime.onInstalled.addListener(async (details) => {
  if (details.reason === 'install') {
    console.log('🐀 LesRats Extension installée!');

    // Définir les paramètres par défaut (URL is not sensitive, token starts empty)
    await chrome.storage.local.set({
      apiUrl: 'http://localhost:8000'
    });
  }
});

// Écouter le raccourci clavier
chrome.commands.onCommand.addListener(async (command) => {
  if (command === 'quick-import') {
    console.log('🐀 Raccourci clavier déclenché: import rapide');
    
    // Récupérer l'onglet actif
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    
    if (!tab || !tab.url || !tab.url.includes('aliexpress.com/item/')) {
      // Afficher une notification d'erreur
      chrome.action.setBadgeText({ text: '!', tabId: tab?.id });
      chrome.action.setBadgeBackgroundColor({ color: '#ef4444', tabId: tab?.id });
      setTimeout(() => {
        chrome.action.setBadgeText({ text: '', tabId: tab?.id });
      }, 2000);
      return;
    }

    // Afficher le badge "en cours"
    chrome.action.setBadgeText({ text: '⏳', tabId: tab.id });
    chrome.action.setBadgeBackgroundColor({ color: '#f59e0b', tabId: tab.id });

    try {
      // Extraire les données du produit
      const extractResponse = await chrome.tabs.sendMessage(tab.id, { action: 'extractProduct' });
      
      if (!extractResponse || !extractResponse.success) {
        throw new Error(extractResponse?.error || 'Extraction échouée');
      }

      // Récupérer les paramètres (token is encrypted)
      const settings = await chrome.storage.local.get(['apiUrl']);
      const apiUrl = settings.apiUrl || 'http://localhost:8000';
      const apiToken = await SecureStorage.getSecure('apiToken') || '';

      // Envoyer au serveur
      const result = await handleImport(extractResponse.data, apiUrl, apiToken);

      if (result.success) {
        // Succès - badge vert
        chrome.action.setBadgeText({ text: '✓', tabId: tab.id });
        chrome.action.setBadgeBackgroundColor({ color: '#22c55e', tabId: tab.id });
        
        // Ouvrir la page du produit dans un nouvel onglet
        if (result.product_url) {
          chrome.tabs.create({ url: result.product_url });
        }
      } else {
        throw new Error(result.error || 'Import échoué');
      }
    } catch (error) {
      console.error('🐀 Erreur import rapide:', error);
      // Erreur - badge rouge
      chrome.action.setBadgeText({ text: '✗', tabId: tab.id });
      chrome.action.setBadgeBackgroundColor({ color: '#ef4444', tabId: tab.id });
    }

    // Réinitialiser le badge après 3 secondes
    setTimeout(() => {
      chrome.action.setBadgeText({ text: '✓', tabId: tab.id });
      chrome.action.setBadgeBackgroundColor({ color: '#22c55e', tabId: tab.id });
    }, 3000);
  }
});

// Gérer les messages entre composants
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'importProduct') {
    handleImport(request.data, request.apiUrl, request.apiToken)
      .then(result => sendResponse(result))
      .catch(error => sendResponse({ success: false, error: error.message }));
    return true;
  }
  
  // Fetch Etsy product data (routed through service worker to avoid CORS)
  if (request.action === 'fetchEtsyData') {
    fetchEtsyData(request.apiUrl, request.productId, request.apiToken)
      .then(result => sendResponse(result))
      .catch(error => sendResponse({ success: false, error: error.message }));
    return true;
  }
  
  // Fetch shops list
  if (request.action === 'fetchShops') {
    fetchShops(request.apiUrl, request.apiToken)
      .then(result => sendResponse(result))
      .catch(error => sendResponse({ success: false, error: error.message }));
    return true;
  }
  
  // Download images from URLs (routed through service worker to avoid CORS)
  if (request.action === 'downloadImages') {
    downloadImages(request.imageUrls)
      .then(result => sendResponse({ success: true, images: result }))
      .catch(error => sendResponse({ success: false, error: error.message }));
    return true;
  }
  
  // Download images and save to Downloads folder
  if (request.action === 'downloadImagesAndSave') {
    downloadAndSaveImages(request.imageUrls, request.productTitle)
      .then(result => sendResponse(result))
      .catch(error => sendResponse({ success: false, error: error.message }));
    return true;
  }
  
  // Auto-upload images via native messaging host
  if (request.action === 'autoUploadImages') {
    autoUploadViaNativeHost(request.images)
      .then(result => sendResponse(result))
      .catch(error => sendResponse({ success: false, error: error.message }));
    return true;
  }
});

// Native messaging host name
const NATIVE_HOST = 'com.lesrats.host';

// Auto-upload images via native messaging host
async function autoUploadViaNativeHost(images) {
  try {
    // Send images to native host
    const response = await chrome.runtime.sendNativeMessage(NATIVE_HOST, {
      action: 'autoUpload',
      images: images
    });
    
    return response;
  } catch (error) {
    console.error('🐀 Native messaging error:', error);
    return { 
      success: false, 
      error: 'Native host not installed. Run install.sh in browser-extension/native-host/' 
    };
  }
}

// Fetch product data for Etsy publishing
async function fetchEtsyData(apiUrl, productId, apiToken) {
  // Remove trailing slash from URL
  const baseUrl = apiUrl.replace(/\/+$/, '');
  
  try {
    const headers = {
      'Accept': 'application/json'
    };
    
    if (apiToken) {
      headers['Authorization'] = `Bearer ${apiToken}`;
    }
    
    const response = await fetch(`${baseUrl}/api/extension/product/${productId}/etsy-data`, {
      headers: headers
    });
    
    if (response.status === 401) {
      return { success: false, error: 'Token invalide ou expiré. Vérifiez votre token API dans les paramètres.' };
    }
    
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('🐀 Error fetching Etsy data:', error);
    return { success: false, error: error.message };
  }
}

// Fetch shops list
async function fetchShops(apiUrl, apiToken) {
  // Remove trailing slash from URL to avoid double slashes
  const baseUrl = apiUrl.replace(/\/+$/, '');
  
  console.log('🐀 fetchShops called with:', { apiUrl: baseUrl, hasToken: !!apiToken });
  
  try {
    const headers = {
      'Accept': 'application/json'
    };
    
    if (apiToken) {
      headers['Authorization'] = `Bearer ${apiToken}`;
    }
    
    const url = `${baseUrl}/api/extension/shops`;
    console.log('🐀 Fetching shops from:', url);
    
    const response = await fetch(url, {
      headers: headers
    });
    
    console.log('🐀 Response status:', response.status);
    
    if (response.status === 401) {
      return { success: false, error: 'Token invalide. Verifiez votre token.' };
    }
    
    if (response.status === 403) {
      return { success: false, error: 'Acces refuse (403)' };
    }
    
    if (!response.ok) {
      return { success: false, error: `Erreur serveur (${response.status})` };
    }
    
    const data = await response.json();
    console.log('🐀 Shops response:', data);
    return data;
  } catch (error) {
    console.error('🐀 Error fetching shops:', error);
    // More specific error messages
    if (error.message.includes('Failed to fetch')) {
      return { success: false, error: 'Serveur inaccessible' };
    }
    return { success: false, error: error.message };
  }
}

// Download image from URL and convert to base64
async function downloadImageAsBase64(imageUrl) {
  try {
    const response = await fetch(imageUrl);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    
    const blob = await response.blob();
    const base64 = await blobToBase64(blob);
    
    // Determine file extension from content-type or URL
    const contentType = response.headers.get('content-type') || 'image/jpeg';
    const extension = contentType.includes('png') ? 'png' : 
                      contentType.includes('webp') ? 'webp' : 'jpg';
    
    return {
      success: true,
      base64: base64,
      mimeType: contentType,
      extension: extension
    };
  } catch (error) {
    console.error('🐀 Error downloading image:', imageUrl, error);
    return { success: false, error: error.message };
  }
}

// Convert blob to base64
function blobToBase64(blob) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onloadend = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(blob);
  });
}

// Download multiple images
async function downloadImages(imageUrls) {
  const results = [];
  for (let i = 0; i < imageUrls.length && i < 10; i++) { // Etsy max 10 images
    const result = await downloadImageAsBase64(imageUrls[i]);
    if (result.success) {
      results.push({
        base64: result.base64,
        mimeType: result.mimeType,
        extension: result.extension,
        index: i
      });
    }
    // Small delay between downloads to avoid rate limiting
    await new Promise(r => setTimeout(r, 200));
  }
  return results;
}

// Download images and save to Downloads folder using chrome.downloads API
async function downloadAndSaveImages(imageUrls, productTitle = '') {
  let count = 0;
  
  // Create a clean filename from product title (first 30 chars, no special chars)
  let baseName = 'lesrats';
  if (productTitle) {
    baseName = productTitle
      .substring(0, 30)
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_+|_+$/g, '');
    if (!baseName) baseName = 'lesrats';
  }
  
  for (let i = 0; i < imageUrls.length && i < 10; i++) {
    const url = imageUrls[i];
    const filename = `${baseName}_${String(i + 1).padStart(2, '0')}.jpg`;
    
    try {
      // Use chrome.downloads to save to Downloads folder
      await chrome.downloads.download({
        url: url,
        filename: filename,
        saveAs: false // Don't prompt, save directly
      });
      count++;
      console.log('🐀 Downloaded:', filename);
      
      // Small delay between downloads
      await new Promise(r => setTimeout(r, 300));
    } catch (e) {
      console.error('🐀 Failed to download:', url, e);
    }
  }
  
  return { success: count > 0, count: count, filename: baseName };
}

// Fonction d'import vers le serveur
async function handleImport(productData, apiUrl, apiToken) {
  // Remove trailing slash from URL
  const baseUrl = apiUrl.replace(/\/+$/, '');
  
  try {
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    };

    if (apiToken) {
      headers['Authorization'] = `Bearer ${apiToken}`;
    }

    const response = await fetch(`${baseUrl}/api/extension/import`, {
      method: 'POST',
      headers: headers,
      body: JSON.stringify(productData)
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || data.error || 'Erreur serveur');
    }

    return {
      success: true,
      product_id: data.product_id,
      product_url: data.product_url
    };
  } catch (error) {
    console.error('Erreur d\'import:', error);
    return {
      success: false,
      error: error.message
    };
  }
}

// Mettre à jour le badge de l'extension selon la page
chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
  if (changeInfo.status === 'complete' && tab.url) {
    if (tab.url.includes('aliexpress.com/item/')) {
      // On est sur une page produit - afficher badge vert
      chrome.action.setBadgeText({ text: '✓', tabId: tabId });
      chrome.action.setBadgeBackgroundColor({ color: '#22c55e', tabId: tabId });
    } else if (tab.url.includes('aliexpress.com')) {
      // Sur AliExpress mais pas sur un produit
      chrome.action.setBadgeText({ text: '', tabId: tabId });
    } else {
      // Pas sur AliExpress
      chrome.action.setBadgeText({ text: '', tabId: tabId });
    }
  }
});
