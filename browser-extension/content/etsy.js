// Content script for Etsy listing editor
// Auto-fills form with product data from LesRats

console.log('🐀 LesRats Etsy Publisher v1.0 loaded');

// Check for pending product to publish on page load
(async function init() {
  // Wait for page to fully load
  await waitForPageLoad();
  
  // Check if there's a pending product to publish
  const pending = await chrome.storage.local.get(['pendingEtsyProduct', 'pendingEtsyShopName', 'apiUrl']);
  
  if (pending.pendingEtsyProduct) {
    console.log('🐀 Found pending product:', pending.pendingEtsyProduct);
    
    // Get current shop name from the page
    const currentShopName = detectShopName();
    console.log('🐀 Current Etsy shop:', currentShopName);
    
    // Validate shop name matches
    if (pending.pendingEtsyShopName && currentShopName) {
      const expectedShop = pending.pendingEtsyShopName.toLowerCase().trim();
      const actualShop = currentShopName.toLowerCase().trim();
      
      if (!actualShop.includes(expectedShop) && !expectedShop.includes(actualShop)) {
        showError(`Mauvaise boutique! Attendu: "${pending.pendingEtsyShopName}", Actuel: "${currentShopName}". Changez de boutique sur Etsy.`);
        // Clear the pending product
        await chrome.storage.local.remove(['pendingEtsyProduct', 'pendingEtsyShopName']);
        return;
      }
    }
    
    // Fetch product data from API
    const apiUrl = pending.apiUrl || 'http://localhost:8000';
    const productData = await fetchProductData(apiUrl, pending.pendingEtsyProduct);
    
    if (productData) {
      // Clear the pending product first
      await chrome.storage.local.remove(['pendingEtsyProduct', 'pendingEtsyShopName']);
      
      // Fill the form
      await fillEtsyForm(productData);
      
      showSuccess('Formulaire rempli! Ajoutez les images et la categorie, puis publiez.');
    }
  }
})();

// Wait for Etsy's React app to load
function waitForPageLoad() {
  return new Promise((resolve) => {
    // Etsy uses React, so we need to wait for the form to render
    const checkInterval = setInterval(() => {
      // Look for the title input field as indicator that form is loaded
      const titleInput = document.querySelector('input[name="title"], input[placeholder*="titre"], input[aria-label*="titre"], textarea[name="title"]');
      if (titleInput) {
        clearInterval(checkInterval);
        // Give extra time for all fields to render
        setTimeout(resolve, 1000);
      }
    }, 500);
    
    // Timeout after 30 seconds
    setTimeout(() => {
      clearInterval(checkInterval);
      resolve();
    }, 30000);
  });
}

// Detect the current shop name from the Etsy page
function detectShopName() {
  // Try to get from Etsy.Context JavaScript object
  try {
    if (window.Etsy && window.Etsy.Context && window.Etsy.Context.data) {
      const data = window.Etsy.Context.data;
      if (data.initial_data && data.initial_data.dashboard && data.initial_data.dashboard.shop_name) {
        return data.initial_data.dashboard.shop_name;
      }
    }
  } catch (e) {
    console.log('🐀 Could not read Etsy.Context');
  }
  
  // Try to find in page scripts
  try {
    const scripts = document.querySelectorAll('script');
    for (const script of scripts) {
      const content = script.textContent || '';
      const shopMatch = content.match(/"shop_name"\s*:\s*"([^"]+)"/);
      if (shopMatch) {
        return shopMatch[1];
      }
    }
  } catch (e) {
    console.log('🐀 Could not find shop name in scripts');
  }
  
  // Try to find in URL
  const urlMatch = window.location.href.match(/\/shops\/([^\/]+)/);
  if (urlMatch && urlMatch[1] !== 'me') {
    return urlMatch[1];
  }
  
  // Try to find in page header
  const headerEl = document.querySelector('[data-nav-header] h2, .wt-text-title-large');
  if (headerEl) {
    return headerEl.textContent.trim();
  }
  
  return null;
}

