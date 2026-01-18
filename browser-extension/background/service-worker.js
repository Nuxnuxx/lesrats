// Service Worker pour l'extension LesRats

// Écouter l'installation de l'extension
chrome.runtime.onInstalled.addListener((details) => {
  if (details.reason === 'install') {
    console.log('🐀 LesRats Extension installée!');

    // Définir les paramètres par défaut
    chrome.storage.local.set({
      apiUrl: 'http://localhost:8000',
      apiToken: ''
    });
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
});

// Fonction d'import vers le serveur
async function handleImport(productData, apiUrl, apiToken) {
  try {
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    };

    if (apiToken) {
      headers['Authorization'] = `Bearer ${apiToken}`;
    }

    const response = await fetch(`${apiUrl}/api/extension/import`, {
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
