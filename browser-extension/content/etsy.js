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
      
      // Handle dialogs in sequence (there may be multiple)
      // Pass is_digital flag from backend for correct product type selection
      await handleAllDialogs(productData, productData.is_digital === true);
      
      // Wait for form to load
      await waitForFormAfterCategory();
      
      // Fill the form
      await fillEtsyForm(productData);
      
      showSuccess('Formulaire rempli! Verifiez et publiez.');
    }
  }
})();

// Handle all dialogs in sequence until main form appears
async function handleAllDialogs(productData, isDigital = false) {
  const categoryData = productData.etsy_category || null;
  let maxAttempts = 10;
  let dialogsHandled = 0;
  let handledDialogTitles = new Set();

  console.log('🐀 handleAllDialogs - isDigital:', isDigital);
  
  while (maxAttempts > 0) {
    // Check if there's a dialog present
    const dialog = document.querySelector('[data-wt-dialog-root="true"]');
    
    if (!dialog) {
      console.log('🐀 No more dialogs found after handling', dialogsHandled, 'dialogs');
      break;
    }
    
    // Get dialog title to track which ones we've handled
    const dialogTitle = dialog.querySelector('.wt-dialog__header__heading')?.textContent?.trim() || '';
    
    // Skip if we already handled this exact dialog
    if (handledDialogTitles.has(dialogTitle)) {
      console.log('🐀 Already handled dialog:', dialogTitle);
      await sleep(500);
      maxAttempts--;
      continue;
    }
    
    console.log('🐀 Found dialog:', dialogTitle);
    await handleCategoryDialog(categoryData, isDigital);
    handledDialogTitles.add(dialogTitle);
    dialogsHandled++;
    
    // Wait for dialog to close and next one to potentially appear
    await sleep(1500);
    maxAttempts--;
  }
  
  console.log('🐀 Dialog handling complete. Total dialogs handled:', dialogsHandled);
}

// Handle the category selection dialog
async function handleCategoryDialog(categoryData, isDigital = false) {
  // Check if category dialog is present
  const dialog = document.querySelector('[data-wt-dialog-root="true"]');
  if (!dialog) {
    console.log('🐀 No category dialog found');
    return;
  }

  // Check dialog title to determine which dialog it is
  const dialogTitle = dialog.querySelector('.wt-dialog__header__heading')?.textContent || '';

  if (dialogTitle.includes('quelle sorte d\'article') || dialogTitle.includes('Quelle sorte')) {
    console.log('🐀 Category selection dialog detected');
    await handleCategorySelectionDialog(dialog, categoryData);
  } else if (dialogTitle.includes('Parlez-nous') || dialogTitle.includes('ensuite de votre article')) {
    console.log('🐀 Article details dialog detected');
    await handleArticleDetailsDialog(dialog, categoryData, isDigital);
  } else {
    console.log('🐀 Unknown dialog:', dialogTitle);
  }
}

// Handle the first dialog - category selection
async function handleCategorySelectionDialog(dialog, categoryData) {
  if (categoryData && categoryData.etsy_id) {
    // Try to click the checkbox by ID
    const checkbox = document.getElementById(categoryData.etsy_id);
    if (checkbox) {
      console.log('🐀 Found category checkbox by ID:', categoryData.etsy_id);
      checkbox.click();
      await sleep(300);
    }
  } else if (categoryData && categoryData.etsy_name) {
    // Try to find by name match
    const categoryLabels = dialog.querySelectorAll('.le-category-action-item h2, [data-test-id="seller-taxonomy-path-name"]');
    for (const label of categoryLabels) {
      if (label.textContent.toLowerCase().includes(categoryData.etsy_name.toLowerCase())) {
        // Find the parent checkbox
        const container = label.closest('label') || label.closest('.le-category-action-item')?.parentElement;
        const checkbox = container?.querySelector('input[type="checkbox"]');
        if (checkbox) {
          console.log('🐀 Found category by name match:', categoryData.etsy_name);
          checkbox.click();
          await sleep(300);
          break;
        }
      }
    }
  }
  
  // Click "Continuer" button
  await clickContinueButton(dialog);
}

