// États de l'interface - Import
const states = {
  NOT_ALIEXPRESS: 'state-not-aliexpress',
  EXTRACTING: 'state-extracting',
  READY: 'state-ready',
  SENDING: 'state-sending',
  SUCCESS: 'state-success',
  ERROR: 'state-error'
};

// États de l'interface - Etsy
const etsyStates = {
  READY: 'state-etsy-ready',
  LOADING: 'state-etsy-loading',
  SUCCESS: 'state-etsy-success',
  ERROR: 'state-etsy-error'
};

let currentProduct = null;
let importedProductUrl = null;
let currentTab = 'import';
let shops = [];

// Initialisation
document.addEventListener('DOMContentLoaded', async () => {
  // Charger les paramètres sauvegardés
  const saved = await chrome.storage.local.get(['apiUrl', 'apiToken', 'lastEtsyShopName']);
  if (saved.apiUrl) {
    document.getElementById('api-url').value = saved.apiUrl;
  } else {
    document.getElementById('api-url').value = 'http://localhost:8000';
  }
  if (saved.apiToken) {
    document.getElementById('api-token').value = saved.apiToken;
  }
  if (saved.lastEtsyShopName) {
    document.getElementById('etsy-shop-name').value = saved.lastEtsyShopName;
  }

  // Event listeners - Import
  document.getElementById('btn-import').addEventListener('click', importProduct);
  document.getElementById('btn-retry').addEventListener('click', retryExtraction);
  document.getElementById('btn-import-another').addEventListener('click', retryExtraction);
  document.getElementById('btn-view-product').addEventListener('click', viewProduct);

  // Event listeners - Etsy
  document.getElementById('btn-publish-etsy').addEventListener('click', publishToEtsy);
  document.getElementById('btn-etsy-retry').addEventListener('click', resetEtsyState);
  document.getElementById('btn-etsy-another').addEventListener('click', resetEtsyState);

  // Event listeners - Tabs
  document.getElementById('tab-import').addEventListener('click', () => switchTab('import'));
  document.getElementById('tab-etsy').addEventListener('click', () => switchTab('etsy'));

  // Sauvegarder les paramètres quand ils changent
  document.getElementById('api-url').addEventListener('change', saveSettings);
  document.getElementById('api-token').addEventListener('change', saveSettings);
  document.getElementById('shop-select').addEventListener('change', saveSettings);
  document.getElementById('etsy-shop-name').addEventListener('change', saveEtsySettings);

  // Load shops when API URL changes
  document.getElementById('api-url').addEventListener('change', loadShops);

  // Load shops on startup
  loadShops();

  // Configurer le raccourci clavier
  document.getElementById('configure-shortcut').addEventListener('click', (e) => {
    e.preventDefault();
    chrome.tabs.create({ url: 'chrome://extensions/shortcuts' });
  });

  // Vérifier si on est sur AliExpress et extraire le produit
  checkCurrentPage();
});

// Sauvegarder les paramètres
async function saveSettings() {
  await chrome.storage.local.set({
    apiUrl: document.getElementById('api-url').value,
    apiToken: document.getElementById('api-token').value,
    selectedShopId: document.getElementById('shop-select').value
  });
}

// Charger la liste des boutiques
async function loadShops() {
  const apiUrl = document.getElementById('api-url').value.trim() || 'http://localhost:8000';
  const shopSelect = document.getElementById('shop-select');
  
  shopSelect.innerHTML = '<option value="">Chargement...</option>';
  
  try {
    const response = await chrome.runtime.sendMessage({
      action: 'fetchShops',
      apiUrl: apiUrl
    });
    
    if (response.success && response.shops) {
      shops = response.shops;
      
      // Get saved selection
      const saved = await chrome.storage.local.get(['selectedShopId']);
      
      // Populate dropdown
      shopSelect.innerHTML = shops.map(shop => 
        `<option value="${shop.id}" ${saved.selectedShopId == shop.id ? 'selected' : ''}>${shop.name} (${shop.platform})</option>`
      ).join('');
      
      // If no saved selection and shops exist, save the first one
      if (!saved.selectedShopId && shops.length > 0) {
        await chrome.storage.local.set({ selectedShopId: shops[0].id.toString() });
      }
    } else {
      shopSelect.innerHTML = '<option value="">Erreur de chargement</option>';
    }
  } catch (error) {
    console.error('Error loading shops:', error);
    shopSelect.innerHTML = '<option value="">Serveur inaccessible</option>';
  }
}

// Sauvegarder les paramètres Etsy
async function saveEtsySettings() {
  await chrome.storage.local.set({
    lastEtsyShopName: document.getElementById('etsy-shop-name').value
  });
}

// Changer d'onglet
function switchTab(tab) {
  currentTab = tab;
  
  // Update tab buttons
  document.getElementById('tab-import').classList.toggle('active', tab === 'import');
  document.getElementById('tab-etsy').classList.toggle('active', tab === 'etsy');
  
  // Show/hide sections
  document.getElementById('section-import').classList.toggle('hidden', tab !== 'import');
  document.getElementById('section-etsy').classList.toggle('hidden', tab !== 'etsy');
}

// Afficher un état (Import section)
function showState(stateId) {
  Object.values(states).forEach(id => {
    const el = document.getElementById(id);
    if (el) el.classList.add('hidden');
  });
  const el = document.getElementById(stateId);
  if (el) el.classList.remove('hidden');
}

