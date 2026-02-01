// Content script for LesRats dashboard
// Stores data for Etsy automation

// Listen for messages from the page
window.addEventListener('message', async (event) => {
  if (event.source !== window) return;
  
  if (event.data?.type === 'LESRATS_PUBLISH_TO_ETSY') {
    const { categoryName, isDigital, productId, apiUrl } = event.data;
    
    await chrome.storage.local.set({
      pendingEtsyCategoryName: categoryName,
      pendingEtsyIsDigital: isDigital,
      pendingEtsyProductId: productId,
      pendingEtsyApiUrl: apiUrl
    });
    
    window.postMessage({ type: 'LESRATS_READY_TO_PUBLISH' }, '*');
  }
});

// Check localStorage fallback on load
try {
  const pending = localStorage.getItem('lesrats_pending_etsy');
  if (pending) {
    const data = JSON.parse(pending);
    if (Date.now() - data.timestamp < 30000) {
      chrome.storage.local.set({
        pendingEtsyCategoryName: data.categoryName,
        pendingEtsyIsDigital: data.isDigital,
        pendingEtsyProductId: data.productId,
        pendingEtsyApiUrl: data.apiUrl
      });
      localStorage.removeItem('lesrats_pending_etsy');
    }
  }
} catch (e) {
  // Ignore errors
}
