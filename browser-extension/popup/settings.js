// Settings page for LesRats extension

document.addEventListener('DOMContentLoaded', async () => {
  // Load saved settings
  const saved = await chrome.storage.local.get(['apiUrl', 'selectedShopId']);
  const apiToken = await SecureStorage.getSecure('apiToken');
  
  if (saved.apiUrl) {
    document.getElementById('api-url').value = saved.apiUrl;
  }
  if (apiToken) {
    document.getElementById('api-token').value = apiToken;
  }
  
  // Load shops if we have URL and token
  if (saved.apiUrl && apiToken) {
    loadShops(saved.selectedShopId);
  }
  
  // Event listeners
  document.getElementById('btn-back').addEventListener('click', goBack);
  document.getElementById('btn-test').addEventListener('click', testConnection);
  document.getElementById('btn-save').addEventListener('click', saveSettings);
  
  // Auto-load shops when URL or token changes
  document.getElementById('api-url').addEventListener('blur', () => loadShops());
  document.getElementById('api-token').addEventListener('blur', () => loadShops());
});

function goBack() {
  window.location.href = 'popup.html';
}

async function testConnection() {
  const apiUrl = document.getElementById('api-url').value.trim().replace(/\/+$/, '');
  const apiToken = document.getElementById('api-token').value.trim();
  const statusDiv = document.getElementById('connection-status');
  
  if (!apiUrl) {
    showStatus(statusDiv, 'error', 'Entrez une URL');
    return;
  }
  
  showStatus(statusDiv, 'pending', 'Test en cours...');
  
  try {
    // Test ping endpoint (no auth required)
    const pingResponse = await fetch(`${apiUrl}/api/extension/ping`);
    
    if (!pingResponse.ok) {
      showStatus(statusDiv, 'error', `Serveur inaccessible (${pingResponse.status})`);
      return;
    }
    
    // Test authenticated endpoint if token provided
    if (apiToken) {
      const shopsResponse = await fetch(`${apiUrl}/api/extension/shops`, {
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${apiToken}`
        }
      });
      
      if (shopsResponse.status === 401) {
        showStatus(statusDiv, 'error', 'Token invalide');
        return;
      }
      
      if (!shopsResponse.ok) {
        showStatus(statusDiv, 'error', `Erreur API (${shopsResponse.status})`);
        return;
      }
      
      const data = await shopsResponse.json();
      if (data.success) {
        showStatus(statusDiv, 'success', `Connecte! ${data.shops.length} boutique(s)`);
        // Refresh shops dropdown
        loadShops();
      } else {
        showStatus(statusDiv, 'error', data.error || 'Erreur inconnue');
      }
    } else {
      showStatus(statusDiv, 'success', 'Serveur accessible (ajoutez un token)');
    }
  } catch (error) {
    console.error('Connection test error:', error);
    showStatus(statusDiv, 'error', 'Impossible de contacter le serveur');
  }
}

function showStatus(element, type, message) {
  element.innerHTML = `<div class="status-indicator ${type}">${message}</div>`;
}

async function loadShops(selectedId = null) {
  const apiUrl = document.getElementById('api-url').value.trim().replace(/\/+$/, '');
  const apiToken = document.getElementById('api-token').value.trim();
  const shopSelect = document.getElementById('default-shop');
  
  if (!apiUrl || !apiToken) {
    shopSelect.innerHTML = '<option value="">Configurez URL et token</option>';
    return;
  }
  
  shopSelect.innerHTML = '<option value="">Chargement...</option>';
  
  try {
    const response = await fetch(`${apiUrl}/api/extension/shops`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${apiToken}`
      }
    });
    
    if (!response.ok) {
      shopSelect.innerHTML = '<option value="">Erreur de chargement</option>';
      return;
    }
    
    const data = await response.json();
    
    if (data.success && data.shops && data.shops.length > 0) {
      // Get saved selection if not provided
      if (!selectedId) {
        const saved = await chrome.storage.local.get(['selectedShopId']);
        selectedId = saved.selectedShopId;
      }
      
      shopSelect.innerHTML = data.shops.map(shop => 
        `<option value="${shop.id}" ${selectedId == shop.id ? 'selected' : ''}>${shop.name}</option>`
      ).join('');
    } else {
      shopSelect.innerHTML = '<option value="">Aucune boutique</option>';
    }
  } catch (error) {
    console.error('Error loading shops:', error);
    shopSelect.innerHTML = '<option value="">Erreur</option>';
  }
}

async function saveSettings() {
  const apiUrl = document.getElementById('api-url').value.trim().replace(/\/+$/, '');
  const apiToken = document.getElementById('api-token').value.trim();
  const selectedShopId = document.getElementById('default-shop').value;
  
  // Save URL and shop selection normally
  await chrome.storage.local.set({
    apiUrl: apiUrl,
    selectedShopId: selectedShopId
  });
  
  // Save token encrypted
  if (apiToken) {
    await SecureStorage.setSecure('apiToken', apiToken);
  }
  
  // Show save confirmation
  const saveStatus = document.getElementById('save-status');
  saveStatus.classList.add('show');
  setTimeout(() => {
    saveStatus.classList.remove('show');
  }, 2000);
}
