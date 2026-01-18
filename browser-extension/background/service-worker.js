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

      // Récupérer les paramètres
      const settings = await chrome.storage.local.get(['apiUrl', 'apiToken']);
      const apiUrl = settings.apiUrl || 'http://localhost:8000';
      const apiToken = settings.apiToken || '';

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
