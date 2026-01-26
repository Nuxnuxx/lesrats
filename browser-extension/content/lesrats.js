// Content script for LesRats dashboard
// Listens for "Publish to Etsy" button clicks and stores data in extension storage

console.log('🐀 LesRats Dashboard helper loaded');

// Listen for messages from the page
window.addEventListener('message', async (event) => {
  // Only accept messages from same origin
  if (event.source !== window) return;
  
  if (event.data && event.data.type === 'LESRATS_PUBLISH_TO_ETSY') {
    console.log('🐀 Received publish request:', event.data);
    
    const { productId, shopName, apiUrl } = event.data;
    
    // Store in extension storage
    await chrome.storage.local.set({
      pendingEtsyProduct: productId,
      pendingEtsyShopName: shopName,
      apiUrl: apiUrl
    });
    
    console.log('🐀 Stored pending product:', productId);
    
    // Notify the page that we're ready
    window.postMessage({ type: 'LESRATS_READY_TO_PUBLISH' }, '*');
  }
});

// Also check if there's pending data in localStorage (fallback)
const checkLocalStorage = () => {
  try {
    const pending = localStorage.getItem('lesrats_pending_etsy');
    if (pending) {
      const data = JSON.parse(pending);
      // Only process if recent (within last 30 seconds)
      if (Date.now() - data.timestamp < 30000) {
        console.log('🐀 Found pending data in localStorage:', data);
        chrome.storage.local.set({
          pendingEtsyProduct: data.productId,
          pendingEtsyShopName: data.shopName,
          apiUrl: data.apiUrl
        });
        // Clear localStorage after processing
        localStorage.removeItem('lesrats_pending_etsy');
      }
    }
  } catch (e) {
    // Ignore errors
  }
};

// Check on load
checkLocalStorage();
