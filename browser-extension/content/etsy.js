// Content script for Etsy listing editor
// Full automation: category selection + form filling

// ============== DEBUG PANEL ==============
const DebugPanel = {
  panel: null,
  stepsContainer: null,
  isMinimized: false,
  steps: [],
  
  init() {
    if (this.panel) return;
    
    this.panel = document.createElement('div');
    this.panel.id = 'lesrats-debug-panel';
    this.panel.innerHTML = `
      <style>
        #lesrats-debug-panel {
          position: fixed;
          bottom: 20px;
          right: 20px;
          z-index: 999999;
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
          font-size: 13px;
        }
        #lesrats-debug-panel * {
          box-sizing: border-box;
        }
        .lesrats-panel-content {
          background: #1a1a2e;
          border-radius: 12px;
          box-shadow: 0 8px 32px rgba(0,0,0,0.4);
          width: 380px;
          max-height: 500px;
          overflow: hidden;
          display: flex;
          flex-direction: column;
        }
        .lesrats-panel-content.minimized {
          display: none;
        }
        .lesrats-header {
          background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
          color: white;
          padding: 12px 16px;
          display: flex;
          align-items: center;
          justify-content: space-between;
        }
        .lesrats-header-title {
          display: flex;
          align-items: center;
          gap: 8px;
          font-weight: 600;
        }
        .lesrats-header-actions {
          display: flex;
          gap: 8px;
        }
        .lesrats-header-btn {
          background: rgba(255,255,255,0.2);
          border: none;
          color: white;
          width: 24px;
          height: 24px;
          border-radius: 4px;
          cursor: pointer;
          font-size: 14px;
        }
        .lesrats-header-btn:hover {
          background: rgba(255,255,255,0.3);
        }
        .lesrats-steps {
          padding: 12px;
          overflow-y: auto;
          max-height: 400px;
          flex: 1;
        }
        .lesrats-step {
          background: rgba(255,255,255,0.05);
          border-radius: 8px;
          margin-bottom: 8px;
          overflow: hidden;
        }
        .lesrats-step-header {
          padding: 10px 12px;
          display: flex;
          align-items: center;
          gap: 10px;
          cursor: pointer;
        }
        .lesrats-step-header:hover {
          background: rgba(255,255,255,0.05);
        }
        .lesrats-step-icon {
          width: 20px;
          height: 20px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 11px;
          flex-shrink: 0;
        }
        .lesrats-step-icon.pending {
          background: #374151;
          color: #9ca3af;
        }
        .lesrats-step-icon.running {
          background: #3b82f6;
          color: white;
          animation: lesrats-pulse 1s infinite;
        }
        .lesrats-step-icon.success {
          background: #22c55e;
          color: white;
        }
        .lesrats-step-icon.error {
          background: #ef4444;
          color: white;
        }
        @keyframes lesrats-pulse {
          0%, 100% { opacity: 1; }
          50% { opacity: 0.5; }
        }
        .lesrats-step-title {
          flex: 1;
          color: #e5e7eb;
          font-weight: 500;
        }
        .lesrats-step-toggle {
          color: #6b7280;
          font-size: 10px;
        }
        .lesrats-step-details {
          display: none;
          padding: 0 12px 12px 42px;
          color: #9ca3af;
          font-size: 12px;
          font-family: 'Monaco', 'Menlo', monospace;
          white-space: pre-wrap;
          word-break: break-all;
        }
        .lesrats-step-details.expanded {
          display: block;
        }
        .lesrats-step-details .error-text {
          color: #fca5a5;
        }
        .lesrats-step-details .success-text {
          color: #86efac;
        }
        .lesrats-minimized-icon {
          width: 50px;
          height: 50px;
          background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 24px;
          cursor: pointer;
          box-shadow: 0 4px 16px rgba(249, 115, 22, 0.4);
        }
        .lesrats-minimized-icon.hidden {
          display: none;
        }
        .lesrats-minimized-icon:hover {
          transform: scale(1.1);
        }
        .lesrats-minimized-icon .badge {
          position: absolute;
          top: -4px;
          right: -4px;
          background: #ef4444;
          color: white;
          font-size: 10px;
          width: 18px;
          height: 18px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
        }
      </style>
      <div class="lesrats-panel-content">
        <div class="lesrats-header">
          <div class="lesrats-header-title">
            <span>🐀</span>
            <span>LesRats Automation</span>
          </div>
          <div class="lesrats-header-actions">
            <button class="lesrats-header-btn" id="lesrats-minimize" title="Minimiser">−</button>
            <button class="lesrats-header-btn" id="lesrats-close" title="Fermer">×</button>
          </div>
        </div>
        <div class="lesrats-steps" id="lesrats-steps"></div>
      </div>
      <div class="lesrats-minimized-icon hidden" id="lesrats-minimized">
        🐀
      </div>
    `;
    
    document.body.appendChild(this.panel);
    this.stepsContainer = document.getElementById('lesrats-steps');
    
    // Event listeners
    document.getElementById('lesrats-minimize').addEventListener('click', () => this.minimize());
    document.getElementById('lesrats-close').addEventListener('click', () => this.close());
    document.getElementById('lesrats-minimized').addEventListener('click', () => this.restore());
  },
  
  addStep(id, title) {
    this.init();
    
    const step = {
      id,
      title,
      status: 'pending',
      details: '',
      element: null
    };
    
    const stepEl = document.createElement('div');
    stepEl.className = 'lesrats-step';
    stepEl.innerHTML = `
      <div class="lesrats-step-header">
        <div class="lesrats-step-icon pending">○</div>
        <div class="lesrats-step-title">${title}</div>
        <div class="lesrats-step-toggle">▼</div>
      </div>
      <div class="lesrats-step-details"></div>
    `;
    
    const header = stepEl.querySelector('.lesrats-step-header');
    const details = stepEl.querySelector('.lesrats-step-details');
    
    header.addEventListener('click', () => {
      details.classList.toggle('expanded');
      stepEl.querySelector('.lesrats-step-toggle').textContent = 
        details.classList.contains('expanded') ? '▲' : '▼';
    });
    
    step.element = stepEl;
    this.steps.push(step);
    this.stepsContainer.appendChild(stepEl);
    
    // Auto-scroll to bottom
    this.stepsContainer.scrollTop = this.stepsContainer.scrollHeight;
    
    return id;
  },
  
  updateStep(id, status, details = '') {
    const step = this.steps.find(s => s.id === id);
    if (!step) return;
    
    step.status = status;
    step.details = details;
    
    const iconEl = step.element.querySelector('.lesrats-step-icon');
    const detailsEl = step.element.querySelector('.lesrats-step-details');
    
    iconEl.className = 'lesrats-step-icon ' + status;
    
    switch (status) {
      case 'running':
        iconEl.textContent = '◉';
        break;
      case 'success':
        iconEl.textContent = '✓';
        break;
      case 'error':
        iconEl.textContent = '✗';
        break;
      default:
        iconEl.textContent = '○';
    }
    
    if (details) {
      const formattedDetails = typeof details === 'object' 
        ? JSON.stringify(details, null, 2)
        : details;
      
      const cssClass = status === 'error' ? 'error-text' : 
                       status === 'success' ? 'success-text' : '';
      
      detailsEl.innerHTML = `<span class="${cssClass}">${formattedDetails}</span>`;
    }
    
    // Auto-scroll
    this.stepsContainer.scrollTop = this.stepsContainer.scrollHeight;
  },
  
  minimize() {
    this.isMinimized = true;
    this.panel.querySelector('.lesrats-panel-content').classList.add('minimized');
    
    const minimizedIcon = document.getElementById('lesrats-minimized');
    minimizedIcon.classList.remove('hidden');
    
    // Show error count badge if any errors
    const errorCount = this.steps.filter(s => s.status === 'error').length;
    let badge = minimizedIcon.querySelector('.badge');
    if (errorCount > 0) {
      if (!badge) {
        badge = document.createElement('div');
        badge.className = 'badge';
        minimizedIcon.style.position = 'relative';
        minimizedIcon.appendChild(badge);
      }
      badge.textContent = errorCount;
    } else if (badge) {
      badge.remove();
    }
  },
  
  restore() {
    this.isMinimized = false;
    this.panel.querySelector('.lesrats-panel-content').classList.remove('minimized');
    document.getElementById('lesrats-minimized').classList.add('hidden');
  },
  
  close() {
    if (this.panel) {
      this.panel.remove();
      this.panel = null;
      this.steps = [];
    }
  },
  
  success(message) {
    this.addStep('final-success', message);
    this.updateStep('final-success', 'success', 'Automation terminee avec succes!');
    setTimeout(() => this.minimize(), 2000);
  },
  
  error(message) {
    this.addStep('final-error', message);
    this.updateStep('final-error', 'error', message);
  }
};

