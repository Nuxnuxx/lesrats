// Minimal popup — shows connection status + links

async function getApiConfig() {
  const saved = await chrome.storage.local.get(['apiUrl', 'devMode', 'devApiUrl']);
  if (saved.devMode) {
    const devToken = await SecureStorage.getSecure('devApiToken');
    return { apiUrl: saved.devApiUrl || 'http://localhost:8000', apiToken: devToken, isDev: true };
  }
  const apiToken = await SecureStorage.getSecure('apiToken');
  return { apiUrl: saved.apiUrl || '', apiToken, isDev: false };
}

document.addEventListener('DOMContentLoaded', async () => {
  const { apiUrl, apiToken, isDev } = await getApiConfig();

  // DEV badge
  const devBadge = document.getElementById('dev-badge');
  if (devBadge) devBadge.style.display = isDev ? 'inline-block' : 'none';

  // Connection status
  if (apiUrl && apiToken) {
    document.getElementById('status-connected').classList.remove('hidden');
    document.getElementById('status-url').textContent = apiUrl;
  } else {
    document.getElementById('status-disconnected').classList.remove('hidden');
  }

  // Dashboard links
  const baseUrl = apiUrl || 'http://localhost:8000';
  document.getElementById('link-dashboard').href = baseUrl + '/dashboard';
  document.getElementById('link-profile').href = baseUrl + '/profile';


});
