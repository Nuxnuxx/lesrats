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

// États de l'interface - Printables
const printablesStates = {
  NOT_PRINTABLES: 'state-not-printables',
  EXTRACTING: 'state-printables-extracting',
  READY: 'state-printables-ready',
  SENDING: 'state-printables-sending',
  SUCCESS: 'state-printables-success',
  ERROR: 'state-printables-error'
};

let currentProduct = null;
let currentPrintablesProduct = null;
let importedProductUrl = null;
let importedPrintablesProductUrl = null;
let currentTab = 'import';
let shops = [];

// Get active API config (dev or prod)
async function getApiConfig() {
  const saved = await chrome.storage.local.get(['apiUrl', 'devMode', 'devApiUrl']);
  if (saved.devMode) {
    const devToken = await SecureStorage.getSecure('devApiToken');
    return { apiUrl: saved.devApiUrl || 'http://localhost:8000', apiToken: devToken, isDev: true };
  }
  const apiToken = await SecureStorage.getSecure('apiToken');
  return { apiUrl: saved.apiUrl || 'http://localhost:8000', apiToken, isDev: false };
}

// Initialisation
document.addEventListener('DOMContentLoaded', async () => {
  // Show DEV badge if dev mode is active
  const { isDev } = await getApiConfig();
  const devBadge = document.getElementById('dev-badge');
  if (devBadge) devBadge.style.display = isDev ? 'inline-block' : 'none';

  // Charger les paramètres sauvegardés
  const saved = await chrome.storage.local.get(['lastEtsyCategoryName', 'lastEtsyIsDigital']);
  
  if (saved.lastEtsyCategoryName) {
    document.getElementById('etsy-category-name').value = saved.lastEtsyCategoryName;
  }
  if (saved.lastEtsyIsDigital) {
    document.getElementById('etsy-is-digital').checked = saved.lastEtsyIsDigital;
  }

  // Event listeners - Settings
  document.getElementById('btn-settings').addEventListener('click', openSettings);

  // Event listeners - Import
  document.getElementById('btn-import').addEventListener('click', importProduct);
  document.getElementById('btn-retry').addEventListener('click', retryExtraction);
  document.getElementById('btn-import-another').addEventListener('click', retryExtraction);
  document.getElementById('btn-view-product').addEventListener('click', viewProduct);

  // Event listeners - Etsy
  document.getElementById('btn-publish-etsy').addEventListener('click', publishToEtsy);
  document.getElementById('btn-etsy-retry').addEventListener('click', resetEtsyState);
  document.getElementById('btn-etsy-another').addEventListener('click', resetEtsyState);

  // Event listeners - Printables
  document.getElementById('btn-import-printables').addEventListener('click', importPrintablesProduct);
  document.getElementById('btn-printables-retry').addEventListener('click', retryPrintablesExtraction);
  document.getElementById('btn-import-another-printables').addEventListener('click', retryPrintablesExtraction);
  document.getElementById('btn-view-printables-product').addEventListener('click', viewPrintablesProduct);

  // Event listeners - Tabs
  document.getElementById('tab-import').addEventListener('click', () => switchTab('import'));
  document.getElementById('tab-printables').addEventListener('click', () => switchTab('printables'));
  document.getElementById('tab-etsy').addEventListener('click', () => switchTab('etsy'));

  // Save shop selection when changed
  document.getElementById('shop-select').addEventListener('change', saveShopSelection);

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

// Open settings page
function openSettings() {
  window.location.href = 'settings.html';
}

// Sauvegarder la selection de boutique
async function saveShopSelection() {
  await chrome.storage.local.set({
    selectedShopId: document.getElementById('shop-select').value
  });
}

// Charger la liste des boutiques
async function loadShops() {
  const { apiUrl, apiToken } = await getApiConfig();

  const shopSelect = document.getElementById('shop-select');

  if (!apiUrl || !apiToken) {
    shopSelect.innerHTML = '<option value="">Configurez dans Parametres</option>';
    return;
  }

  shopSelect.innerHTML = '<option value="">Chargement...</option>';

  try {
    const response = await chrome.runtime.sendMessage({
      action: 'fetchShops',
      apiUrl: apiUrl,
      apiToken: apiToken
    });
    
    if (response.success && response.shops) {
      shops = response.shops;
      
      // Get saved selection
      const saved = await chrome.storage.local.get(['selectedShopId']);
      
      // Populate dropdown
      shopSelect.innerHTML = shops.map(shop => 
        `<option value="${shop.id}" ${saved.selectedShopId == shop.id ? 'selected' : ''}>${shop.name}${shop.platform ? ` (${shop.platform})` : ''}</option>`
      ).join('');
      
      // If no saved selection and shops exist, save the first one
      if (!saved.selectedShopId && shops.length > 0) {
        await chrome.storage.local.set({ selectedShopId: shops[0].id.toString() });
      }
    } else {
      // Show error message - could be auth error
      const errorMsg = response.error || 'Erreur de chargement';
      shopSelect.innerHTML = `<option value="">${errorMsg}</option>`;
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
  document.getElementById('tab-printables').classList.toggle('active', tab === 'printables');
  document.getElementById('tab-etsy').classList.toggle('active', tab === 'etsy');

  // Show/hide sections
  document.getElementById('section-import').classList.toggle('hidden', tab !== 'import');
  document.getElementById('section-printables').classList.toggle('hidden', tab !== 'printables');
  document.getElementById('section-etsy').classList.toggle('hidden', tab !== 'etsy');

  // Check page when switching to printables tab
  if (tab === 'printables') {
    checkPrintablesPage();
  }
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

    // Envoyer un message au content script pour extraire les données (without country prices initially)
    const response = await chrome.tabs.sendMessage(tab.id, { 
      action: 'extractProduct',
      includeCountryPrices: false 
    });

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

  const { apiUrl, apiToken } = await getApiConfig();
  const shopId = document.getElementById('shop-select').value;
  const includeCountryPrices = document.getElementById('include-country-prices').checked;

  if (!apiUrl || !apiToken) {
    showError('Configurez URL et token dans Parametres');
    return;
  }

  if (!shopId) {
    showError('Veuillez selectionner une boutique');
    return;
  }

  // If country prices are requested, scrape them first
  if (includeCountryPrices) {
    try {
      showCountryPriceProgress(true);
      updateCountryPriceProgress(0, 6, 'Demarrage...');
      
      const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
      
      // Listen for progress updates
      const progressListener = (message) => {
        if (message.type === 'COUNTRY_PRICE_PROGRESS' && message.progress) {
          updateCountryPriceProgress(
            message.progress.current, 
            message.progress.total, 
            `${message.progress.country} (${message.progress.code})`
          );
        }
      };
      chrome.runtime.onMessage.addListener(progressListener);
      
      // Scrape country prices
      const response = await chrome.tabs.sendMessage(tab.id, { 
        action: 'scrapeCountryPrices'
      });
      
      // Remove listener
      chrome.runtime.onMessage.removeListener(progressListener);
      
      if (response && response.success && response.countryPrices) {
        currentProduct.country_prices = response.countryPrices;
        console.log('Country prices scraped:', response.countryPrices);
      } else {
        console.warn('Failed to scrape country prices:', response?.error);
      }
      
      showCountryPriceProgress(false);
    } catch (error) {
      console.error('Error scraping country prices:', error);
      showCountryPriceProgress(false);
      // Continue with import even if country prices fail
    }
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

    // Remove trailing slash from URL
    const baseUrl = apiUrl.replace(/\/+$/, '');

    const response = await fetch(`${baseUrl}/api/extension/import`, {
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

// Show/hide country price progress
function showCountryPriceProgress(show) {
  const progressDiv = document.getElementById('country-price-progress');
  if (progressDiv) {
    progressDiv.classList.toggle('hidden', !show);
  }
}

// Update country price progress
function updateCountryPriceProgress(current, total, countryName) {
  const fill = document.getElementById('progress-fill');
  const text = document.getElementById('progress-text');
  
  if (fill) {
    const percent = (current / total) * 100;
    fill.style.width = `${percent}%`;
  }
  
  if (text) {
    text.textContent = `Scraping ${countryName}... (${current}/${total})`;
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
  const categoryName = document.getElementById('etsy-category-name').value.trim();
  const isDigital = document.getElementById('etsy-is-digital').checked;
  
  if (!categoryName) {
    showEtsyError('Veuillez entrer le nom de la categorie');
    return;
  }
  
  showEtsyState(etsyStates.LOADING);
  
  try {
    const result = await chrome.runtime.sendMessage({
      action: 'openEtsyWithCategory',
      categoryName: categoryName,
      isDigital: isDigital
    });
    
    if (result.success) {
      showEtsyState(etsyStates.SUCCESS);
    } else {
      showEtsyError(result.error || 'Erreur');
    }
  } catch (error) {
    showEtsyError('Erreur lors de l\'ouverture d\'Etsy.');
  }
}

// Afficher une erreur Etsy
function showEtsyError(message) {
  document.getElementById('etsy-error-message').textContent = message;
  showEtsyState(etsyStates.ERROR);
}

// Reset Etsy state
function resetEtsyState() {
  document.getElementById('etsy-category-name').value = '';
  showEtsyState(etsyStates.READY);
}

// ============== PRINTABLES FUNCTIONS ==============

// Afficher un état (Printables section)
function showPrintablesState(stateId) {
  Object.values(printablesStates).forEach(id => {
    const el = document.getElementById(id);
    if (el) el.classList.add('hidden');
  });
  const el = document.getElementById(stateId);
  if (el) el.classList.remove('hidden');
}

// Vérifier si on est sur Printables
async function checkPrintablesPage() {
  try {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });

    if (!tab.url || !tab.url.includes('printables.com/model/')) {
      showPrintablesState(printablesStates.NOT_PRINTABLES);
      return;
    }

    // On est sur une page modèle Printables
    showPrintablesState(printablesStates.EXTRACTING);

    // Envoyer un message au content script pour extraire les données
    const response = await chrome.tabs.sendMessage(tab.id, {
      action: 'extractPrintablesProduct'
    });

    if (response && response.success) {
      currentPrintablesProduct = response.data;
      displayPrintablesProduct(currentPrintablesProduct);
      showPrintablesState(printablesStates.READY);
      // Load shops for Printables section
      loadPrintablesShops();
    } else {
      showPrintablesError(response?.error || 'Impossible d\'extraire les données du modèle');
    }
  } catch (error) {
    console.error('Erreur Printables:', error);
    showPrintablesError('Erreur de communication avec la page. Rechargez la page et réessayez.');
  }
}

// Afficher les infos du produit Printables
function displayPrintablesProduct(product) {
  document.getElementById('printables-product-title').textContent = product.title || 'Sans titre';
  document.getElementById('printables-product-price').textContent = product.price ? `${product.price} €` : 'Gratuit';

  const imgElement = document.getElementById('printables-product-image');
  if (product.images && product.images.length > 0) {
    imgElement.src = product.images[0];
  } else {
    imgElement.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23666"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
  }
}

// Charger les boutiques pour Printables
async function loadPrintablesShops() {
  const { apiUrl, apiToken } = await getApiConfig();
  const saved = await chrome.storage.local.get(['selectedShopId']);

  const shopSelect = document.getElementById('printables-shop-select');

  if (!apiUrl || !apiToken) {
    shopSelect.innerHTML = '<option value="">Configurez dans Parametres</option>';
    return;
  }

  shopSelect.innerHTML = '<option value="">Chargement...</option>';

  try {
    const response = await chrome.runtime.sendMessage({
      action: 'fetchShops',
      apiUrl: apiUrl,
      apiToken: apiToken
    });

    if (response.success && response.shops) {
      shopSelect.innerHTML = response.shops.map(shop =>
        `<option value="${shop.id}" ${saved.selectedShopId == shop.id ? 'selected' : ''}>${shop.name}</option>`
      ).join('');
    } else {
      const errorMsg = response.error || 'Erreur de chargement';
      shopSelect.innerHTML = `<option value="">${errorMsg}</option>`;
    }
  } catch (error) {
    console.error('Error loading shops for Printables:', error);
    shopSelect.innerHTML = '<option value="">Serveur inaccessible</option>';
  }
}

// Importer le produit Printables
async function importPrintablesProduct() {
  if (!currentPrintablesProduct) {
    showPrintablesError('Aucun modele a importer');
    return;
  }

  const { apiUrl, apiToken } = await getApiConfig();
  const shopId = document.getElementById('printables-shop-select').value;

  if (!apiUrl || !apiToken) {
    showPrintablesError('Configurez URL et token dans Parametres');
    return;
  }

  if (!shopId) {
    showPrintablesError('Veuillez selectionner une boutique');
    return;
  }

  showPrintablesState(printablesStates.SENDING);

  try {
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${apiToken}`
    };

    // Add shop_id to product data
    const productData = {
      ...currentPrintablesProduct,
      shop_id: parseInt(shopId)
    };

    // Remove trailing slash from URL
    const baseUrl = apiUrl.replace(/\/+$/, '');

    const response = await fetch(`${baseUrl}/api/extension/import`, {
      method: 'POST',
      headers: headers,
      body: JSON.stringify(productData)
    });

    const data = await response.json();

    if (response.ok && data.success) {
      importedPrintablesProductUrl = data.product_url || `${baseUrl}/products/${data.product_id}/edit`;
      showPrintablesState(printablesStates.SUCCESS);
    } else {
      showPrintablesError(data.message || data.error || 'Erreur lors de l\'import');
    }
  } catch (error) {
    console.error('Erreur d\'import Printables:', error);
    showPrintablesError('Impossible de se connecter au serveur.');
  }
}

// Voir le produit Printables importé
function viewPrintablesProduct() {
  if (importedPrintablesProductUrl) {
    chrome.tabs.create({ url: importedPrintablesProductUrl });
  }
}

// Réessayer l'extraction Printables
function retryPrintablesExtraction() {
  currentPrintablesProduct = null;
  importedPrintablesProductUrl = null;
  checkPrintablesPage();
}

// Afficher une erreur Printables
function showPrintablesError(message) {
  document.getElementById('printables-error-message').textContent = message;
  showPrintablesState(printablesStates.ERROR);
}