// Fetch product data from LesRats API
async function fetchProductData(apiUrl, productId) {
  try {
    const response = await fetch(`${apiUrl}/api/extension/product/${productId}/etsy-data`);
    const data = await response.json();
    
    if (data.success) {
      console.log('🐀 Product data fetched:', data.data);
      return data.data;
    } else {
      showError(`Erreur API: ${data.message}`);
      return null;
    }
  } catch (error) {
    console.error('🐀 Error fetching product data:', error);
    showError(`Erreur de connexion: ${error.message}`);
    return null;
  }
}

// Main function to fill Etsy's listing form
async function fillEtsyForm(product) {
  console.log('🐀 Filling Etsy form with:', product);
  
  // Fill title
  await fillTitle(product.title);
  
  // Fill description
  await fillDescription(product.description);
  
  // Fill price
  await fillPrice(product.price);
  
  // Fill tags
  await fillTags(product.tags);
  
  // Fill quantity
  await fillQuantity(product.quantity);
  
  // Try to select shipping profile "Standart"
  await selectShippingProfile('Standart');
  
  console.log('🐀 Form filling complete!');
}

// Fill the title field
async function fillTitle(title) {
  if (!title) return;
  
  const selectors = [
    'input[name="title"]',
    'input[placeholder*="titre"]',
    'input[placeholder*="title"]',
    'input[aria-label*="titre"]',
    'input[aria-label*="title"]',
    'textarea[name="title"]',
    '#listing-edit-title input',
    '[data-test-id="title-input"] input'
  ];
  
  const input = findElement(selectors);
  if (input) {
    await setInputValue(input, title);
    console.log('🐀 Title filled');
  } else {
    console.warn('🐀 Title input not found');
  }
}

// Fill the description field
async function fillDescription(description) {
  if (!description) return;
  
  const selectors = [
    'textarea[name="description"]',
    'textarea[placeholder*="description"]',
    'textarea[aria-label*="description"]',
    '#listing-edit-description textarea',
    '[data-test-id="description-input"] textarea',
    '.wt-textarea'
  ];
  
  const textarea = findElement(selectors);
  if (textarea) {
    await setInputValue(textarea, description);
    console.log('🐀 Description filled');
  } else {
    console.warn('🐀 Description textarea not found');
  }
}

// Fill the price field
async function fillPrice(price) {
  if (!price) return;
  
  const selectors = [
    'input[name="price"]',
    'input[name="offering_price"]',
    'input[placeholder*="prix"]',
    'input[placeholder*="price"]',
    'input[aria-label*="prix"]',
    'input[aria-label*="price"]',
    '#listing-edit-price input',
    '[data-test-id="price-input"] input'
  ];
  
  const input = findElement(selectors);
  if (input) {
    await setInputValue(input, price.toFixed(2));
    console.log('🐀 Price filled');
  } else {
    console.warn('🐀 Price input not found');
  }
}

// Fill tags (Etsy has a special tag input system)
async function fillTags(tags) {
  if (!tags || tags.length === 0) return;
  
  // Find the tags input
  const selectors = [
    'input[name="tags"]',
    'input[placeholder*="tag"]',
    'input[aria-label*="tag"]',
    '[data-test-id="tags-input"] input',
    '.tag-input input',
    '#listing-edit-tags input'
  ];
  
  const input = findElement(selectors);
  if (input) {
    // Etsy typically requires typing each tag and pressing Enter
    for (let i = 0; i < Math.min(tags.length, 13); i++) {
      const tag = tags[i].trim();
      if (tag) {
        await setInputValue(input, tag);
        // Simulate Enter key to add the tag
        await simulateEnter(input);
        await sleep(300); // Wait between tags
      }
    }
    console.log('🐀 Tags filled:', tags.length);
  } else {
    console.warn('🐀 Tags input not found');
  }
}

// Fill quantity field
async function fillQuantity(quantity) {
  if (!quantity) return;
  
  const selectors = [
    'input[name="quantity"]',
    'input[placeholder*="quantite"]',
    'input[placeholder*="quantity"]',
    'input[aria-label*="quantite"]',
    'input[aria-label*="quantity"]',
    '#listing-edit-quantity input'
  ];
  
  const input = findElement(selectors);
  if (input) {
    await setInputValue(input, quantity.toString());
    console.log('🐀 Quantity filled');
  } else {
    console.warn('🐀 Quantity input not found');
  }
}

