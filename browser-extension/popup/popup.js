// Minimal popup — import is handled by floating panels on product pages

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

document.addEventListener('DOMContentLoaded', async () => {
  // Show DEV badge if dev mode is active
  const { isDev } = await getApiConfig();
  const devBadge = document.getElementById('dev-badge');
  if (devBadge) devBadge.style.display = isDev ? 'inline-block' : 'none';

  // Settings
  document.getElementById('btn-settings').addEventListener('click', () => {
    window.location.href = 'settings.html';
  });

  // Configure shortcut
  document.getElementById('configure-shortcut').addEventListener('click', (e) => {
    e.preventDefault();
    chrome.tabs.create({ url: 'chrome://extensions/shortcuts' });
  });
});