// Helper to run a step with debug output
async function runStep(id, title, fn) {
  DebugPanel.addStep(id, title);
  DebugPanel.updateStep(id, 'running');
  
  try {
    const result = await fn();
    DebugPanel.updateStep(id, 'success', result || 'OK');
    return result;
  } catch (error) {
    DebugPanel.updateStep(id, 'error', error.message || error);
    throw error;
  }
}

// Check for pending data on page load
(async function init() {
  try {
    const pending = await chrome.storage.local.get([
      'pendingEtsyCategoryName', 
      'pendingEtsyIsDigital',
      'pendingEtsyProductId',
      'pendingEtsyApiUrl'
    ]);
    
    if (!pending.pendingEtsyCategoryName) return;
    
    // Initialize debug panel
    DebugPanel.init();
    
    const categoryName = pending.pendingEtsyCategoryName;
    const isDigital = pending.pendingEtsyIsDigital === true;
    const productId = pending.pendingEtsyProductId;
    const apiUrl = pending.pendingEtsyApiUrl;
    
    await runStep('init', 'Initialisation', async () => {
      await chrome.storage.local.remove([
        'pendingEtsyCategoryName', 
        'pendingEtsyIsDigital',
        'pendingEtsyProductId',
        'pendingEtsyApiUrl'
      ]);
      return { categoryName, isDigital, productId };
    });
    
    await runStep('wait-page', 'Attente chargement page', async () => {
      await waitForPageLoad();
      return 'Page chargee';
    });
    
    // Fetch product data from API
    let productData = null;
    if (productId) {
      await runStep('fetch-product', 'Recuperation donnees produit', async () => {
        const settings = await chrome.storage.local.get(['apiUrl', 'devMode', 'devApiUrl']);
        let finalApiUrl;
        if (settings.devMode) {
          finalApiUrl = settings.devApiUrl || 'http://localhost:8000';
        } else {
          finalApiUrl = apiUrl || settings.apiUrl || 'http://localhost:8000';
        }
        productData = await fetchProductData(finalApiUrl, productId);
        if (!productData) throw new Error('Produit non trouve ou token invalide');
        return { title: productData.title, price: productData.price };
      });
    }
    
    // Handle all dialogs
    await handleAllDialogs({ etsy_category: { etsy_name: categoryName } }, isDigital);
    
    // Wait for main form
    await runStep('wait-form', 'Attente formulaire principal', async () => {
      await waitForFormAfterCategory();
      return 'Formulaire pret';
    });
    
    // Fill form
    if (productData) {
      await runStep('fill-form', 'Remplissage formulaire', async () => {
        await fillEtsyForm(productData);
        return 'Titre, description, prix, tags remplis';
      });
    }
    
    DebugPanel.success('Automation terminee!');
    
  } catch (error) {
    console.error('🐀 Error:', error);
    DebugPanel.error('Erreur: ' + error.message);
  }
})();