// Handle the second dialog - article details (type, who made it, etc.)
async function handleArticleDetailsDialog(dialog, categoryData, isDigital = false) {
  console.log('🐀 Filling article details dialog');
  console.log('🐀 isDigital flag from backend:', isDigital);

  // 1. Select article type: "Fichiers numériques" (digital) or "Article physique" (physical)
  // isDigital flag comes from backend (true for Printables products)
  const typeValue = isDigital ? 'download' : 'physical';
  const typeRadio = dialog.querySelector(`input[name="listing_type_options_group"][value="${typeValue}"]`);
  if (typeRadio) {
    typeRadio.click();
    console.log('🐀 Selected article type:', typeValue);
    await sleep(300);
  }
  
  // 2. Select "Who made it": "Une autre personne ou entreprise" (third option)
  const whoMadeRadios = dialog.querySelectorAll('input[name="whoMade"]');
  if (whoMadeRadios.length >= 3) {
    whoMadeRadios[2].click(); // Third option: "Une autre personne ou entreprise"
    console.log('🐀 Selected: Une autre personne ou entreprise');
    await sleep(300);
  }
  
  // 3. Select "What is it": "Un produit fini" (first option)
  const isSupplyRadios = dialog.querySelectorAll('input[name="isSupply"]');
  if (isSupplyRadios.length >= 1) {
    isSupplyRadios[0].click(); // First option: "Un produit fini"
    console.log('🐀 Selected: Un produit fini');
    await sleep(300);
  }
  
  // 4. Select "When made": "2020 - 2026"
  const whenMadeSelect = dialog.querySelector('#when-made-select');
  if (whenMadeSelect) {
    whenMadeSelect.value = '2020_2026';
    whenMadeSelect.dispatchEvent(new Event('change', { bubbles: true }));
    console.log('🐀 Selected: 2020 - 2026');
    await sleep(300);
  }
  
  // 5. Handle production partners (required for "Une autre personne ou entreprise")
  await handleProductionPartners(dialog);
  
  // Click "Continuer" button
  await clickContinueButton(dialog);
}

// Handle production partners selection
async function handleProductionPartners(dialog) {
  // Find the production partners button - try multiple selectors
  let selectPartnersBtn = dialog.querySelector('button[data-change-production-partners-button="true"]');
  
  // Fallback: find by aria-controls
  if (!selectPartnersBtn) {
    selectPartnersBtn = dialog.querySelector('button[aria-controls="production-partners-overlay"]');
  }
  
  // Fallback: find by text content
  if (!selectPartnersBtn) {
    const buttons = dialog.querySelectorAll('button');
    for (const btn of buttons) {
      const text = btn.textContent.toLowerCase();
      if (text.includes('partenaire') && (text.includes('ajouter') || text.includes('sélectionner'))) {
        selectPartnersBtn = btn;
        break;
      }
    }
  }
  
  if (!selectPartnersBtn) {
    console.log('🐀 Production partners button not found');
    return;
  }
  
  console.log('🐀 Clicking production partners button:', selectPartnersBtn.textContent.trim());
  selectPartnersBtn.click();
  
  // Wait for overlay modal with animation-done class (fully loaded)
  let overlay = null;
  for (let i = 0; i < 20; i++) {
    overlay = document.querySelector('.wt-overlay__modal.wt-overlay--animation-done');
    if (overlay) {
      console.log('🐀 Overlay animation done, found at attempt', i + 1);
      break;
    }
    await sleep(200);
  }
  
  if (!overlay) {
    console.log('🐀 Production partners overlay not found');
    return;
  }
  
  console.log('🐀 Found overlay modal with animation done');
  
  // Wait for checkbox to appear inside overlay
  let firstCheckbox = null;
  for (let i = 0; i < 20; i++) {
    firstCheckbox = overlay.querySelector('input[type="checkbox"]');
    if (firstCheckbox) {
      console.log('🐀 Checkbox found at attempt', i + 1);
      break;
    }
    console.log('🐀 Waiting for checkbox... attempt', i + 1);
    await sleep(200);
  }
  
  if (firstCheckbox) {
    const isChecked = firstCheckbox.getAttribute('aria-checked') === 'true';
    console.log('🐀 Found checkbox:', firstCheckbox.id, 'isChecked:', isChecked);
    
    if (!isChecked) {
      console.log('🐀 Clicking checkbox...');
      firstCheckbox.click();
      await sleep(500);
      console.log('🐀 After click, aria-checked:', firstCheckbox.getAttribute('aria-checked'));
    } else {
      console.log('🐀 Checkbox already checked');
    }
  } else {
    console.log('🐀 No checkbox found in overlay after 20 attempts');
  }
  
  // ALWAYS click "Terminé" button (even if checkbox was already checked)
  await sleep(300);
  let termineBtn = document.querySelector('button[data-apply-button="true"]');
  
  // Retry finding the button
  for (let i = 0; i < 5 && !termineBtn; i++) {
    await sleep(200);
    termineBtn = document.querySelector('button[data-apply-button="true"]');
  }
  
  if (termineBtn) {
    console.log('🐀 Clicking "Terminé"');
    termineBtn.click();
    await sleep(500);
  } else {
    console.log('🐀 Terminé button not found');
  }
}

