// Floating import panel for AliExpress product pages
// Dark theme, orange header, rat emoji — matches Etsy DebugPanel
// ALL network calls go through service worker (avoids ad blocker blocking fetch)

(function() {
  'use strict';

  if (document.getElementById('lesrats-ali-panel')) return;

  // Le panel apparait sur TOUTES les pages AliExpress.
  // - Sur une fiche produit (/item/xxx.html) : panel d'import complet.
  // - Ailleurs (accueil, recherche, categories) : FAB + bulle "Cherche un article" pour guider le user.
  const isProductPage = /aliexpress\.com\/item\/[^\/]+\.html/i.test(window.location.href);

  // ============== STATE ==============
  let panelState = {
    isOpen: false,
    shops: [],
    selectedShopId: null,
    productData: null,
    isImporting: false,
    importResult: null,
  };

  // ============== API CONFIG (uses SecureStorage from lib/secure-storage.js) ==============
  async function getApiConfig() {
    const saved = await chrome.storage.local.get(['apiUrl', 'devMode', 'devApiUrl']);
    if (saved.devMode) {
      const devToken = await SecureStorage.getSecure('devApiToken');
      return { apiUrl: saved.devApiUrl || 'http://localhost:8000', apiToken: devToken, isDev: true };
    }
    const apiToken = await SecureStorage.getSecure('apiToken');
    return { apiUrl: saved.apiUrl || 'http://localhost:8000', apiToken, isDev: false };
  }

  // ============== CREATE PANEL ==============
  function createPanel() {
    const icon32 = chrome.runtime.getURL('icons/icon32.png');
    const icon48 = chrome.runtime.getURL('icons/icon48.png');
    const container = document.createElement('div');
    container.id = 'lesrats-ali-panel';
    container.innerHTML = `
      <style>
        #lesrats-ali-panel {
          position: fixed;
          bottom: 20px;
          right: 20px;
          z-index: 999999;
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
          font-size: 13px;
        }
        #lesrats-ali-panel * {
          box-sizing: border-box;
        }

        /* Floating icon */
        .lesrats-ali-fab {
          width: 54px;
          height: 54px;
          background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 26px;
          cursor: pointer;
          box-shadow: 0 4px 16px rgba(249, 115, 22, 0.5);
          transition: transform 0.2s, box-shadow 0.2s;
          user-select: none;
          position: relative;
        }
        .lesrats-ali-fab:hover {
          transform: scale(1.1);
          box-shadow: 0 6px 24px rgba(249, 115, 22, 0.6);
        }
        .lesrats-ali-fab.hidden { display: none; }

        /* Panel content */
        .lesrats-ali-content {
          background: #1a1a2e;
          border-radius: 12px;
          box-shadow: 0 8px 32px rgba(0,0,0,0.5);
          width: 400px;
          max-height: 560px;
          overflow: hidden;
          display: none;
          flex-direction: column;
        }
        .lesrats-ali-content.open {
          display: flex;
        }

        /* Header */
        .lesrats-ali-header {
          background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
          color: white;
          padding: 12px 16px;
          display: flex;
          align-items: center;
          justify-content: space-between;
          flex-shrink: 0;
        }
        .lesrats-ali-header-title {
          display: flex;
          align-items: center;
          gap: 8px;
          font-weight: 600;
          font-size: 14px;
        }
        .lesrats-ali-header-actions {
          display: flex;
          gap: 6px;
        }
        .lesrats-ali-hbtn {
          background: rgba(255,255,255,0.2);
          border: none;
          color: white;
          width: 26px;
          height: 26px;
          border-radius: 6px;
          cursor: pointer;
          font-size: 15px;
          display: flex;
          align-items: center;
          justify-content: center;
          line-height: 1;
        }
        .lesrats-ali-hbtn:hover { background: rgba(255,255,255,0.3); }

        /* Body */
        .lesrats-ali-body {
          padding: 14px;
          overflow-y: auto;
          max-height: 470px;
          flex: 1;
        }

        /* Shop selector */
        .lesrats-ali-label {
          color: #9ca3af;
          font-size: 11px;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          margin-bottom: 6px;
          display: flex;
          align-items: center;
          gap: 6px;
        }
        .lesrats-ali-select {
          width: 100%;
          padding: 8px 10px;
          background: #16213e;
          border: 1px solid #2d3a5c;
          border-radius: 8px;
          color: #e5e7eb;
          font-size: 13px;
          outline: none;
          margin-bottom: 12px;
          cursor: pointer;
        }
        .lesrats-ali-select:focus {
          border-color: #f97316;
        }
        .lesrats-ali-select option {
          background: #16213e;
          color: #e5e7eb;
        }

        /* Import button */
        .lesrats-ali-import-btn {
          width: 100%;
          padding: 14px;
          background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
          color: white;
          border: none;
          border-radius: 10px;
          font-size: 16px;
          font-weight: 700;
          cursor: pointer;
          transition: opacity 0.2s, transform 0.1s;
          margin-bottom: 12px;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          letter-spacing: 0.3px;
        }
        .lesrats-ali-import-btn:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(249,115,22,0.4); }
        .lesrats-ali-import-btn:active { transform: translateY(0); box-shadow: none; }
        .lesrats-ali-import-btn:disabled {
          opacity: 0.5;
          cursor: not-allowed;
          transform: none;
          box-shadow: none;
        }

        /* Checkbox */
        .lesrats-ali-checkbox {
          display: flex;
          align-items: center;
          gap: 6px;
          margin-bottom: 12px;
          cursor: pointer;
        }
        .lesrats-ali-checkbox input {
          accent-color: #f97316;
          cursor: pointer;
        }
        .lesrats-ali-checkbox span {
          color: #9ca3af;
          font-size: 12px;
        }

        /* Steps */
        .lesrats-ali-steps { margin-top: 4px; }
        .lesrats-ali-step {
          background: rgba(255,255,255,0.05);
          border-radius: 8px;
          margin-bottom: 6px;
          padding: 9px 12px;
          display: flex;
          align-items: flex-start;
          gap: 10px;
          transition: background 0.2s;
        }
        .lesrats-ali-step.active {
          background: rgba(59, 130, 246, 0.08);
        }
        .lesrats-ali-step-icon {
          width: 20px;
          height: 20px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 11px;
          flex-shrink: 0;
          margin-top: 1px;
        }
        .lesrats-ali-step-icon.pending {
          background: #374151;
          color: #9ca3af;
        }
        .lesrats-ali-step-icon.running {
          background: #3b82f6;
          color: white;
          animation: lesrats-ali-pulse 1s infinite;
        }
        .lesrats-ali-step-icon.success {
          background: #22c55e;
          color: white;
        }
        .lesrats-ali-step-icon.error {
          background: #ef4444;
          color: white;
        }
        @keyframes lesrats-ali-pulse {
          0%, 100% { opacity: 1; }
          50% { opacity: 0.5; }
        }
        .lesrats-ali-step-body {
          flex: 1;
          min-width: 0;
        }
        .lesrats-ali-step-text {
          color: #e5e7eb;
          font-size: 12px;
          font-weight: 500;
        }
        .lesrats-ali-step-detail {
          color: #6b7280;
          font-size: 11px;
          margin-top: 2px;
          word-break: break-word;
        }

        /* Result link */
        .lesrats-ali-result {
          margin-top: 8px;
          padding: 14px;
          border-radius: 10px;
          display: none;
        }
        .lesrats-ali-result.visible { display: block; }
        .lesrats-ali-result.ok {
          background: rgba(34, 197, 94, 0.08);
          border: 1px solid rgba(34, 197, 94, 0.25);
        }
        .lesrats-ali-result-link {
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          padding: 10px;
          background: linear-gradient(135deg, #22c55e, #16a34a);
          color: #fff;
          text-decoration: none;
          border-radius: 8px;
          font-weight: 700;
          font-size: 14px;
          transition: opacity 0.15s;
        }
        .lesrats-ali-result-link:hover { opacity: 0.88; }
        .lesrats-ali-etsy-link {
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          padding: 10px;
          margin-top: 8px;
          background: linear-gradient(135deg, #f97316, #ea580c);
          color: #fff;
          text-decoration: none;
          border-radius: 8px;
          font-weight: 700;
          font-size: 14px;
          cursor: pointer;
          border: none;
          transition: opacity 0.15s;
        }
        .lesrats-ali-etsy-link:hover { opacity: 0.88; }
        .lesrats-ali-result-sub {
          color: #6b7280;
          font-size: 11px;
          margin-top: 8px;
          text-align: center;
        }

        /* Error result */
        .lesrats-ali-result.error-result {
          background: rgba(239, 68, 68, 0.08);
          border: 1px solid rgba(239, 68, 68, 0.25);
        }
        .lesrats-ali-result.error-result .lesrats-ali-result-msg {
          color: #fca5a5;
          font-size: 12px;
          text-align: center;
        }

        .lesrats-ali-another {
          width: 100%;
          padding: 8px;
          margin-top: 10px;
          background: rgba(255,255,255,0.08);
          color: #9ca3af;
          border: 1px solid rgba(255,255,255,0.1);
          border-radius: 8px;
          font-size: 12px;
          cursor: pointer;
          transition: background 0.2s;
        }
        .lesrats-ali-another:hover {
          background: rgba(255,255,255,0.14);
          color: #e5e7eb;
        }
      </style>

      <!-- Floating icon (always visible) -->
      <div class="lesrats-ali-fab" id="lesrats-ali-fab" title="LesRats Import (Alt+Shift+I)">
        <img src="${icon48}" width="32" height="32" style="border-radius:50%;">
      </div>

      <!-- Panel (toggled) -->
      <div class="lesrats-ali-content" id="lesrats-ali-content">
        <div class="lesrats-ali-header">
          <div class="lesrats-ali-header-title">
            <img src="${icon32}" width="22" height="22" style="border-radius:50%;">
            <span>LesRats Import</span>
          </div>
          <div class="lesrats-ali-header-actions">
            <button class="lesrats-ali-hbtn" id="lesrats-ali-minimize" title="Minimiser">\u2212</button>
            <button class="lesrats-ali-hbtn" id="lesrats-ali-close" title="Fermer">\u00D7</button>
          </div>
        </div>
        <div class="lesrats-ali-body">
          <!-- Result (shown after import — at top for visibility) -->
          <div class="lesrats-ali-result ok" id="lesrats-ali-result">
            <a class="lesrats-ali-result-link" id="lesrats-ali-result-link" href="#" target="_blank">\u2192 Voir le produit dans LesRats</a>
            <a class="lesrats-ali-etsy-link" id="lesrats-ali-etsy-link" href="#">\u2192 Publier sur Etsy</a>
            <div class="lesrats-ali-result-sub" id="lesrats-ali-result-sub"></div>
            <button class="lesrats-ali-another" id="lesrats-ali-another">Importer un autre produit</button>
          </div>

          <!-- Error result -->
          <div class="lesrats-ali-result error-result" id="lesrats-ali-error-result">
            <div class="lesrats-ali-result-msg" id="lesrats-ali-error-msg"></div>
          </div>

          <!-- Form (hidden after success) -->
          <div id="lesrats-ali-form">
            <!-- Shop selector -->
            <div class="lesrats-ali-label">
              Boutique
            </div>
            <select class="lesrats-ali-select" id="lesrats-ali-shop-select">
              <option value="">Chargement...</option>
            </select>

            <!-- Country prices checkbox -->
            <label class="lesrats-ali-checkbox">
              <input type="checkbox" id="lesrats-ali-country-prices">
              <span>Scraper prix par pays (+15s)</span>
            </label>

            <!-- Import button -->
            <button class="lesrats-ali-import-btn" id="lesrats-ali-import-btn">
              \u{1F680} Importer vers LesRats
            </button>
          </div>

          <!-- Steps container -->
          <div class="lesrats-ali-steps" id="lesrats-ali-steps"></div>
        </div>
      </div>
    `;

    document.body.appendChild(container);
    bindEvents();
    loadShops();
  }

  // ============== EVENTS ==============
  function bindEvents() {
    document.getElementById('lesrats-ali-fab').addEventListener('click', togglePanel);
    document.getElementById('lesrats-ali-minimize').addEventListener('click', closePanel);
    document.getElementById('lesrats-ali-close').addEventListener('click', closePanel);
    document.getElementById('lesrats-ali-import-btn').addEventListener('click', startImport);
    document.getElementById('lesrats-ali-shop-select').addEventListener('change', onShopChange);
    document.getElementById('lesrats-ali-another').addEventListener('click', resetPanel);
    document.getElementById('lesrats-ali-etsy-link').addEventListener('click', publishToEtsy);
  }

  function togglePanel() {
    panelState.isOpen = !panelState.isOpen;
    const content = document.getElementById('lesrats-ali-content');
    const fab = document.getElementById('lesrats-ali-fab');
    if (panelState.isOpen) {
      content.classList.add('open');
      fab.classList.add('hidden');
    } else {
      content.classList.remove('open');
      fab.classList.remove('hidden');
    }
  }

  function openPanel() {
    if (!panelState.isOpen) {
      panelState.isOpen = true;
      document.getElementById('lesrats-ali-content').classList.add('open');
      document.getElementById('lesrats-ali-fab').classList.add('hidden');
    }
  }

  function closePanel() {
    panelState.isOpen = false;
    document.getElementById('lesrats-ali-content').classList.remove('open');
    document.getElementById('lesrats-ali-fab').classList.remove('hidden');
  }

  // ============== SHOPS ==============
  async function loadShops() {
    const { apiUrl, apiToken } = await getApiConfig();
    const select = document.getElementById('lesrats-ali-shop-select');

    if (!apiUrl || !apiToken) {
      select.innerHTML = '<option value="">Config manquante (Settings)</option>';
      return;
    }

    try {
      const response = await chrome.runtime.sendMessage({
        action: 'fetchShops',
        apiUrl,
        apiToken,
      });

      if (response.success && response.shops) {
        panelState.shops = response.shops;
        const saved = await chrome.storage.local.get(['selectedShopId']);
        panelState.selectedShopId = saved.selectedShopId || null;

        select.innerHTML = response.shops.map(shop =>
          `<option value="${shop.id}" ${panelState.selectedShopId == shop.id ? 'selected' : ''}>${shop.name}${shop.platform ? ` (${shop.platform})` : ''}</option>`
        ).join('');

        if (!panelState.selectedShopId && response.shops.length > 0) {
          panelState.selectedShopId = response.shops[0].id.toString();
          chrome.storage.local.set({ selectedShopId: panelState.selectedShopId });
        }

        // Auto-suggest shop via AI if multiple shops
        if (response.shops.length >= 2) {
          suggestShop();
        }
      } else {
        select.innerHTML = `<option value="">${response.error || 'Erreur'}</option>`;
      }
    } catch (e) {
      console.error('\u{1F400} Panel: loadShops error', e);
      select.innerHTML = '<option value="">Serveur inaccessible</option>';
    }
  }

  async function onShopChange() {
    panelState.selectedShopId = document.getElementById('lesrats-ali-shop-select').value;
    chrome.storage.local.set({ selectedShopId: panelState.selectedShopId });
  }

  // ============== AI SHOP SUGGESTION ==============
  async function suggestShop() {
    if (panelState.suggestingShop || panelState.shops.length < 2) return;
    panelState.suggestingShop = true;

    try {
      if (!panelState.productData) {
        panelState.productData = await extractProductForPanel();
      }

      const { apiUrl, apiToken } = await getApiConfig();

      const data = await chrome.runtime.sendMessage({
        action: 'suggestShop',
        apiUrl,
        apiToken,
        productData: {
          title: panelState.productData.title || '',
          description: panelState.productData.description || '',
          price: panelState.productData.price || null,
          source_type: 'aliexpress',
        },
      });

      if (data.success && data.shop_id) {
        const select = document.getElementById('lesrats-ali-shop-select');
        select.value = data.shop_id.toString();
        panelState.selectedShopId = data.shop_id.toString();
        chrome.storage.local.set({ selectedShopId: panelState.selectedShopId });
      }
    } catch (e) {
      console.error('\u{1F400} Panel: suggestShop error', e);
    } finally {
      panelState.suggestingShop = false;
    }
  }

  // ============== PRODUCT EXTRACTION ==============
  async function extractProductForPanel() {
    if (typeof extractProductData === 'function') {
      return await extractProductData(false);
    }
    throw new Error('extractProductData non disponible');
  }

  // ============== STEPS ==============
  function clearSteps() {
    document.getElementById('lesrats-ali-steps').innerHTML = '';
    document.getElementById('lesrats-ali-result').classList.remove('visible');
    document.getElementById('lesrats-ali-error-result').classList.remove('visible');
    document.getElementById('lesrats-ali-form').style.display = '';
  }

  function resetPanel() {
    clearSteps();
    panelState.importResult = null;
    panelState.productData = null;
    const btn = document.getElementById('lesrats-ali-import-btn');
    btn.disabled = false;
    btn.textContent = '\u{1F680} Importer vers LesRats';
  }

  function addStep(id, text) {
    const container = document.getElementById('lesrats-ali-steps');
    const el = document.createElement('div');
    el.className = 'lesrats-ali-step';
    el.id = `lesrats-ali-step-${id}`;
    el.innerHTML = `
      <div class="lesrats-ali-step-icon pending">\u25CB</div>
      <div class="lesrats-ali-step-body">
        <div class="lesrats-ali-step-text">${text}</div>
        <div class="lesrats-ali-step-detail"></div>
      </div>
    `;
    container.appendChild(el);
    // Scroll to bottom
    const body = el.closest('.lesrats-ali-body');
    if (body) body.scrollTop = body.scrollHeight;
  }

  function updateStep(id, status, detail) {
    const el = document.getElementById(`lesrats-ali-step-${id}`);
    if (!el) return;

    const iconEl = el.querySelector('.lesrats-ali-step-icon');
    const detailEl = el.querySelector('.lesrats-ali-step-detail');

    iconEl.className = 'lesrats-ali-step-icon ' + status;
    el.classList.toggle('active', status === 'running');
    switch (status) {
      case 'running': iconEl.textContent = '\u25C9'; break;
      case 'success': iconEl.textContent = '\u2713'; break;
      case 'error':   iconEl.textContent = '\u2717'; break;
      default:        iconEl.textContent = '\u25CB';
    }
    if (detail !== undefined && detail !== null) detailEl.textContent = detail;

    const body = el.closest('.lesrats-ali-body');
    if (body) body.scrollTop = body.scrollHeight;
  }

  // ============== IMPORT ==============
  async function startImport() {
    if (panelState.isImporting) return;

    const shopId = document.getElementById('lesrats-ali-shop-select').value;
    if (!shopId) { showError('Selectionnez une boutique'); return; }

    const { apiUrl, apiToken } = await getApiConfig();
    if (!apiUrl || !apiToken) { showError('Configurez l\'extension (Settings)'); return; }

    panelState.isImporting = true;
    panelState.importResult = null;
    const btn = document.getElementById('lesrats-ali-import-btn');
    btn.disabled = true;
    btn.textContent = '\u23F3 Import en cours...';
    clearSteps();

    const includeCountryPrices = document.getElementById('lesrats-ali-country-prices').checked;

    try {
      // === Step 1: Page ready ===
      addStep('page', 'Verification de la page');
      updateStep('page', 'success', 'Page prete');

      // === Step 2: Extract title & price ===
      addStep('title', 'Extraction du titre et prix');
      updateStep('title', 'running');
      let productData;
      try {
        productData = await extractProductForPanel();
        panelState.productData = productData;
      } catch (e) {
        updateStep('title', 'error', e.message);
        throw e;
      }
      const title = productData.title || '?';
      const price = productData.price ? `${productData.price}\u00A0\u20AC` : '?';
      updateStep('title', 'success', `${title.substring(0, 45)}${title.length > 45 ? '...' : ''} \u2014 ${price}`);

      // === Step 3: Images ===
      addStep('img', 'Extraction des images');
      updateStep('img', 'running');
      const imgs = productData.images || [];
      if (imgs.length === 0) {
        updateStep('img', 'error', 'Aucune image trouvee');
      } else {
        updateStep('img', 'success', `${imgs.length} image${imgs.length > 1 ? 's' : ''}`);
      }

      // === Step 4: Sizes / variants ===
      addStep('sizes', 'Extraction des tailles / variantes');
      updateStep('sizes', 'running');
      const variants = productData.variants || [];
      const sizeValues = variants.flatMap(v => v.values || []);
      if (sizeValues.length === 0) {
        updateStep('sizes', 'success', 'Aucune variante');
      } else {
        const preview = sizeValues.slice(0, 5).join(', ') + (sizeValues.length > 5 ? ` +${sizeValues.length - 5}` : '');
        updateStep('sizes', 'success', `${sizeValues.length}: ${preview}`);
      }

      // === Step 5: Country prices (optional) ===
      if (includeCountryPrices) {
        addStep('prices', 'Scraping des prix par pays');
        updateStep('prices', 'running', 'Demarrage...');
        try {
          if (typeof scrapeCountryPrices === 'function') {
            const countryPrices = await scrapeCountryPrices((progress) => {
              updateStep('prices', 'running', `${progress.country} (${progress.current}/${progress.total})`);
            });
            if (countryPrices && Object.keys(countryPrices).length > 0) {
              productData.country_prices = countryPrices;
              const countries = Object.keys(countryPrices).join(', ');
              updateStep('prices', 'success', countries);
            } else {
              updateStep('prices', 'error', 'Aucun prix');
            }
          } else {
            updateStep('prices', 'error', 'Fonction non disponible');
          }
        } catch (e) {
          updateStep('prices', 'error', e.message);
        }
      }

      // === Step 6: Send to API (via service worker) ===
      addStep('send', 'Envoi vers le serveur LesRats');
      updateStep('send', 'running', 'Connexion...');

      const payload = { ...productData, shop_id: parseInt(shopId) };
      delete payload._debug;

      const data = await chrome.runtime.sendMessage({
        action: 'importProduct',
        data: payload,
        apiUrl,
        apiToken,
      });

      if (!data || !data.success) {
        const errMsg = data?.error || data?.message || 'Erreur inconnue';
        updateStep('send', 'error', errMsg);
        showError(errMsg);
        return;
      }

      if (data.is_existing) {
        updateStep('send', 'success', 'Produit deja dans la base');
      } else {
        updateStep('send', 'success', `Produit #${data.product_id} cree`);
      }

      // === Step 7: AI optimization ===
      addStep('ai', 'Optimisation IA (titre, description, tags, categorie)');
      updateStep('ai', 'success', 'Traite cote serveur');

      // === Show result — hide form, show big link at top ===
      panelState.importResult = data;
      document.getElementById('lesrats-ali-form').style.display = 'none';
      const resultEl = document.getElementById('lesrats-ali-result');
      const linkEl = document.getElementById('lesrats-ali-result-link');
      const subEl = document.getElementById('lesrats-ali-result-sub');
      const baseUrl = apiUrl.replace(/\/+$/, '');
      linkEl.href = data.product_url || `${baseUrl}/products/${data.product_id}/edit`;
      linkEl.textContent = '\u2192 Voir le produit dans LesRats';
      subEl.textContent = data.is_existing ? 'Ce produit existait deja' : `Produit #${data.product_id} importe avec succes`;
      resultEl.classList.add('visible');
      // Scroll to top so result is visible
      const body = document.querySelector('.lesrats-ali-body');
      if (body) body.scrollTop = 0;

    } catch (e) {
      console.error('\u{1F400} Panel: import error', e);
      showError(e.message || 'Erreur inattendue');
    } finally {
      panelState.isImporting = false;
      btn.disabled = false;
      btn.textContent = '\u{1F680} Importer vers LesRats';
    }
  }

  // ============== PUBLISH TO ETSY ==============
  async function publishToEtsy(e) {
    e.preventDefault();
    if (!panelState.importResult || !panelState.importResult.product_id) return;

    const btn = document.getElementById('lesrats-ali-etsy-link');
    btn.textContent = '\u23F3 Preparation...';
    btn.style.pointerEvents = 'none';

    try {
      const { apiUrl, apiToken } = await getApiConfig();
      const productId = panelState.importResult.product_id;

      // Fetch Etsy data (category, isDigital, etc.)
      const etsyData = await chrome.runtime.sendMessage({
        action: 'fetchEtsyData',
        apiUrl,
        productId,
        apiToken,
        needsToken: true,
      });

      if (etsyData.success && etsyData.data) {
        const categoryName = etsyData.data.etsy_category || '';
        const isDigital = etsyData.data.is_digital || false;

        // Store for Etsy content script to pick up
        await chrome.storage.local.set({
          pendingEtsyCategoryName: categoryName,
          pendingEtsyIsDigital: isDigital,
          pendingEtsyProductId: productId,
          pendingEtsyApiUrl: apiUrl,
        });

        // Open Etsy listing editor
        window.open('https://www.etsy.com/your/shops/me/listing-editor/create', '_blank');
        btn.textContent = '\u2713 Etsy ouvert';
      } else {
        btn.textContent = '\u2717 Erreur';
        console.error('\u{1F400} Panel: fetchEtsyData failed', etsyData);
      }
    } catch (e) {
      console.error('\u{1F400} Panel: publishToEtsy error', e);
      btn.textContent = '\u2717 Erreur';
    } finally {
      setTimeout(() => {
        btn.textContent = '\u2192 Publier sur Etsy';
        btn.style.pointerEvents = '';
      }, 2000);
    }
  }

  function showError(msg) {
    const el = document.getElementById('lesrats-ali-error-result');
    document.getElementById('lesrats-ali-error-msg').textContent = msg;
    el.classList.add('visible');
  }

  // ============== KEYBOARD SHORTCUT ==============
  chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'openLesratsPanel') {
      openPanel();
      sendResponse({ success: true });
    }
  });

  // ============== HINT-ONLY MODE (pages AliExpress hors fiche produit) ==============
  function createHintPanel() {
    const icon48 = chrome.runtime.getURL('icons/icon48.png');
    const container = document.createElement('div');
    container.id = 'lesrats-ali-panel';
    container.innerHTML = `
      <style>
        #lesrats-ali-panel {
          position: fixed;
          bottom: 20px;
          right: 20px;
          z-index: 999999;
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        #lesrats-ali-panel * { box-sizing: border-box; }
        .lesrats-hint-wrap {
          position: relative;
          display: flex;
          align-items: flex-end;
          gap: 10px;
        }
        .lesrats-hint-bubble {
          background: #1a1a2e;
          color: #fff;
          padding: 12px 16px;
          border-radius: 14px;
          font-size: 13px;
          font-weight: 600;
          box-shadow: 0 8px 32px rgba(0,0,0,0.35);
          max-width: 240px;
          line-height: 1.4;
          position: relative;
          animation: lesrats-hint-pop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .lesrats-hint-bubble strong {
          color: #fb923c;
        }
        .lesrats-hint-bubble::after {
          content: '';
          position: absolute;
          right: -8px;
          bottom: 18px;
          width: 0;
          height: 0;
          border-top: 8px solid transparent;
          border-bottom: 8px solid transparent;
          border-left: 8px solid #1a1a2e;
        }
        .lesrats-hint-bubble.hidden { display: none; }
        .lesrats-hint-close {
          position: absolute;
          top: 4px;
          right: 6px;
          background: transparent;
          border: none;
          color: #6b7280;
          font-size: 16px;
          line-height: 1;
          cursor: pointer;
          padding: 2px 4px;
        }
        .lesrats-hint-close:hover { color: #fff; }
        .lesrats-ali-fab {
          width: 54px;
          height: 54px;
          background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          cursor: pointer;
          box-shadow: 0 4px 16px rgba(249, 115, 22, 0.5);
          transition: transform 0.2s, box-shadow 0.2s;
          user-select: none;
          animation: lesrats-hint-bounce 2s ease-in-out infinite;
        }
        .lesrats-ali-fab:hover {
          transform: scale(1.1);
          box-shadow: 0 6px 24px rgba(249, 115, 22, 0.6);
          animation-play-state: paused;
        }
        @keyframes lesrats-hint-pop {
          0% { opacity: 0; transform: translateY(8px) scale(0.95); }
          100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes lesrats-hint-bounce {
          0%, 100% { transform: translateY(0); }
          50% { transform: translateY(-4px); }
        }
      </style>
      <div class="lesrats-hint-wrap">
        <div class="lesrats-hint-bubble" id="lesrats-hint-bubble">
          <button class="lesrats-hint-close" id="lesrats-hint-close" title="Fermer">×</button>
          <strong>\u{1F400} Cherche un article</strong><br>
          puis clique sur moi pour l'importer dans LesRats.
        </div>
        <div class="lesrats-ali-fab" id="lesrats-ali-fab" title="LesRats : ouvrez une fiche produit pour importer">
          <img src="${icon48}" width="32" height="32" style="border-radius:50%;">
        </div>
      </div>
    `;
    document.body.appendChild(container);

    const bubble = document.getElementById('lesrats-hint-bubble');
    const dismissedKey = 'lesratsHintDismissed';
    chrome.storage.local.get([dismissedKey]).then((saved) => {
      if (saved[dismissedKey]) bubble.classList.add('hidden');
    });

    document.getElementById('lesrats-hint-close').addEventListener('click', (e) => {
      e.stopPropagation();
      bubble.classList.add('hidden');
      chrome.storage.local.set({ [dismissedKey]: true });
    });

    document.getElementById('lesrats-ali-fab').addEventListener('click', () => {
      bubble.classList.remove('hidden');
      chrome.storage.local.remove([dismissedKey]);
    });
  }

  // ============== INIT ==============
  const init = isProductPage ? createPanel : createHintPanel;
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  console.log('\u{1F400} LesRats AliExpress Panel loaded (' + (isProductPage ? 'product' : 'hint') + ' mode)');
})();