// Handle all dialogs in sequence until main form appears
async function handleAllDialogs(productData, isDigital = false) {
  const categoryData = productData.etsy_category || null;
  let maxAttempts = 10;
  let handledDialogTitles = new Set();
  let dialogCount = 0;
  
  while (maxAttempts > 0) {
    const dialog = document.querySelector('[data-wt-dialog-root="true"]');
    if (!dialog) break;
    
    const dialogTitle = dialog.querySelector('.wt-dialog__header__heading')?.textContent?.trim() || '';
    
    if (handledDialogTitles.has(dialogTitle)) {
      await sleep(500);
      maxAttempts--;
      continue;
    }
    
    dialogCount++;
    const stepId = `dialog-${dialogCount}`;
    const isCategory = dialog.textContent.includes('Vos principales catégories') || 
                       dialogTitle.includes('quelle sorte d\'article');
    
    DebugPanel.addStep(stepId, isCategory ? 'Selection categorie' : 'Details article');
    DebugPanel.updateStep(stepId, 'running', { dialog: dialogTitle });
    
    try {
      const result = await handleCategoryDialog(categoryData, isDigital);
      DebugPanel.updateStep(stepId, 'success', result || 'Dialog traite');
    } catch (e) {
      DebugPanel.updateStep(stepId, 'error', e.message);
    }
    
    handledDialogTitles.add(dialogTitle);
    await sleep(1500);
    maxAttempts--;
  }
}