// Helper to click the Continue button
async function clickContinueButton(dialog) {
  await sleep(500);
  
  // Try footer first (where Etsy puts the Continue button)
  const footer = dialog.querySelector('.wt-dialog__footer__container');
  if (footer) {
    const continueBtn = footer.querySelector('.wt-btn--primary');
    if (continueBtn && continueBtn.textContent.trim() === 'Continuer') {
      console.log('🐀 Clicking Continue button in footer');
      continueBtn.click();
      await sleep(1000);
      return;
    }
  }
  
  // Fallback: search all primary buttons
  const buttons = dialog.querySelectorAll('.wt-btn--primary');
  for (const btn of buttons) {
    if (btn.textContent.includes('Continuer')) {
      console.log('🐀 Clicking Continue button (fallback)');
      btn.click();
      await sleep(1000);
      return;
    }
  }
  
  console.warn('🐀 Continue button not found!');
}

// Wait for the main form to appear after category dialog closes
async function waitForFormAfterCategory() {
  return new Promise((resolve) => {
    let attempts = 0;
    const maxAttempts = 20;
    
    const checkInterval = setInterval(() => {
      attempts++;
      
      // Check if dialog is gone and form is visible
      const dialog = document.querySelector('[data-wt-dialog-root="true"]');
      const titleInput = document.querySelector('#listing-title-input, textarea[name="title"], [data-field-id="title"] textarea');
      
      if (!dialog && titleInput) {
        clearInterval(checkInterval);
        setTimeout(resolve, 1000); // Extra time for form to stabilize
      } else if (attempts >= maxAttempts) {
        clearInterval(checkInterval);
        resolve(); // Continue anyway after timeout
      }
    }, 500);
  });
}

