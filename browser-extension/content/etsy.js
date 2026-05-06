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
    
    // Fill category inline (new Etsy UI — no more dialog)
    await runStep('fill-category', 'Selection categorie', async () => {
      const result = await fillCategoryInline(categoryName);
      return result;
    });

    // Fill article details inline (listing type, who made, when made)
    await runStep('fill-article-details', 'Details article', async () => {
      const result = await fillArticleDetailsInline(isDigital);
      return result;
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

// Fill the category field inline (new Etsy UI — category is in the main form, not a dialog)
// Strategy A: match against the shop's pre-configured category checkboxes
// Strategy B: type into the search input and click the first dropdown result
async function fillCategoryInline(categoryData) {
  if (!categoryData) return { skipped: true, reason: 'no category data' };

  // Wait up to 5s for #field-category to appear
  let fieldCategory = null;
  for (let i = 0; i < 10; i++) {
    fieldCategory = document.querySelector('#field-category');
    if (fieldCategory) break;
    await sleep(500);
  }

  if (!fieldCategory) return { skipped: true, reason: 'no #field-category found' };

  const searchName = String(categoryData).toLowerCase().trim();

  // Strategy A: click a matching checkbox from the shop's pre-configured categories
  const checkboxes = fieldCategory.querySelectorAll('input[type="checkbox"][id^="category-"]');
  for (const checkbox of checkboxes) {
    const label = document.querySelector(`label[for="${checkbox.id}"]`);
    if (!label) continue;
    const h2 = label.querySelector('h2.wt-text-title');
    if (!h2) continue;
    const name = h2.textContent.toLowerCase().trim();
    if (name === searchName || name.includes(searchName) || searchName.includes(name)) {
      checkbox.click();
      await sleep(300);
      return { action: 'category_checkbox', matched: h2.textContent.trim() };
    }
  }

  // Strategy B: type into search input and click the first dropdown result
  const searchInput = fieldCategory.querySelector('#category-field-search');
  if (!searchInput) return { skipped: true, reason: 'no search input and no checkbox match' };

  // Use native setter to trigger React's onChange
  const nativeInputSetter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
  searchInput.focus();
  await sleep(100);
  nativeInputSetter.call(searchInput, categoryData);
  searchInput.dispatchEvent(new Event('input', { bubbles: true }));
  searchInput.dispatchEvent(new Event('change', { bubbles: true }));

  // Wait for dropdown to populate
  let dropdown = null;
  for (let i = 0; i < 10; i++) {
    await sleep(300);
    dropdown = fieldCategory.querySelector('ul[role="listbox"]');
    if (dropdown && dropdown.querySelectorAll('li[role="option"]').length > 0) break;
    // Also check for the panel being visible (aria-hidden may toggle)
    const panel = fieldCategory.querySelector('.le-category-search__panel');
    if (panel && panel.getAttribute('aria-hidden') !== 'true') {
      dropdown = panel.querySelector('ul[role="listbox"]');
      if (dropdown && dropdown.querySelectorAll('li[role="option"]').length > 0) break;
    }
  }

  if (dropdown) {
    const options = dropdown.querySelectorAll('li[role="option"]');
    if (options.length > 0) {
      simulateRealClick(options[0]);
      await sleep(400);
      return { action: 'category_search', matched: options[0].textContent.trim() };
    }
  }

  return { skipped: true, reason: 'no dropdown results for: ' + categoryData };
}

// Fill article details inline (new Etsy UI — listing type, who made it, when made are in the main form)
async function fillArticleDetailsInline(isDigital = false) {
  const result = { action: 'article_details_inline', selections: {} };

  // Wait briefly for the fields to be present
  await sleep(500);

  // 1. Listing type: physical or digital
  const typeValue = isDigital ? 'download' : 'physical';
  const typeRadio = document.querySelector(`#field-listingType input[name="listing_type_options_group"][value="${typeValue}"]`);
  if (typeRadio && !typeRadio.checked) {
    typeRadio.click();
    result.selections.type = typeValue;
    await sleep(300);
  } else if (typeRadio) {
    result.selections.type = typeValue + ' (already selected)';
  }

  // 2. "Who made it" — only present for physical products in some flows
  const whoMadeRadios = document.querySelectorAll('input[name="whoMade"]');
  if (whoMadeRadios.length >= 1) {
    if (isDigital) {
      whoMadeRadios[0].click();
      result.selections.whoMade = 'je lai fait';
    } else {
      if (whoMadeRadios.length >= 3) {
        whoMadeRadios[2].click();
        result.selections.whoMade = 'autre personne';
      }
    }
    await sleep(300);
  }

  // 3. "What is it" — "Un produit fini" (first option)
  const isSupplyRadios = document.querySelectorAll('input[name="isSupply"]');
  if (isSupplyRadios.length >= 1) {
    isSupplyRadios[0].click();
    result.selections.isSupply = 'produit fini';
    await sleep(300);
  }

  // 4. "When made" — select "2020 - 2026"
  const whenMadeSelect = document.querySelector('#when-made-select');
  if (whenMadeSelect) {
    whenMadeSelect.value = '2020_2026';
    whenMadeSelect.dispatchEvent(new Event('change', { bubbles: true }));
    result.selections.whenMade = '2020-2026';
    await sleep(300);
  }

  return result;
}

// Wait for Etsy's React app to load (inline form fields)
function waitForPageLoad() {
  return new Promise((resolve) => {
    // Etsy uses React — wait for the inline category field or title input to appear
    const checkInterval = setInterval(() => {
      // New inline UI: category search input
      const categoryField = document.querySelector('#field-category, #category-field-search');
      // Or title input (always present once the form is ready)
      const titleInput = document.querySelector('#listing-title-input, textarea[name="title"], #field-title textarea');
      
      if (categoryField || titleInput) {
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

// Fill product attributes: primary color, secondary color, materials
async function fillProductAttributes(product) {
  try {
    // Primary color
    if (product.main_color) {
      const primarySelect = document.querySelector('select[name="primary_color"], #primary-color-select, [data-testid="primary-color-select"] select');
      if (primarySelect) {
        const option = Array.from(primarySelect.options).find(o => o.text.toLowerCase() === product.main_color.toLowerCase());
        if (option) {
          primarySelect.value = option.value;
          primarySelect.dispatchEvent(new Event('change', { bubbles: true }));
          await sleep(300);
          console.log('🐀 Primary color set:', product.main_color);
        }
      }
    }

    // Secondary color
    if (product.secondary_color) {
      const secondarySelect = document.querySelector('select[name="secondary_color"], #secondary-color-select, [data-testid="secondary-color-select"] select');
      if (secondarySelect) {
        const option = Array.from(secondarySelect.options).find(o => o.text.toLowerCase() === product.secondary_color.toLowerCase());
        if (option) {
          secondarySelect.value = option.value;
          secondarySelect.dispatchEvent(new Event('change', { bubbles: true }));
          await sleep(300);
          console.log('🐀 Secondary color set:', product.secondary_color);
        }
      }
    }

    // Materials (multi-select or tag input)
    if (product.materials && product.materials.length > 0) {
      // Try multi-select
      const materialsSelect = document.querySelector('select[name="materials"], select[name="material"], #materials-select');
      if (materialsSelect && materialsSelect.multiple) {
        Array.from(materialsSelect.options).forEach(o => {
          o.selected = product.materials.some(m => m.toLowerCase() === o.text.toLowerCase());
        });
        materialsSelect.dispatchEvent(new Event('change', { bubbles: true }));
        await sleep(300);
        console.log('🐀 Materials set (multi-select):', product.materials);
      } else {
        // Try tag-style input (type material name + press Enter)
        const materialInput = document.querySelector('input[placeholder*="aterial"], input[name*="aterial"]');
        if (materialInput) {
          for (const mat of product.materials.slice(0, 5)) {
            materialInput.value = mat;
            materialInput.dispatchEvent(new Event('input', { bubbles: true }));
            await sleep(500);
            materialInput.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
            await sleep(300);
          }
          console.log('🐀 Materials set (input):', product.materials);
        }
      }
    }

  } catch (e) {
    console.log('🐀 fillProductAttributes error (non-blocking):', e.message);
  }
}

// Native setters — bypasse les wrappers React
// Déclarés en lazy (pas au top-level) pour ne pas crasher le script au chargement
function nativeSet(el, value) {
  try {
    const proto = el instanceof HTMLSelectElement ? HTMLSelectElement.prototype : HTMLInputElement.prototype;
    const setter = Object.getOwnPropertyDescriptor(proto, 'value')?.set;
    if (setter) {
      setter.call(el, value);
    } else {
      el.value = value; // fallback si le getter n'existe pas
    }
  } catch(e) {
    el.value = value; // fallback safe
  }
}

// Attend qu'un sélecteur apparaisse dans le DOM (polling toutes les 100ms)
async function waitForElement(selector, timeout = 5000) {
  const start = Date.now();
  while (Date.now() - start < timeout) {
    const el = document.querySelector(selector);
    if (el) return el;
    await sleep(100);
  }
  return null;
}

// Lit le texte visible d'un bouton :
//   - spans sans SVG (pattern matériaux/couleurs)
//   - paragraphes (pattern tailles : <p>M</p>)
//   - textContent brut en fallback
function readButtonText(btn) {
  for (const span of btn.querySelectorAll('span')) {
    if (span.querySelector('svg')) continue;
    const t = span.textContent?.trim();
    if (t) return t;
  }
  const p = btn.querySelector('p');
  if (p) return p.textContent?.trim() || '';
  return btn.textContent?.trim() || '';
}

// Détecte la saison depuis le titre/description du produit
function detectSeason(title = '', description = '') {
  const text = (title + ' ' + description).toLowerCase();
  if (/hiver|winter|froid|cold|ski|snow|neige|duvet|polaire|fleece/.test(text)) return '475';  // Hiver
  if (/\bété\b|summer|estival|beach|plage/.test(text))                           return '466';  // Été
  if (/printemps|spring/.test(text))                                              return '462';  // Printemps
  if (/automne|autumn|\bfall\b/.test(text))                                       return '1065'; // Automne
  return '3454'; // Toutes saisons
}

// Clique le bouton "Ajouter des variations" dans la section item-options.
// Ce bouton DOIT être cliqué en premier pour révéler la section taille inline.
async function clickAddVariationsButton() {
  const section = document.querySelector('[data-testid="item-options-variations-section"]');
  if (!section) return false;
  for (const btn of section.querySelectorAll('button')) {
    const text = btn.textContent?.trim().toLowerCase() || '';
    if (text.includes('ajouter des variations') || text.includes('add variation')) {
      simulateRealClick(btn);
      await sleep(800);
      return true;
    }
  }
  return false;
}

// Remplit un champ typeahead d'attribut Etsy (matériaux, couleur secondaire, motif…) :
//   1. Native setter → React détecte la valeur et ouvre le menu filtré
//   2. simulateRealClick sur le bouton correspondant dans le menu
async function fillAttributeTypeahead(containerSelector, value) {
  const container = document.querySelector(containerSelector);
  if (!container) return false;

  const input = container.querySelector('input[id^="typeahead-input-"]');
  if (!input) return false;

  input.focus();
  await sleep(100);
  nativeSet(input, value);
  input.dispatchEvent(new Event('input',  { bubbles: true }));
  input.dispatchEvent(new Event('change', { bubbles: true }));
  await sleep(600);

  for (const btn of container.querySelectorAll('button[role="menuitemradio"], button[role="option"]')) {
    if (readButtonText(btn).toLowerCase() === value.toLowerCase()) {
      simulateRealClick(btn);
      await sleep(300);
      console.log('🐀 Attribute set:', containerSelector, '→', value);
      return true;
    }
  }
  return false;
}

// Remplissage de l'attribut Taille via le bouton "J'en propose plusieurs".
// containerSelector : ID du conteneur Taille (varie selon catégorie).
async function fillSizeAttribute(sizes, containerSelector = '#field-attributes-attribute-295') {
  const container = document.querySelector(containerSelector);
  if (!container) {
    console.log('🐀 Conteneur Taille introuvable:', containerSelector);
    return;
  }

  const triggerBtn = container.querySelector('button[data-variations-overlay-trigger="true"]');
  if (!triggerBtn) {
    console.log('🐀 Bouton "J\'en propose plusieurs" introuvable dans', containerSelector);
    return;
  }

  simulateRealClick(triggerBtn);
  console.log('🐀 Cliqué "J\'en propose plusieurs"');
  await sleep(1200);

  // Chercher l'overlay avec plusieurs sélecteurs possibles
  const overlaySelectors = [
    '#le-variations-overlay',
    '[role="dialog"]',
    '.wt-overlay__modal',
    '[data-overlay]',
  ];
  let overlay = null;
  for (const sel of overlaySelectors) {
    overlay = document.querySelector(sel);
    if (overlay) { console.log('🐀 Overlay trouvé:', sel); break; }
  }
  if (!overlay) {
    // Attendre un peu plus — React peut être lent
    await sleep(2000);
    for (const sel of overlaySelectors) {
      overlay = document.querySelector(sel);
      if (overlay) { console.log('🐀 Overlay trouvé (2e tentative):', sel); break; }
    }
  }
  if (!overlay) {
    console.log('🐀 Aucun overlay détecté après clic — capture le HTML (copy(document.body.outerHTML)) avec l\'overlay ouvert');
    return;
  }
  await sleep(500);

  // Si l'overlay montre une liste de types de variation, sélectionner "Taille"
  const overlayButtons = Array.from(overlay.querySelectorAll('button'));
  const hasTailleType = overlayButtons.some(b => {
    const t = (b.textContent?.trim() || '').toLowerCase();
    return t === 'taille' || t === 'size' || t === 'größe';
  });
  if (hasTailleType) {
    console.log('🐀 Sélection du type Taille dans l\'overlay');
    await selectStructuredVariationType(['taille', 'size', 'größe'], ['taille de bague', 'ring size', 'ringgröße']);
    await sleep(800);
  }

  // Sélectionner l'échelle US (sélecteur générique, pas seulement #le-structured-variation-scale-select)
  const scaleSelect = document.querySelector('#le-structured-variation-scale-select, [id*="scale-select"]');
  if (scaleSelect) {
    await selectUSScale('clothing');
    await sleep(800);
  } else {
    console.log('🐀 Select échelle non trouvé dans l\'overlay');
  }

  // Ajouter chaque taille via le typeahead de l'overlay
  await toggleSizeCheckboxes(sizes);

  // Fermer : "Terminé" puis "Appliquer"
  await sleep(500);
  await clickOverlayFooterButton();
  await sleep(2000);
  await clickOverlayFooterButton();

  console.log('🐀 Tailles attribut remplies:', sizes);
}

// Remplissage des attributs spécifiques à "Vestes et manteaux"
async function fillVestesManteaux(product) {
  try {
    // Saison — <select> React contrôlé : native setter + change
    const saisonSelect = document.querySelector('select#attribute-475');
    if (saisonSelect) {
      const season = detectSeason(product.title, product.description);
      nativeSet(saisonSelect, season);
      saisonSelect.dispatchEvent(new Event('change', { bubbles: true }));
      await sleep(300);
      console.log('🐀 Saison:', season);
    }

    // Tailles — mapping AliExpress → Etsy puis overlay multi-sélection
    if (product.sizes && product.sizes.length > 0) {
      const etsySizes = [...new Set(product.sizes.map(mapSizeToEtsy))];
      await fillSizeAttribute(etsySizes);
    }

    // Matériaux (attribute-357) — typeahead, max 3
    if (product.materials && product.materials.length > 0) {
      for (const mat of product.materials.slice(0, 3)) {
        await fillAttributeTypeahead('#field-attributes-attribute-357', mat);
      }
    }

    // Couleur secondaire (attribute-271) — typeahead
    if (product.secondary_color) {
      await fillAttributeTypeahead('#field-attributes-attribute-271', product.secondary_color);
    }

  } catch (e) {
    console.log('🐀 fillVestesManteaux error:', e.message);
  }
}

// Remplissage des attributs spécifiques à "Pantalons"
async function fillPantalons(product) {
  try {
    // Style de pantalon — <select> React contrôlé
    const styleSelect = document.querySelector('select#attribute-441');
    if (styleSelect && product.pant_style) {
      nativeSet(styleSelect, product.pant_style);
      styleSelect.dispatchEvent(new Event('change', { bubbles: true }));
      await sleep(300);
      console.log('🐀 Style pantalon:', product.pant_style);
    }

    // Tailles — échelle US (49) puis overlay via "J'en propose plusieurs"
    // Le select d'échelle (attributes-4-scale-select) DOIT être sélectionné avant
    // pour que le champ Taille apparaisse dans #field-attributes-attribute-299
    if (product.sizes && product.sizes.length > 0) {
      const echelleSelect = document.querySelector('select#attributes-4-scale-select');
      if (echelleSelect) {
        nativeSet(echelleSelect, '49');
        echelleSelect.dispatchEvent(new Event('change', { bubbles: true }));
        await sleep(600);
      }
      const etsySizes = [...new Set(product.sizes.map(mapSizeToEtsy))];
      await fillSizeAttribute(etsySizes, '#field-attributes-attribute-299');
    }

    // Matériaux (attribute-357) — typeahead, max 5
    if (product.materials && product.materials.length > 0) {
      for (const mat of product.materials.slice(0, 5)) {
        await fillAttributeTypeahead('#field-attributes-attribute-357', mat);
      }
    }

    // Couleur principale (attribute-2) — typeahead
    if (product.main_color) {
      await fillAttributeTypeahead('#field-attributes-attribute-2', product.main_color);
    }

    // Couleur secondaire (attribute-271) — typeahead
    if (product.secondary_color) {
      await fillAttributeTypeahead('#field-attributes-attribute-271', product.secondary_color);
    }

    // Style de vêtements (attribute-378) — typeahead
    if (product.clothing_style) {
      await fillAttributeTypeahead('#field-attributes-attribute-378', product.clothing_style);
    }

    // Occasion (attribute-3) — typeahead
    if (product.occasion) {
      await fillAttributeTypeahead('#field-attributes-attribute-3', product.occasion);
    }

  } catch (e) {
    console.log('🐀 fillPantalons error:', e.message);
  }
}

// Dispatcher — route vers la bonne fonction selon la catégorie Etsy du produit
async function fillCategoryAttributes(product) {
  const cat = (product.etsy_category || '').toLowerCase();
  if (!cat) return;

  if (cat.includes('veste') || cat.includes('manteau') || cat.includes('jacket') || cat.includes('coat')) {
    await fillVestesManteaux(product);
  } else if (cat.includes('pantalon') || cat.includes('trouser') || cat.includes('pant')) {
    await fillPantalons(product);
  }
  // Ajouter d'autres catégories ici au fur et à mesure
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

  // Les tailles sont gérées par fillCategoryAttributes — jamais via le bouton "Ajouter des variations"

  // Fill product attributes (color, materials)
  await fillProductAttributes(product);

  // Fill category-specific attributes (season, size scale, category fields)
  await fillCategoryAttributes(product);

  // Show STL upload reminder for digital products
  if (product.is_digital) {
    showDigitalFileReminder(product.source_url);
  }

  // Scroll to the image upload area so user can drag & drop images
  await sleep(500);
  const imageUploadArea = document.querySelector('#field-listingImages .wt-upload__area .wt-display-flex-xs.wt-justify-content-center.wt-align-items-center');
  if (imageUploadArea) {
    imageUploadArea.scrollIntoView({ behavior: 'smooth', block: 'center' });
    console.log('🐀 Scrolled to image upload area');
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
// sizeType: 'ring', 'clothing', or 'custom'
async function fillVariations(sizes, sizeType = 'custom') {
  if (!sizes || sizes.length === 0) return;
  console.log('🐀 Filling size variations:', sizes, 'type:', sizeType);

  // Step 1: Click "Add a variation" button to open the type selection modal
  const addVariationClicked = await clickAddVariation();
  if (!addVariationClicked) {
    console.warn('🐀 Could not open variation dialog');
    return;
  }

  // Step 2: Select the variation type in the modal
  if (sizeType === 'ring') {
    await selectStructuredVariationType(['taille de bague', 'ring size', 'ringgröße']);
  } else if (sizeType === 'clothing') {
    await selectStructuredVariationType(['taille', 'size', 'größe'], ['taille de bague', 'ring size', 'ringgröße']);
  } else {
    // Custom: use "Create your own variation"
    await selectCustomVariation();
    await fillCustomSizes(sizes);
    return;
  }

  // Step 3: Select scale (always US)
  await sleep(800);
  await selectUSScale(sizeType);

  // Step 4: Wait for size options to load, then toggle each size
  await sleep(800);
  await toggleSizeCheckboxes(sizes);

  // Step 5: Click the overlay footer button — first click = "Terminé", second click = "Appliquer"
  // Both are the same button at #le-variations-overlay .wt-overlay__footer__action > button
  await sleep(500);
  await clickOverlayFooterButton();
  await sleep(2000);
  await clickOverlayFooterButton();

  console.log('🐀 Size variations filled:', sizes.length, 'sizes');
}

// Click the footer action button inside #le-variations-overlay
// This button is "Terminé" first, then becomes "Appliquer" after first click
async function clickOverlayFooterButton() {
  for (let attempt = 0; attempt < 5; attempt++) {
    const btn = document.querySelector('#le-variations-overlay .wt-overlay__footer__action button');

    if (!btn) {
      console.warn('🐀 Overlay footer button not found, attempt', attempt + 1);
      await sleep(800);
      continue;
    }

    if (btn.disabled || btn.getAttribute('aria-disabled') === 'true') {
      console.warn('🐀 Overlay footer button disabled, attempt', attempt + 1);
      await sleep(800);
      continue;
    }

    const btnText = btn.textContent?.trim();

    // Use MAIN world to scroll overlay + click
    document.dispatchEvent(new CustomEvent('lesrats-scroll-and-click', {
      detail: { selector: '#le-variations-overlay .wt-overlay__footer__action button', text: btnText }
    }));
    console.log('🐀 Dispatched scroll-and-click for:', btnText);
    return true;
  }

  console.warn('🐀 Overlay footer button not found after retries');
  return false;
}

// Click the "Add a variation" button on the Etsy form
async function clickAddVariation() {
  // Search by text content - matches "Ajouter des variations" / "Add a variation" etc.
  const allButtons = document.querySelectorAll('button, [role="button"]');
  for (const btn of allButtons) {
    const text = btn.textContent?.trim().toLowerCase() || '';
    if (text.includes('ajouter des variations') || text.includes('add a variation') || text.includes('ajouter une variation') || text.includes('add variation')) {
      simulateRealClick(btn);
      console.log('🐀 Clicked variation button:', btn.textContent.trim());
      await sleep(1000);
      return true;
    }
  }

  // Fallback: selector-based
  const addBtn = findElement([
    '[data-testid="add-variation-button"]',
    'button[aria-label*="variation" i]',
  ]);

  if (addBtn) {
    simulateRealClick(addBtn);
    console.log('🐀 Clicked variation button (selector match)');
    await sleep(1000);
    return true;
  }

  console.warn('🐀 Add variation button not found');
  return false;
}

// Select a structured variation type (ring size, clothing size) from the modal
// matchTexts: texts to match (e.g., ['taille de bague', 'ring size'])
// excludeTexts: texts to exclude (e.g., for clothing, exclude 'taille de bague')
async function selectStructuredVariationType(matchTexts, excludeTexts = []) {
  // Wait for modal to appear
  await sleep(500);

  // Look for buttons in the variation type modal
  const buttons = document.querySelectorAll('.wt-overlay__modal button, [role="dialog"] button');

  for (const btn of buttons) {
    const text = btn.textContent?.trim().toLowerCase() || '';

    // Skip if text matches exclusion list
    if (excludeTexts.some(ex => text === ex)) continue;

    // Check if text matches any of our target texts
    if (matchTexts.some(mt => text === mt)) {
      simulateRealClick(btn);
      console.log('🐀 Selected variation type:', btn.textContent.trim());
      await sleep(800);
      return true;
    }
  }

  console.warn('🐀 Structured variation type not found, tried:', matchTexts);
  return false;
}

// Select US scale from the scale dropdown
async function selectUSScale(sizeType) {
  const select = document.querySelector('#le-structured-variation-scale-select');
  if (!select) {
    console.warn('🐀 Scale dropdown not found');
    return false;
  }

  // Find the US option
  const options = Array.from(select.options);
  let usOption = null;

  if (sizeType === 'ring') {
    // For rings: look for exact "US" option
    usOption = options.find(o => o.text.trim() === 'US');
  } else if (sizeType === 'clothing') {
    // For clothing: look for option containing "US" (e.g., "Lettre femmes (US)")
    usOption = options.find(o => o.text.includes('US'));
  }

  if (!usOption) {
    // Generic fallback: any option containing "US"
    usOption = options.find(o => o.text.includes('US'));
  }

  if (usOption) {
    select.value = usOption.value;
    select.dispatchEvent(new Event('change', { bubbles: true }));
    select.dispatchEvent(new Event('input', { bubbles: true }));
    console.log('🐀 Selected scale:', usOption.text.trim());
    return true;
  }

  console.warn('🐀 US scale option not found');
  return false;
}

// Add sizes using the typeahead input, then close dropdown before Terminé
async function toggleSizeCheckboxes(sizes) {
  await sleep(600);

  let added = 0;
  const nativeSetter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;

  for (let i = 0; i < sizes.length; i++) {
    const size = sizes[i];

    try {
      const input = document.querySelector(
        '.wt-overlay__modal input.wt-input[id^="typeahead-input"], ' +
        '.wt-overlay__modal input[placeholder*="option" i], ' +
        '.wt-overlay__modal input[placeholder*="Indiquez" i]'
      );

      if (!input) {
        console.warn('🐀 Typeahead input not found for size:', size);
        break;
      }

      // Clear and type
      input.focus();
      await sleep(100);
      nativeSetter.call(input, '');
      input.dispatchEvent(new Event('input', { bubbles: true }));
      await sleep(150);
      nativeSetter.call(input, String(size));
      input.dispatchEvent(new Event('input', { bubbles: true }));
      await sleep(600);

      // Click the matching menuitemradio
      const menuItems = document.querySelectorAll(
        '.wt-overlay__modal button[role="menuitemradio"]:not([data-add-all-options])'
      );
      const normalizedSize = normalizeSize(size);
      let matched = false;

      for (const item of menuItems) {
        const spans = item.querySelectorAll('span');
        let itemText = '';
        for (const span of spans) {
          if (span.querySelector('svg')) continue;
          const t = span.textContent?.trim();
          if (t) { itemText = t; break; }
        }
        if (!itemText) continue;

        if (normalizeSize(itemText) === normalizedSize) {
          item.click();
          matched = true;
          added++;
          console.log('🐀 Added size:', itemText, '(' + (i + 1) + '/' + sizes.length + ')');
          await sleep(300);
          break;
        }
      }

      if (!matched) {
        console.warn('🐀 No match for size:', size);
      }
    } catch (e) {
      console.error('🐀 Error adding size', size, ':', e.message);
    }
  }

  // Close the typeahead dropdown by dispatching mousedown outside it via MAIN world
  // React dropdown menus close on mousedown outside — this is the standard dismiss pattern
  document.dispatchEvent(new CustomEvent('lesrats-dismiss-dropdown', {}));
  console.log('🐀 Dispatched dropdown dismiss');
  await sleep(800);

  console.log('🐀 Added', added, '/', sizes.length, 'sizes');
  return added;
}

// Normalize a size string for comparison
function normalizeSize(size) {
  return String(size).trim().toUpperCase()
    .replace(/½/g, '.5')
    .replace(/,/g, '.')
    .replace(/\s+/g, '');
}

// Convertit les tailles AliExpress vers le format Etsy :
//   - Supprime les apostrophes parasites (L' → L)
//   - Mappe XXL/XXXL/XXXXL+ → 2X/3X/4X (max Etsy)
function mapSizeToEtsy(size) {
  let s = String(size).trim().replace(/'/g, '').toUpperCase();
  const map = {
    'XXL':    '2X',
    '2XL':    '2X',
    'XXXL':   '3X',
    '3XL':    '3X',
    'XXXXL':  '4X',
    '4XL':    '4X',
    'XXXXXL': '4X',
    '5XL':    '4X',
    '6XL':    '4X',
  };
  return map[s] ?? s;
}

// Simulate a click using full mouse event sequence + .click()
// Works for most React buttons from content scripts
function simulateRealClick(el) {
  const rect = el.getBoundingClientRect();
  const x = rect.left + rect.width / 2;
  const y = rect.top + rect.height / 2;
  const opts = { bubbles: true, cancelable: true, clientX: x, clientY: y, button: 0 };
  el.dispatchEvent(new PointerEvent('pointerdown', opts));
  el.dispatchEvent(new MouseEvent('mousedown', opts));
  el.dispatchEvent(new PointerEvent('pointerup', opts));
  el.dispatchEvent(new MouseEvent('mouseup', opts));
  el.dispatchEvent(new MouseEvent('click', opts));
  el.click();
}

// Simulate pressing Enter on a focused element — useful when .click() doesn't work
function simulateEnterKey(el) {
  el.focus();
  const opts = { key: 'Enter', code: 'Enter', keyCode: 13, which: 13, bubbles: true, cancelable: true };
  el.dispatchEvent(new KeyboardEvent('keydown', opts));
  el.dispatchEvent(new KeyboardEvent('keypress', opts));
  el.dispatchEvent(new KeyboardEvent('keyup', opts));
}

// (clickDoneButton removed — replaced by clickOverlayFooterButton)

// Select "Create your own variation" for custom size types
async function selectCustomVariation() {
  await sleep(500);

  // Look for the "Créer votre propre variation" / "Create your own variation" button
  const buttons = document.querySelectorAll('.wt-overlay__modal button');
  for (const btn of buttons) {
    const text = btn.textContent?.trim().toLowerCase() || '';
    if (text.includes('propre variation') || text.includes('your own variation') || text.includes('eigene variation')) {
      btn.click();
      console.log('🐀 Selected custom variation');
      await sleep(800);
      return true;
    }
  }

  console.warn('🐀 Custom variation button not found');
  return false;
}

// Fill sizes using free-form input (for custom/unrecognized size types)
async function fillCustomSizes(sizes) {
  await sleep(500);

  // Type variation name
  const nameInput = findElement([
    'input[placeholder*="variation" i]',
    'input[placeholder*="name" i]',
    'input[placeholder*="nom" i]',
  ]);
  if (nameInput) {
    await setInputValue(nameInput, 'Size');
    await sleep(300);
  }

  // Fill each size value
  for (const size of sizes) {
    const input = findElement([
      'input[placeholder*="option" i]',
      'input[placeholder*="size" i]',
      'input[placeholder*="taille" i]',
      '[data-testid="variation-option-input"]',
      'input[aria-label*="variation" i]',
    ]);

    if (input) {
      await setInputValue(input, size);
      await sleep(100);
      await simulateEnter(input);
      await sleep(400);
      console.log('🐀 Added custom size:', size);
    } else {
      console.warn('🐀 Size input field not found for:', size);
      break;
    }
  }

  // Click done
  await sleep(500);
  await clickOverlayFooterButton();
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