// Handle the category selection dialog
async function handleCategoryDialog(categoryData, isDigital = false) {
  const dialog = document.querySelector('[data-wt-dialog-root="true"]');
  if (!dialog) return { skipped: true };

  const dialogTitle = dialog.querySelector('.wt-dialog__header__heading')?.textContent || '';
  const dialogContent = dialog.textContent || '';

  if (dialogContent.includes('Vos principales catégories') || 
      dialogTitle.includes('quelle sorte d\'article') || 
      dialogTitle.includes('Quelle sorte') ||
      dialog.querySelector('.le-category-action-group')) {
    return await handleCategorySelectionDialog(dialog, categoryData);
  } else if (dialogTitle.includes('Parlez-nous') || dialogTitle.includes('ensuite de votre article')) {
    return await handleArticleDetailsDialog(dialog, categoryData, isDigital);
  } else {
    return await handleCategorySelectionDialog(dialog, categoryData);
  }
}

// Handle the first dialog - category selection ("Vos principales catégories")
async function handleCategorySelectionDialog(dialog, categoryData) {
  const result = { action: 'category_selection' };
  
  if (!categoryData) {
    await clickContinueButton(dialog);
    return { ...result, skipped: true };
  }
  
  let found = false;
  let matchedCategory = null;
  
  // Primary: search by category name
  if (categoryData.etsy_name) {
    const searchName = categoryData.etsy_name.toLowerCase().trim();
    result.searchName = searchName;
    
    let checkboxes = dialog.querySelectorAll('input[type="checkbox"][id^="category-"]');
    if (checkboxes.length === 0) {
      checkboxes = document.querySelectorAll('input[type="checkbox"][id^="category-"]');
    }
    result.checkboxesFound = checkboxes.length;
    
    // First pass: exact match on h2 title
    for (const checkbox of checkboxes) {
      const label = document.querySelector(`label[for="${checkbox.id}"]`);
      if (!label) continue;
      
      const h2 = label.querySelector('h2.wt-text-title');
      if (!h2) continue;
      
      const categoryName = h2.textContent.toLowerCase().trim();
      
      if (categoryName === searchName || 
          categoryName.includes(searchName) || 
          searchName.includes(categoryName)) {
        checkbox.click();
        found = true;
        matchedCategory = { id: checkbox.id, name: h2.textContent.trim() };
        await sleep(300);
        break;
      }
    }
    
    // Second pass: match on full taxonomy path
    if (!found) {
      for (const checkbox of checkboxes) {
        const label = document.querySelector(`label[for="${checkbox.id}"]`);
        if (!label) continue;
        
        if (label.textContent.toLowerCase().includes(searchName)) {
          checkbox.click();
          found = true;
          matchedCategory = { id: checkbox.id, name: 'taxonomy match' };
          await sleep(300);
          break;
        }
      }
    }
  }
  
  // Fallback: search by ID
  if (!found && categoryData.etsy_id) {
    const checkbox = document.getElementById(categoryData.etsy_id);
    if (checkbox) {
      checkbox.click();
      found = true;
      matchedCategory = { id: categoryData.etsy_id, name: 'ID fallback' };
      await sleep(300);
    }
  }
  
  result.found = found;
  result.matchedCategory = matchedCategory;
  
  if (!found && categoryData.etsy_name) {
    result.error = `Categorie "${categoryData.etsy_name}" non trouvee`;
  }
  
  await clickContinueButton(dialog);
  return result;
}