// Select shipping profile by name
async function selectShippingProfile(profileName) {
  // Try to find shipping dropdown or radio buttons
  const selectors = [
    'select[name*="shipping"]',
    '[data-test-id="shipping-profile"] select',
    '#shipping-profile-select'
  ];
  
  // Try select dropdown
  const select = findElement(selectors);
  if (select) {
    const options = select.querySelectorAll('option');
    for (const option of options) {
      if (option.textContent.toLowerCase().includes(profileName.toLowerCase())) {
        select.value = option.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        console.log('🐀 Shipping profile selected:', option.textContent);
        return;
      }
    }
  }
  
  // Try to find and click radio/button with the profile name
  const buttons = document.querySelectorAll('button, [role="button"], label, [role="option"]');
  for (const btn of buttons) {
    if (btn.textContent.toLowerCase().includes(profileName.toLowerCase())) {
      btn.click();
      console.log('🐀 Shipping profile clicked:', btn.textContent.trim());
      return;
    }
  }
  
  console.warn('🐀 Shipping profile not found:', profileName);
}

// Helper: Find element by multiple selectors
function findElement(selectors) {
  for (const selector of selectors) {
    try {
      const el = document.querySelector(selector);
      if (el) return el;
    } catch (e) {
      // Invalid selector, skip
    }
  }
  return null;
}

// Helper: Set input value with proper events
async function setInputValue(input, value) {
  // Focus the input
  input.focus();
  await sleep(100);
  
  // Clear existing value
  input.value = '';
  input.dispatchEvent(new Event('input', { bubbles: true }));
  
  // Set new value
  input.value = value;
  
  // Dispatch events to trigger React state updates
  input.dispatchEvent(new Event('input', { bubbles: true }));
  input.dispatchEvent(new Event('change', { bubbles: true }));
  input.dispatchEvent(new KeyboardEvent('keyup', { bubbles: true }));
  
  // Blur to finalize
  await sleep(100);
  input.blur();
}

// Helper: Simulate Enter key press
async function simulateEnter(input) {
  input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true }));
  input.dispatchEvent(new KeyboardEvent('keypress', { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true }));
  input.dispatchEvent(new KeyboardEvent('keyup', { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true }));
}

// Helper: Sleep function
function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

// Show success notification
function showSuccess(message) {
  showNotification(message, 'success');
}

// Show error notification
function showError(message) {
  showNotification(message, 'error');
}

// Show notification banner
function showNotification(message, type = 'info') {
  // Remove existing notification
  const existing = document.getElementById('lesrats-notification');
  if (existing) existing.remove();
  
  const colors = {
    success: { bg: '#10B981', text: 'white' },
    error: { bg: '#EF4444', text: 'white' },
    info: { bg: '#3B82F6', text: 'white' }
  };
  
  const color = colors[type] || colors.info;
  
  const notification = document.createElement('div');
  notification.id = 'lesrats-notification';
  notification.innerHTML = `
    <div style="
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 999999;
      background: ${color.bg};
      color: ${color.text};
      padding: 16px 24px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      font-size: 14px;
      max-width: 400px;
      display: flex;
      align-items: center;
      gap: 12px;
    ">
      <span style="font-size: 20px;">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
      <span>${message}</span>
      <button onclick="this.parentElement.parentElement.remove()" style="
        background: none;
        border: none;
        color: ${color.text};
        cursor: pointer;
        padding: 4px;
        margin-left: 8px;
        font-size: 18px;
        opacity: 0.8;
      ">×</button>
    </div>
  `;
  
  document.body.appendChild(notification);
  
  // Auto-remove after 10 seconds for success, keep error longer
  setTimeout(() => {
    notification.remove();
  }, type === 'error' ? 30000 : 10000);
}

// Listen for messages from popup or background
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'fillEtsyForm') {
    fillEtsyForm(request.data)
      .then(() => sendResponse({ success: true }))
      .catch(error => sendResponse({ success: false, error: error.message }));
    return true; // Async response
  }
  
  if (request.action === 'getShopName') {
    sendResponse({ shopName: detectShopName() });
  }
});
