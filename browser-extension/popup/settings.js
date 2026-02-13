// Settings page for LesRats extension

document.addEventListener('DOMContentLoaded', async () => {
  // Load saved settings
  const saved = await chrome.storage.local.get(['apiUrl', 'selectedShopId', 'devMode', 'devApiUrl']);
  const apiToken = await SecureStorage.getSecure('apiToken');
  const devApiToken = await SecureStorage.getSecure('devApiToken');

  if (saved.apiUrl) {
    document.getElementById('api-url').value = saved.apiUrl;
  }
  if (apiToken) {
    document.getElementById('api-token').value = apiToken;
  }

  // Dev mode
  const devToggle = document.getElementById('dev-mode-toggle');
  const devFields = document.getElementById('dev-mode-fields');
  devToggle.checked = !!saved.devMode;
  devFields.style.display = saved.devMode ? 'block' : 'none';
  if (saved.devApiUrl) {
    document.getElementById('dev-api-url').value = saved.devApiUrl;
  }
  if (devApiToken) {
    document.getElementById('dev-api-token').value = devApiToken;
  }

  devToggle.addEventListener('change', () => {
    devFields.style.display = devToggle.checked ? 'block' : 'none';
  });

  // Load shops if we have URL and token
  if (saved.apiUrl && apiToken) {
    loadShops(saved.selectedShopId);
  }

  // Event listeners
  document.getElementById('btn-back').addEventListener('click', goBack);
  document.getElementById('btn-test').addEventListener('click', testConnection);
  document.getElementById('btn-test-dev').addEventListener('click', testDevConnection);
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

async function testDevConnection() {
  const devUrl = document.getElementById('dev-api-url').value.trim().replace(/\/+$/, '');
  const devToken = document.getElementById('dev-api-token').value.trim();
  const statusDiv = document.getElementById('dev-connection-status');

  if (!devUrl) {
    showStatus(statusDiv, 'error', 'Entrez une URL locale');
    return;
  }

  showStatus(statusDiv, 'pending', 'Test en cours...');

  try {
    const pingResponse = await fetch(`${devUrl}/api/extension/ping`);
    if (!pingResponse.ok) {
      showStatus(statusDiv, 'error', `Serveur inaccessible (${pingResponse.status})`);
      return;
    }

    if (devToken) {
      const shopsResponse = await fetch(`${devUrl}/api/extension/shops`, {
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${devToken}` }
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
      } else {
        showStatus(statusDiv, 'error', data.error || 'Erreur inconnue');
      }
    } else {
      showStatus(statusDiv, 'success', 'Serveur accessible (ajoutez un token)');
    }
  } catch (error) {
    console.error('Dev connection test error:', error);
    showStatus(statusDiv, 'error', 'Impossible de contacter le serveur local');
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
  const devMode = document.getElementById('dev-mode-toggle').checked;
  const devApiUrl = document.getElementById('dev-api-url').value.trim().replace(/\/+$/, '');
  const devApiToken = document.getElementById('dev-api-token').value.trim();

  // Save URL, shop selection, and dev mode
  await chrome.storage.local.set({
    apiUrl: apiUrl,
    selectedShopId: selectedShopId,
    devMode: devMode,
    devApiUrl: devApiUrl
  });

  // Save tokens encrypted
  if (apiToken) {
    await SecureStorage.setSecure('apiToken', apiToken);
  }
  if (devApiToken) {
    await SecureStorage.setSecure('devApiToken', devApiToken);
  }

  // Show save confirmation
  const saveStatus = document.getElementById('save-status');
  saveStatus.classList.add('show');
  setTimeout(() => {
    saveStatus.classList.remove('show');
  }, 2000);
}