// Handle the second dialog - article details (type, who made it, etc.)
async function handleArticleDetailsDialog(dialog, categoryData, isDigital = false) {
  const result = { action: 'article_details', selections: {} };
  
  // 1. Select article type: digital or physical
  const typeValue = isDigital ? 'download' : 'physical';
  const typeRadio = dialog.querySelector(`input[name="listing_type_options_group"][value="${typeValue}"]`);
  if (typeRadio) {
    typeRadio.click();
    result.selections.type = typeValue;
    await sleep(300);
  }
  
  // 2. Select "Who made it"
  const whoMadeRadios = dialog.querySelectorAll('input[name="whoMade"]');
  if (whoMadeRadios.length >= 1) {
    if (isDigital) {
      // Pour STL: "Je l'ai fait moi-meme" (option 1)
      whoMadeRadios[0].click();
      result.selections.whoMade = 'je lai fait';
    } else {
      // Pour physique: "Une autre personne ou entreprise" (option 3)
      if (whoMadeRadios.length >= 3) {
        whoMadeRadios[2].click();
        result.selections.whoMade = 'autre personne';
      }
    }
    await sleep(300);
  }
  
  // 3. Select "What is it": "Un produit fini" (first option)
  const isSupplyRadios = dialog.querySelectorAll('input[name="isSupply"]');
  if (isSupplyRadios.length >= 1) {
    isSupplyRadios[0].click();
    result.selections.isSupply = 'produit fini';
    await sleep(300);
  }
  
  // 4. Select "When made": "2020 - 2026"
  const whenMadeSelect = dialog.querySelector('#when-made-select');
  if (whenMadeSelect) {
    whenMadeSelect.value = '2020_2026';
    whenMadeSelect.dispatchEvent(new Event('change', { bubbles: true }));
    result.selections.whenMade = '2020-2026';
    await sleep(300);
  }
  
  // 5. Handle production partners - SKIP pour digital
  if (!isDigital) {
    const partnersResult = await handleProductionPartners(dialog);
    result.selections.productionPartners = partnersResult;
  } else {
    console.log('🐀 Digital product - skipping production partners');
    result.selections.productionPartners = { skipped: 'digital product' };
  }

  await clickContinueButton(dialog);
  return result;
}

