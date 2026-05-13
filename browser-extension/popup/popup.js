// Minimal popup — shows connection status + links, plus admin-only dev toggle

async function getApiConfig() {
  const saved = await chrome.storage.local.get(['apiUrl', 'devMode', 'devApiUrl', 'isAdmin']);
  if (saved.devMode) {
    const devToken = await SecureStorage.getSecure('devApiToken');
    return {
      apiUrl: saved.devApiUrl || 'http://localhost:8000',
      apiToken: devToken,
      isDev: true,
      isAdmin: !!saved.isAdmin,
    };
  }
  const apiToken = await SecureStorage.getSecure('apiToken');
  return {
    apiUrl: saved.apiUrl || '',
    apiToken,
    isDev: false,
    isAdmin: !!saved.isAdmin,
  };
}

document.addEventListener('DOMContentLoaded', async () => {
  const { apiUrl, apiToken, isDev, isAdmin } = await getApiConfig();

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

  // Admin-only dev toggle
  if (isAdmin) {
    await setupAdminDevToggle(isDev);
  }
});

async function setupAdminDevToggle(isDev) {
  const wrap = document.getElementById('admin-dev-toggle');
  const checkbox = document.getElementById('dev-mode-checkbox');
  const hint = document.getElementById('admin-dev-hint');
  if (!wrap || !checkbox) return;

  const devApiToken = await SecureStorage.getSecure('devApiToken');
  const hasDevToken = !!devApiToken;

  wrap.classList.remove('hidden');
  checkbox.checked = !!isDev;

  // If we have no dev token yet and we're not already in dev mode, disable + hint.
  if (!hasDevToken && !isDev) {
    checkbox.disabled = true;
    hint.classList.remove('hidden');
  }

  checkbox.addEventListener('change', async () => {
    await chrome.storage.local.set({ devMode: checkbox.checked });
    // Reload popup to reflect new active env (badge, status url, etc.)
    window.location.reload();
  });
}
