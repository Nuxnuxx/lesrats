// Content script for LesRats dashboard
// Handles: auto-connect extension, Etsy publish bridge

(async function() {
  'use strict';

  // ============== AUTO-CONNECT ==============

  // Read API URL from meta tag (present on all authenticated pages)
  const apiUrlMeta = document.querySelector('meta[name="lesrats-api-url"]');
  if (apiUrlMeta && apiUrlMeta.content) {
    // Always keep the API URL up to date
    await chrome.storage.local.set({ apiUrl: apiUrlMeta.content });
  }

  // Check if we're already connected (have a token)
  const apiToken = await getToken();
  const isConnected = !!apiToken;
  const extensionVersion = chrome.runtime.getManifest().version;

  // Listen for messages from the dashboard page
  window.addEventListener('message', async (event) => {
    if (event.source !== window) return;

    // Dashboard asks if extension is installed
    if (event.data?.type === 'LESRATS_PING') {
      window.postMessage({
        type: 'LESRATS_EXTENSION_PRESENT',
        connected: isConnected,
        version: extensionVersion,
      }, '*');
    }

    // Dashboard sends token after "Connect" button clicked
    if (event.data?.type === 'LESRATS_CONNECT') {
      const { token, apiUrl, isAdmin } = event.data;
      if (token && apiUrl) {
        try {
          // Determine if this is a dev URL (localhost / 127.0.0.1)
          const isDev = apiUrl.includes('localhost') || apiUrl.includes('127.0.0.1');
          const isAdminFlag = !!isAdmin;

          if (isDev) {
            await chrome.storage.local.set({ devMode: true, devApiUrl: apiUrl, isAdmin: isAdminFlag });
            await SecureStorage.setSecure('devApiToken', token);
          } else {
            await chrome.storage.local.set({ apiUrl: apiUrl, devMode: false, isAdmin: isAdminFlag });
            await SecureStorage.setSecure('apiToken', token);
          }

          console.log('\u{1F400} Extension connected to', apiUrl, isDev ? '(dev)' : '(prod)');
          window.postMessage({ type: 'LESRATS_CONNECT_SAVED' }, '*');
        } catch (e) {
          console.error('\u{1F400} Failed to save extension config:', e);
        }
      }
    }

    // Etsy publish bridge (existing functionality)
    if (event.data?.type === 'LESRATS_PUBLISH_TO_ETSY') {
      const { categoryName, isDigital, productId, apiUrl } = event.data;
      await chrome.storage.local.set({
        pendingEtsyCategoryName: categoryName,
        pendingEtsyIsDigital: isDigital,
        pendingEtsyProductId: productId,
        pendingEtsyApiUrl: apiUrl,
      });
      window.postMessage({ type: 'LESRATS_READY_TO_PUBLISH' }, '*');
    }
  });

  // Announce presence after a short delay (let the page JS load)
  setTimeout(() => {
    window.postMessage({
      type: 'LESRATS_EXTENSION_PRESENT',
      connected: isConnected,
      version: extensionVersion,
    }, '*');
  }, 500);

  // ============== HELPERS ==============

  async function getToken() {
    const saved = await chrome.storage.local.get(['devMode']);
    if (saved.devMode) {
      return await SecureStorage.getSecure('devApiToken');
    }
    return await SecureStorage.getSecure('apiToken');
  }

  // ============== LEGACY: localStorage fallback for Etsy ==============
  try {
    const pending = localStorage.getItem('lesrats_pending_etsy');
    if (pending) {
      const data = JSON.parse(pending);
      if (Date.now() - data.timestamp < 30000) {
        chrome.storage.local.set({
          pendingEtsyCategoryName: data.categoryName,
          pendingEtsyIsDigital: data.isDigital,
          pendingEtsyProductId: data.productId,
          pendingEtsyApiUrl: data.apiUrl,
        });
        localStorage.removeItem('lesrats_pending_etsy');
      }
    }
  } catch (e) {
    // Ignore
  }

  console.log('\u{1F400} LesRats dashboard bridge loaded', isConnected ? '(connected)' : '(not connected)');
})();