// Handle production partners selection
async function handleProductionPartners(dialog) {
  const result = { action: 'production_partners' };
  
  // Find the production partners button
  let selectPartnersBtn = dialog.querySelector('button[data-change-production-partners-button="true"]') ||
                          dialog.querySelector('button[aria-controls="production-partners-overlay"]');
  
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
    result.skipped = 'button not found';
    return result;
  }
  
  selectPartnersBtn.click();
  result.buttonClicked = true;
  
  // Wait for overlay
  let overlay = null;
  for (let i = 0; i < 20; i++) {
    overlay = document.querySelector('.wt-overlay__modal.wt-overlay--animation-done');
    if (overlay) break;
    await sleep(200);
  }
  
  if (!overlay) {
    result.error = 'overlay not found';
    return result;
  }
  
  // Wait for checkbox
  let firstCheckbox = null;
  for (let i = 0; i < 20; i++) {
    firstCheckbox = overlay.querySelector('input[type="checkbox"]');
    if (firstCheckbox) break;
    await sleep(200);
  }
  
  if (firstCheckbox && firstCheckbox.getAttribute('aria-checked') !== 'true') {
    firstCheckbox.click();
    result.checkboxClicked = true;
    await sleep(500);
  } else {
    result.checkboxAlreadyChecked = true;
  }
  
  // Click "Terminé" button
  await sleep(300);
  let termineBtn = document.querySelector('button[data-apply-button="true"]');
  for (let i = 0; i < 5 && !termineBtn; i++) {
    await sleep(200);
    termineBtn = document.querySelector('button[data-apply-button="true"]');
  }
  
  if (termineBtn) {
    termineBtn.click();
    result.completed = true;
    await sleep(500);
  }
  
  return result;
}