// Wait for Etsy's React app to load (either category dialog or main form)
function waitForPageLoad() {
  return new Promise((resolve) => {
    // Etsy uses React, so we need to wait for the form to render
    const checkInterval = setInterval(() => {
      // Check for category dialog (appears first on create page)
      const categoryDialog = document.querySelector('[data-wt-dialog-root="true"]');
      // Or title input (main form)
      const titleInput = document.querySelector('#listing-title-input, textarea[name="title"], #field-title textarea');
      
      if (categoryDialog || titleInput) {
        clearInterval(checkInterval);
        // Give extra time for all fields to render
        setTimeout(resolve, 1500);
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

// Fetch product data from LesRats API via service worker (to avoid CORS issues)
async function fetchProductData(apiUrl, productId) {
  try {
    // Route through service worker to avoid HTTPS->HTTP mixed content block
    const data = await chrome.runtime.sendMessage({
      action: 'fetchEtsyData',
      apiUrl: apiUrl,
      productId: productId
    });
    
    if (data.success) {
      console.log('🐀 Product data fetched:', data.data);
      return data.data;
    } else {
      showError(`Erreur API: ${data.message || data.error}`);
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
  
  // Upload images if available
  if (product.images && product.images.length > 0) {
    await uploadImages(product.images, product.title);
  }
  
  console.log('🐀 Form filling complete!');
}

// Upload images to Etsy - download and show drag helper
async function uploadImages(imageUrls, productTitle = '') {
  if (!imageUrls || imageUrls.length === 0) {
    console.log('🐀 No images to upload');
    return;
  }
  
  const count = Math.min(imageUrls.length, 10);
  console.log('🐀 Downloading', count, 'images...');
  showNotification(`Telechargement de ${count} images...`, 'info');
  
  // Download images to Downloads folder with product name
  const result = await chrome.runtime.sendMessage({
    action: 'downloadImagesAndSave',
    imageUrls: imageUrls.slice(0, 10),
    productTitle: productTitle
  });
  
  if (result.success) {
    showDownloadDragHelper(result.count, result.filename);
  } else {
    showError('Erreur telechargement: ' + (result.error || 'Inconnue'));
  }
}

// Show helper for dragging from downloads
function showDownloadDragHelper(imageCount, filename = 'lesrats') {
  // Remove existing
  const existing = document.getElementById('lesrats-image-panel');
  if (existing) existing.remove();
  
  const panel = document.createElement('div');
  panel.id = 'lesrats-image-panel';
  panel.innerHTML = `
    <div style="
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 999999;
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.3);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      max-width: 350px;
      overflow: hidden;
    ">
      <div style="
        background: linear-gradient(135deg, #F97316 0%, #EA580C 100%);
        color: white;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
      ">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 20px;">🐀</span>
          <span style="font-weight: bold;">${imageCount} images telechargees!</span>
        </div>
        <button onclick="this.closest('#lesrats-image-panel').remove()" style="
          background: rgba(255,255,255,0.2);
          border: none;
          color: white;
          width: 24px;
          height: 24px;
          border-radius: 4px;
          cursor: pointer;
          font-size: 16px;
        ">×</button>
      </div>
      <div style="padding: 16px;">
        <div style="font-size: 13px; color: #333; line-height: 1.6;">
          <strong>Pour ajouter les images:</strong><br><br>
          1. Cliquez sur l'icone <strong>Telechargements</strong> (↓) en haut a droite<br>
          2. Glissez <strong>${filename}_01.jpg</strong> vers la zone d'upload<br>
          3. Repetez pour les autres images
        </div>
        <div style="margin-top: 12px; padding: 10px; background: #FFF7ED; border-radius: 8px; font-size: 12px; color: #9A3412;">
          💡 Astuce: Vous pouvez aussi ouvrir le dossier Telechargements et glisser tous les fichiers d'un coup!
        </div>
      </div>
    </div>
  `;
  
  document.body.appendChild(panel);
  
  // Highlight Etsy upload area
  const uploadArea = document.querySelector('[data-clg-id="WtUploadArea"]');
  if (uploadArea) {
    uploadArea.style.border = '3px dashed #F97316';
    uploadArea.style.borderRadius = '8px';
  }
}

// Show panel with draggable images
function showDraggableImagePanel(images) {
  // Remove existing panel
  const existing = document.getElementById('lesrats-image-panel');
  if (existing) existing.remove();
  
  const panel = document.createElement('div');
  panel.id = 'lesrats-image-panel';
  panel.innerHTML = `
    <div style="
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 999999;
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.3);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      max-width: 320px;
      max-height: 80vh;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    ">
      <div style="
        background: linear-gradient(135deg, #F97316 0%, #EA580C 100%);
        color: white;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
      ">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 20px;">🐀</span>
          <span style="font-weight: bold;">${images.length} images</span>
        </div>
        <button id="lesrats-close-panel" style="
          background: rgba(255,255,255,0.2);
          border: none;
          color: white;
          width: 24px;
          height: 24px;
          border-radius: 4px;
          cursor: pointer;
          font-size: 16px;
        ">×</button>
      </div>
      <div style="padding: 8px; font-size: 12px; color: #666; background: #f5f5f5;">
        Glissez les images vers la zone d'upload Etsy
      </div>
      <div id="lesrats-images-container" style="
        padding: 8px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        overflow-y: auto;
        max-height: 400px;
      "></div>
    </div>
  `;
  
  document.body.appendChild(panel);
  
  // Add images
  const container = document.getElementById('lesrats-images-container');
  images.forEach((img, index) => {
    const imgWrapper = document.createElement('div');
    imgWrapper.style.cssText = 'position: relative; aspect-ratio: 1; border-radius: 8px; overflow: hidden; cursor: grab; border: 2px solid #ddd;';
    imgWrapper.draggable = true;
    
    const imgEl = document.createElement('img');
    imgEl.src = img.base64;
    imgEl.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
    imgEl.draggable = false;
    
    const badge = document.createElement('div');
    badge.style.cssText = 'position: absolute; top: 4px; left: 4px; background: #F97316; color: white; font-size: 10px; padding: 2px 6px; border-radius: 4px; font-weight: bold;';
    badge.textContent = index + 1;
    
    imgWrapper.appendChild(imgEl);
    imgWrapper.appendChild(badge);
    container.appendChild(imgWrapper);
    
    // Drag events
    imgWrapper.addEventListener('dragstart', (e) => {
      imgWrapper.style.opacity = '0.5';
      imgWrapper.style.border = '2px solid #F97316';
      
      // Convert base64 to blob for drag
      const byteString = atob(img.base64.split(',')[1]);
      const mimeType = img.mimeType || 'image/jpeg';
      const ab = new ArrayBuffer(byteString.length);
      const ia = new Uint8Array(ab);
      for (let i = 0; i < byteString.length; i++) {
        ia[i] = byteString.charCodeAt(i);
      }
      const blob = new Blob([ab], { type: mimeType });
      const file = new File([blob], `lesrats_${index + 1}.jpg`, { type: mimeType });
      
      e.dataTransfer.setData('application/x-moz-file', file);
      e.dataTransfer.setData('text/plain', img.base64);
      e.dataTransfer.effectAllowed = 'copy';
      
      // Try to set file directly
      try {
        e.dataTransfer.items.add(file);
      } catch (err) {
        console.log('🐀 Could not add file to dataTransfer');
      }
    });
    
    imgWrapper.addEventListener('dragend', () => {
      imgWrapper.style.opacity = '1';
      imgWrapper.style.border = '2px solid #22c55e';
    });
  });
  
  // Close button
  document.getElementById('lesrats-close-panel').addEventListener('click', () => {
    panel.remove();
  });
  
  // Highlight Etsy upload area
  const uploadArea = document.querySelector('[data-clg-id="WtUploadArea"]');
  if (uploadArea) {
    uploadArea.style.border = '3px dashed #F97316';
    uploadArea.style.borderRadius = '8px';
  }
}

// Show helper UI for image upload
function showImageUploadHelper(imageCount, directory = null) {
  const folderName = directory ? directory.split('/').pop() : 'Telechargements';
  const filePrefix = directory ? 'lesrats_' : 'lesrats_image_';
  // Remove existing helper if any
  const existing = document.getElementById('lesrats-image-helper');
  if (existing) existing.remove();
  
  const helper = document.createElement('div');
  helper.id = 'lesrats-image-helper';
  helper.innerHTML = `
    <div style="
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 999999;
      background: linear-gradient(135deg, #F97316 0%, #EA580C 100%);
      color: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.3);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      max-width: 350px;
    ">
      <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
        <span style="font-size: 28px;">🐀</span>
        <div>
          <div style="font-weight: bold; font-size: 16px;">Images pretes!</div>
          <div style="font-size: 13px; opacity: 0.9;">${imageCount} fichiers dans ${folderName}</div>
        </div>
      </div>
      
      <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 8px; margin-bottom: 12px;">
        <div style="font-size: 13px; margin-bottom: 8px;"><strong>Etape suivante:</strong></div>
        <div style="font-size: 12px; line-height: 1.5;">
          1. Cliquez sur <strong>"Chargez les fichiers"</strong> ci-dessus<br>
          2. Allez dans <strong>${folderName}</strong><br>
          3. Selectionnez <strong>${filePrefix}01.jpg</strong> a <strong>${filePrefix}${String(imageCount).padStart(2, '0')}.jpg</strong>
        </div>
      </div>
      
      <button onclick="this.parentElement.parentElement.remove()" style="
        width: 100%;
        padding: 10px;
        background: rgba(255,255,255,0.2);
        border: none;
        border-radius: 6px;
        color: white;
        font-size: 13px;
        cursor: pointer;
        font-weight: 500;
      ">Compris, fermer</button>
    </div>
  `;
  
  document.body.appendChild(helper);
  
  // Highlight the upload button
  const uploadBtn = document.querySelector('[data-clg-id="WtUploadArea"] button');
  if (uploadBtn) {
    uploadBtn.style.border = '3px solid #F97316';
    uploadBtn.style.boxShadow = '0 0 15px #F97316';
    uploadBtn.style.animation = 'pulse 1.5s infinite';
    
    // Add pulse animation
    const style = document.createElement('style');
    style.textContent = `
      @keyframes pulse {
        0%, 100% { box-shadow: 0 0 15px #F97316; }
        50% { box-shadow: 0 0 25px #F97316, 0 0 35px #F97316; }
      }
    `;
    document.head.appendChild(style);
  }
}

// Find the image upload input on Etsy
function findImageInput() {
  // Etsy's upload area has a hidden file input inside #field-listingImages
  const selectors = [
    '#field-listingImages input[type="file"]',
    '[data-clg-id="WtUploadArea"] input[type="file"]',
    '.wt-upload__area input[type="file"]',
    'input[type="file"][multiple]',
    'input[type="file"]'
  ];
  
  for (const selector of selectors) {
    const input = document.querySelector(selector);
    if (input) {
      console.log('🐀 Found image input with selector:', selector);
      return input;
    }
  }
  
  return null;
}

// Alternative: trigger upload via drag and drop simulation
async function uploadImagesViaDragDrop(files) {
  const dropZone = document.querySelector('[data-clg-id="WtUploadArea"]') || 
                   document.querySelector('.wt-upload__area') ||
                   document.querySelector('#field-listingImages');
  
  if (!dropZone) {
    console.warn('🐀 Could not find drop zone');
    return false;
  }
  
  console.log('🐀 Simulating drag and drop on:', dropZone);
  
  // Create a DataTransfer with files
  const dataTransfer = new DataTransfer();
  files.forEach(file => dataTransfer.items.add(file));
  
  // Simulate drag events
  const dragEnterEvent = new DragEvent('dragenter', {
    bubbles: true,
    cancelable: true,
    dataTransfer: dataTransfer
  });
  
  const dragOverEvent = new DragEvent('dragover', {
    bubbles: true,
    cancelable: true,
    dataTransfer: dataTransfer
  });
  
  const dropEvent = new DragEvent('drop', {
    bubbles: true,
    cancelable: true,
    dataTransfer: dataTransfer
  });
  
  dropZone.dispatchEvent(dragEnterEvent);
  await sleep(100);
  dropZone.dispatchEvent(dragOverEvent);
  await sleep(100);
  dropZone.dispatchEvent(dropEvent);
  
  return true;
}

// Fill the title field
async function fillTitle(title) {
  if (!title) return;
  
  const selectors = [
    '#listing-title-input',
    'textarea[name="title"]',
    'input[name="title"]'
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
    '#listing-description-textarea',
    'textarea[name="description"]'
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
    '#listing-price-input',
    'input[name="variations.configuration.price"]',
    'input[name="price"]'
  ];
  
  const input = findElement(selectors);
  if (input) {
    await setInputValue(input, price.toFixed(2));
    console.log('🐀 Price filled');
  } else {
    console.warn('🐀 Price input not found');
  }
}

// Fill tags - put comma-separated in input and click Ajouter
async function fillTags(tags) {
  if (!tags || tags.length === 0) return;
  
  // Limit to 13 tags (Etsy max)
  const tagsToFill = tags.slice(0, 13).map(t => t.trim()).filter(t => t);
  
  // Join as comma-separated string
  const tagsString = tagsToFill.join(', ');
  
  // Try multiple selectors for the tags input
  const selectors = [
    '#listing-tags-input',
    'input[name="tags"]',
    'input[placeholder*="tag"]',
    'input[placeholder*="Tag"]',
    '[data-field="tags"] input',
    '#tags input',
    '.wt-tag-input input',
    'input[aria-label*="tag"]',
    'input[aria-label*="Tag"]'
  ];
  
  let input = null;
  for (const selector of selectors) {
    input = document.querySelector(selector);
    if (input) {
      console.log('🐀 Found tags input with selector:', selector);
      break;
    }
  }
  
  if (input) {
    // Focus and set value directly
    input.focus();
    input.value = tagsString;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
    await sleep(300);
    
    // Click "Ajouter" button
    const addButtonSelectors = [
      '#listing-tags-button',
      'button[data-selector="add-tag-button"]',
      '[data-field="tags"] button',
      'button.wt-btn--small'
    ];
    
    // Also try finding button by text "Ajouter"
    let addButton = null;
    for (const selector of addButtonSelectors) {
      addButton = document.querySelector(selector);
      if (addButton) {
        console.log('🐀 Found add button with selector:', selector);
        break;
      }
    }
    
    // Fallback: find button by text content near the tags input
    if (!addButton) {
      const buttons = document.querySelectorAll('button');
      for (const btn of buttons) {
        if (btn.textContent.trim().toLowerCase() === 'ajouter') {
          addButton = btn;
          console.log('🐀 Found add button by text');
          break;
        }
      }
    }
    
    if (addButton) {
      addButton.click();
      console.log('🐀 Clicked Ajouter button');
      await sleep(300);
    }
    
    console.log('🐀 Tags filled (comma-separated):', tagsString);
  } else {
    console.warn('🐀 Tags input not found');
    // Copy to clipboard as fallback
    try {
      await navigator.clipboard.writeText(tagsString);
      console.log('🐀 Tags copied to clipboard:', tagsString);
    } catch (e) {
      console.warn('🐀 Could not copy tags to clipboard');
    }
  }
}

// Fill quantity field
async function fillQuantity(quantity) {
  if (!quantity) return;
  
  const selectors = [
    '#listing-quantity-input',
    'input[name="quantity"]'
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