// Afficher un état (Etsy section)
function showEtsyState(stateId) {
  Object.values(etsyStates).forEach(id => {
    const el = document.getElementById(id);
    if (el) el.classList.add('hidden');
  });
  const el = document.getElementById(stateId);
  if (el) el.classList.remove('hidden');
}

// Vérifier la page actuelle
async function checkCurrentPage() {
  try {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });

    if (!tab.url || !tab.url.includes('aliexpress.com/item/')) {
      showState(states.NOT_ALIEXPRESS);
      return;
    }

    // On est sur une page produit AliExpress
    showState(states.EXTRACTING);

    // Envoyer un message au content script pour extraire les données
    const response = await chrome.tabs.sendMessage(tab.id, { action: 'extractProduct' });

    if (response && response.success) {
      currentProduct = response.data;
      displayProduct(currentProduct);
      showState(states.READY);
    } else {
      showError(response?.error || 'Impossible d\'extraire les données du produit');
    }
  } catch (error) {
    console.error('Erreur:', error);
    showError('Erreur de communication avec la page. Rechargez la page et réessayez.');
  }
}

// Afficher les infos du produit
function displayProduct(product) {
  document.getElementById('product-title').textContent = product.title || 'Sans titre';
  document.getElementById('product-price').textContent = product.price ? `${product.price} €` : 'Prix non disponible';

  const imgElement = document.getElementById('product-image');
  if (product.images && product.images.length > 0) {
    imgElement.src = product.images[0];
  } else {
    imgElement.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23666"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
  }
}

// Importer le produit
async function importProduct() {
  if (!currentProduct) {
    showError('Aucun produit à importer');
    return;
  }

  const apiUrl = document.getElementById('api-url').value.trim();
  const apiToken = document.getElementById('api-token').value.trim();
  const shopId = document.getElementById('shop-select').value;

  if (!apiUrl) {
    showError('Veuillez entrer l\'URL de votre serveur LesRats');
    return;
  }

  if (!shopId) {
    showError('Veuillez sélectionner une boutique');
    return;
  }

  showState(states.SENDING);

  try {
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    };

    if (apiToken) {
      headers['Authorization'] = `Bearer ${apiToken}`;
    }

    // Add shop_id to product data
    const productData = {
      ...currentProduct,
      shop_id: parseInt(shopId)
    };

    const response = await fetch(`${apiUrl}/api/extension/import`, {
      method: 'POST',
      headers: headers,
      body: JSON.stringify(productData)
    });

    const data = await response.json();

    if (response.ok && data.success) {
      importedProductUrl = data.product_url || `${apiUrl}/products/${data.product_id}/edit`;
      showState(states.SUCCESS);
    } else {
      showError(data.message || data.error || 'Erreur lors de l\'import');
    }
  } catch (error) {
    console.error('Erreur d\'import:', error);
    showError('Impossible de se connecter au serveur. Vérifiez l\'URL et que le serveur est démarré.');
  }
}

// Voir le produit importé
function viewProduct() {
  if (importedProductUrl) {
    chrome.tabs.create({ url: importedProductUrl });
  }
}

// Réessayer l'extraction
function retryExtraction() {
  currentProduct = null;
  importedProductUrl = null;
  checkCurrentPage();
}

// Afficher une erreur
function showError(message) {
  document.getElementById('error-message').textContent = message;
  showState(states.ERROR);
}

// ============== ETSY FUNCTIONS ==============

// Publier sur Etsy
async function publishToEtsy() {
  const productId = document.getElementById('etsy-product-id').value.trim();
  const shopName = document.getElementById('etsy-shop-name').value.trim();
  
  if (!productId) {
    showEtsyError('Veuillez entrer l\'ID du produit');
    return;
  }
  
  if (!shopName) {
    showEtsyError('Veuillez entrer le nom de la boutique Etsy');
    return;
  }
  
  // Get API URL
  const saved = await chrome.storage.local.get(['apiUrl']);
  const apiUrl = saved.apiUrl || 'http://localhost:8000';
  
  showEtsyState(etsyStates.LOADING);
  
  try {
    // First, verify the product exists by fetching its data via service worker
    const data = await chrome.runtime.sendMessage({
      action: 'fetchEtsyData',
      apiUrl: apiUrl,
      productId: productId
    });
    
    if (!data.success) {
      showEtsyError(`Produit non trouve: ${data.message || data.error}`);
      return;
    }
    
    // Store the pending product info in extension storage
    await chrome.storage.local.set({
      pendingEtsyProduct: productId,
      pendingEtsyShopName: shopName,
      apiUrl: apiUrl,
      lastEtsyShopName: shopName
    });
    
    // Open Etsy listing editor
    const etsyUrl = 'https://www.etsy.com/your/shops/me/listing-editor/create';
    chrome.tabs.create({ url: etsyUrl });
    
    showEtsyState(etsyStates.SUCCESS);
    
  } catch (error) {
    console.error('Erreur Etsy:', error);
    showEtsyError('Erreur de connexion. Verifiez que le serveur LesRats est demarre.');
  }
}

// Afficher une erreur Etsy
function showEtsyError(message) {
  document.getElementById('etsy-error-message').textContent = message;
  showEtsyState(etsyStates.ERROR);
}

// Reset Etsy state
function resetEtsyState() {
  document.getElementById('etsy-product-id').value = '';
  showEtsyState(etsyStates.READY);
}