// Helper to click the Continue button
async function clickContinueButton(dialog) {
  await sleep(500);
  
  const footer = dialog.querySelector('.wt-dialog__footer__container');
  if (footer) {
    const continueBtn = footer.querySelector('.wt-btn--primary');
    if (continueBtn && continueBtn.textContent.trim() === 'Continuer') {
      continueBtn.click();
      await sleep(1000);
      return;
    }
  }
  
  const buttons = dialog.querySelectorAll('.wt-btn--primary');
  for (const btn of buttons) {
    if (btn.textContent.includes('Continuer')) {
      btn.click();
      await sleep(1000);
      return;
    }
  }
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

// Fetch product data from LesRats API via service worker
async function fetchProductData(apiUrl, productId) {
  try {
    const data = await chrome.runtime.sendMessage({
      action: 'fetchEtsyData',
      apiUrl: apiUrl,
      productId: productId,
      needsToken: true
    });
    
    return data.success ? data.data : null;
  } catch (error) {
    console.error('🐀 Error fetching product data:', error);
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

  // Only select shipping profile for physical products
  if (!product.is_digital) {
    await selectShippingProfile('Standart');
  } else {
    console.log('🐀 Digital product - skipping shipping profile');
  }

  // Upload images if available
  if (product.images && product.images.length > 0) {
    await uploadImages(product.images, product.title);
  }

  // Fill size variations if available
  if (product.sizes && product.sizes.length > 0) {
    await fillVariations(product.sizes);
  }

  // Show STL upload reminder for digital products
  if (product.is_digital) {
    showDigitalFileReminder(product.source_url);
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

// Show reminder for digital file upload (STL)
function showDigitalFileReminder(sourceUrl) {
  // Remove existing reminder
  const existing = document.getElementById('lesrats-stl-reminder');
  if (existing) existing.remove();

  const panel = document.createElement('div');
  panel.id = 'lesrats-stl-reminder';
  panel.innerHTML = `
    <div style="
      position: fixed;
      top: 20px;
      left: 20px;
      z-index: 999999;
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.3);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      max-width: 380px;
      overflow: hidden;
    ">
      <div style="
        background: linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%);
        color: white;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
      ">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 20px;">📁</span>
          <span style="font-weight: bold;">Produit Digital (STL)</span>
        </div>
        <button id="lesrats-close-stl-reminder" style="
          background: rgba(255,255,255,0.2);
          border: none;
          color: white;
          width: 24px;
          height: 24px;
          border-radius: 50%;
          cursor: pointer;
          font-size: 14px;
        ">✕</button>
      </div>
      <div style="padding: 16px;">
        <div style="
          background: #F5F3FF;
          border-radius: 8px;
          padding: 12px;
          margin-bottom: 12px;
        ">
          <p style="margin: 0 0 8px 0; font-weight: 600; color: #5B21B6;">
            N'oubliez pas d'uploader le fichier STL !
          </p>
          <p style="margin: 0; font-size: 13px; color: #6B7280;">
            Etsy necessite le fichier STL pour les produits digitaux. Telechargez-le depuis Printables et uploadez-le dans la section "Fichiers numeriques".
          </p>
        </div>
        ${sourceUrl ? `
        <a href="${sourceUrl}" target="_blank" style="
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          background: #7C3AED;
          color: white;
          text-decoration: none;
          padding: 10px 16px;
          border-radius: 8px;
          font-weight: 500;
          font-size: 14px;
        ">
          <span>🔗</span>
          Ouvrir Printables pour telecharger le STL
        </a>
        ` : ''}
        <div style="margin-top: 12px; padding: 10px; background: #FEF3C7; border-radius: 8px; font-size: 12px; color: #92400E;">
          💡 Astuce: Cherchez la section "Fichiers numeriques" dans le formulaire Etsy pour uploader votre fichier STL.
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(panel);

  // Close button handler
  document.getElementById('lesrats-close-stl-reminder')?.addEventListener('click', () => {
    panel.remove();
  });
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

// Fill size variations in Etsy listing form
async function fillVariations(sizes) {
  if (!sizes || sizes.length === 0) return;
  console.log('🐀 Filling size variations:', sizes);

  // Find the "Add a variation" button
  const addBtn = findElement([
    '[data-testid="add-variation-button"]',
    'button[aria-label*="variation" i]',
    'button[aria-label*="Variation" i]',
  ]);

  // Fallback: search by text content
  if (!addBtn) {
    const allButtons = document.querySelectorAll('button, [role="button"]');
    for (const btn of allButtons) {
      const text = btn.textContent?.trim().toLowerCase() || '';
      if (text.includes('add a variation') || text.includes('ajouter une variation') || text.includes('variation')) {
        btn.click();
        console.log('🐀 Clicked variation button (text match):', btn.textContent.trim());
        await sleep(1000);
        break;
      }
    }
  } else {
    addBtn.click();
    console.log('🐀 Clicked variation button (selector match)');
    await sleep(1000);
  }

  // Look for "Size" option in the modal/dialog
  const sizeOption = Array.from(document.querySelectorAll('input[type="radio"], input[type="checkbox"], button, label, [role="option"]'))
    .find(el => {
      const text = (el.textContent || el.value || el.getAttribute('aria-label') || '').trim().toLowerCase();
      return text === 'size' || text === 'taille' || text === 'sizes';
    });

  if (sizeOption) {
    sizeOption.click();
    console.log('🐀 Selected Size variation type');
    await sleep(800);
  } else {
    console.warn('🐀 Size option not found in variation dialog');
    return;
  }

  // Fill each size value
  for (const size of sizes) {
    // Look for the input field to type size values
    const input = findElement([
      'input[placeholder*="size" i]',
      'input[placeholder*="option" i]',
      'input[placeholder*="taille" i]',
      '[data-testid="variation-option-input"]',
      'input[aria-label*="variation" i]',
    ]);

    if (input) {
      await setInputValue(input, size);
      // Press Enter to confirm the size value
      input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', keyCode: 13, bubbles: true }));
      input.dispatchEvent(new KeyboardEvent('keyup', { key: 'Enter', keyCode: 13, bubbles: true }));
      await sleep(400);
      console.log('🐀 Added size:', size);
    } else {
      console.warn('🐀 Size input field not found for:', size);
      break;
    }
  }

  // Try to confirm/close the dialog
  await sleep(500);
  const saveBtn = Array.from(document.querySelectorAll('button'))
    .find(btn => {
      const text = btn.textContent?.trim().toLowerCase() || '';
      return text.includes('save') || text.includes('done') || text.includes('apply') || text.includes('enregistrer');
    });
  if (saveBtn) {
    saveBtn.click();
    console.log('🐀 Confirmed variations dialog');
  }

  console.log('🐀 Size variations filled:', sizes.length, 'sizes');
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
