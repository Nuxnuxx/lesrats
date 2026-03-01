// Floating import panel for Printables model pages
// Dark theme, orange header — same style as AliExpress panel
// ALL network calls go through service worker

(function() {
  'use strict';

  if (!window.location.href.includes('printables.com/model/')) return;
  if (document.getElementById('lesrats-pri-panel')) return;

  // ============== STATE ==============
  let panelState = {
    isOpen: false,
    shops: [],
    selectedShopId: null,
    productData: null,
    isImporting: false,
    importResult: null,
  };

  // ============== API CONFIG ==============
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
    container.id = 'lesrats-pri-panel';
    container.innerHTML = `
      <style>
        #lesrats-pri-panel {
          position: fixed;
          bottom: 20px;
          right: 20px;
          z-index: 999999;
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
          font-size: 13px;
        }
        #lesrats-pri-panel * {
          box-sizing: border-box;
        }

        /* Floating icon */
        .lesrats-pri-fab {
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
        .lesrats-pri-fab:hover {
          transform: scale(1.1);
          box-shadow: 0 6px 24px rgba(249, 115, 22, 0.6);
        }
        .lesrats-pri-fab.hidden { display: none; }

        /* Panel content */
        .lesrats-pri-content {
          background: #1a1a2e;
          border-radius: 12px;
          box-shadow: 0 8px 32px rgba(0,0,0,0.5);
          width: 400px;
          max-height: 560px;
          overflow: hidden;
          display: none;
          flex-direction: column;
        }
        .lesrats-pri-content.open {
          display: flex;
        }

        /* Header */
        .lesrats-pri-header {
          background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
          color: white;
          padding: 12px 16px;
          display: flex;
          align-items: center;
          justify-content: space-between;
          flex-shrink: 0;
        }
        .lesrats-pri-header-title {
          display: flex;
          align-items: center;
          gap: 8px;
          font-weight: 600;
          font-size: 14px;
        }
        .lesrats-pri-header-actions {
          display: flex;
          gap: 6px;
        }
        .lesrats-pri-hbtn {
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
        .lesrats-pri-hbtn:hover { background: rgba(255,255,255,0.3); }

        /* Body */
        .lesrats-pri-body {
          padding: 14px;
          overflow-y: auto;
          max-height: 470px;
          flex: 1;
        }

        /* Shop selector */
        .lesrats-pri-label {
          color: #9ca3af;
          font-size: 11px;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          margin-bottom: 6px;
          display: flex;
          align-items: center;
          gap: 6px;
        }
        .lesrats-pri-select {
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
        .lesrats-pri-select:focus {
          border-color: #f97316;
        }
        .lesrats-pri-select option {
          background: #16213e;
          color: #e5e7eb;
        }

        /* Import button */
        .lesrats-pri-import-btn {
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
        .lesrats-pri-import-btn:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(249,115,22,0.4); }
        .lesrats-pri-import-btn:active { transform: translateY(0); box-shadow: none; }
        .lesrats-pri-import-btn:disabled {
          opacity: 0.5;
          cursor: not-allowed;
          transform: none;
          box-shadow: none;
        }

        /* Steps */
        .lesrats-pri-steps { margin-top: 4px; }
        .lesrats-pri-step {
          background: rgba(255,255,255,0.05);
          border-radius: 8px;
          margin-bottom: 6px;
          padding: 9px 12px;
          display: flex;
          align-items: flex-start;
          gap: 10px;
          transition: background 0.2s;
        }
        .lesrats-pri-step.active {
          background: rgba(59, 130, 246, 0.08);
        }
        .lesrats-pri-step-icon {
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
        .lesrats-pri-step-icon.pending {
          background: #374151;
          color: #9ca3af;
        }
        .lesrats-pri-step-icon.running {
          background: #3b82f6;
          color: white;
          animation: lesrats-pri-pulse 1s infinite;
        }
        .lesrats-pri-step-icon.success {
          background: #22c55e;
          color: white;
        }
        .lesrats-pri-step-icon.error {
          background: #ef4444;
          color: white;
        }
        @keyframes lesrats-pri-pulse {
          0%, 100% { opacity: 1; }
          50% { opacity: 0.5; }
        }
        .lesrats-pri-step-body {
          flex: 1;
          min-width: 0;
        }
        .lesrats-pri-step-text {
          color: #e5e7eb;
          font-size: 12px;
          font-weight: 500;
        }
        .lesrats-pri-step-detail {
          color: #6b7280;
          font-size: 11px;
          margin-top: 2px;
          word-break: break-word;
        }

        /* Result link */
        .lesrats-pri-result {
          margin-top: 8px;
          padding: 14px;
          border-radius: 10px;
          display: none;
        }
        .lesrats-pri-result.visible { display: block; }
        .lesrats-pri-result.ok {
          background: rgba(34, 197, 94, 0.08);
          border: 1px solid rgba(34, 197, 94, 0.25);
        }
        .lesrats-pri-result-link {
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
        .lesrats-pri-result-link:hover { opacity: 0.88; }
        .lesrats-pri-etsy-link {
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
        .lesrats-pri-etsy-link:hover { opacity: 0.88; }
        .lesrats-pri-result-sub {
          color: #6b7280;
          font-size: 11px;
          margin-top: 8px;
          text-align: center;
        }

        /* Error result */
        .lesrats-pri-result.error-result {
          background: rgba(239, 68, 68, 0.08);
          border: 1px solid rgba(239, 68, 68, 0.25);
        }
        .lesrats-pri-result.error-result .lesrats-pri-result-msg {
          color: #fca5a5;
          font-size: 12px;
          text-align: center;
        }

        .lesrats-pri-another {
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
        .lesrats-pri-another:hover {
          background: rgba(255,255,255,0.14);
          color: #e5e7eb;
        }
      </style>

      <!-- Floating icon -->
      <div class="lesrats-pri-fab" id="lesrats-pri-fab" title="LesRats Import (Alt+Shift+I)">
        <img src="${icon48}" width="32" height="32" style="border-radius:50%;">
      </div>

      <!-- Panel -->
      <div class="lesrats-pri-content" id="lesrats-pri-content">
        <div class="lesrats-pri-header">
          <div class="lesrats-pri-header-title">
            <img src="${icon32}" width="22" height="22" style="border-radius:50%;">
            <span>LesRats Import</span>
          </div>
          <div class="lesrats-pri-header-actions">
            <button class="lesrats-pri-hbtn" id="lesrats-pri-minimize" title="Minimiser">\u2212</button>
            <button class="lesrats-pri-hbtn" id="lesrats-pri-close" title="Fermer">\u00D7</button>
          </div>
        </div>
        <div class="lesrats-pri-body">
          <!-- Result (shown after import — at top) -->
          <div class="lesrats-pri-result ok" id="lesrats-pri-result">
            <a class="lesrats-pri-result-link" id="lesrats-pri-result-link" href="#" target="_blank">\u2192 Voir le produit dans LesRats</a>
            <a class="lesrats-pri-etsy-link" id="lesrats-pri-etsy-link" href="#">\u2192 Publier sur Etsy</a>
            <div class="lesrats-pri-result-sub" id="lesrats-pri-result-sub"></div>
            <button class="lesrats-pri-another" id="lesrats-pri-another">Importer un autre produit</button>
          </div>

          <!-- Error result -->
          <div class="lesrats-pri-result error-result" id="lesrats-pri-error-result">
            <div class="lesrats-pri-result-msg" id="lesrats-pri-error-msg"></div>
          </div>

          <!-- Form -->
          <div id="lesrats-pri-form">
            <div class="lesrats-pri-label">Boutique</div>
            <select class="lesrats-pri-select" id="lesrats-pri-shop-select">
              <option value="">Chargement...</option>
            </select>

            <button class="lesrats-pri-import-btn" id="lesrats-pri-import-btn">
              \u{1F680} Importer vers LesRats
            </button>
          </div>

          <!-- Steps -->
          <div class="lesrats-pri-steps" id="lesrats-pri-steps"></div>
        </div>
      </div>
    `;

    document.body.appendChild(container);
    bindEvents();
    loadShops();
  }

  // ============== EVENTS ==============
  function bindEvents() {
    document.getElementById('lesrats-pri-fab').addEventListener('click', togglePanel);
    document.getElementById('lesrats-pri-minimize').addEventListener('click', closePanel);
    document.getElementById('lesrats-pri-close').addEventListener('click', closePanel);
    document.getElementById('lesrats-pri-import-btn').addEventListener('click', startImport);
    document.getElementById('lesrats-pri-shop-select').addEventListener('change', onShopChange);
    document.getElementById('lesrats-pri-another').addEventListener('click', resetPanel);
    document.getElementById('lesrats-pri-etsy-link').addEventListener('click', publishToEtsy);
  }

  function togglePanel() {
    panelState.isOpen = !panelState.isOpen;
    const content = document.getElementById('lesrats-pri-content');
    const fab = document.getElementById('lesrats-pri-fab');
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
      document.getElementById('lesrats-pri-content').classList.add('open');
      document.getElementById('lesrats-pri-fab').classList.add('hidden');
    }
  }

  function closePanel() {
    panelState.isOpen = false;
    document.getElementById('lesrats-pri-content').classList.remove('open');
    document.getElementById('lesrats-pri-fab').classList.remove('hidden');
  }

  // ============== SHOPS ==============
  async function loadShops() {
    const { apiUrl, apiToken } = await getApiConfig();
    const select = document.getElementById('lesrats-pri-shop-select');

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
    panelState.selectedShopId = document.getElementById('lesrats-pri-shop-select').value;
    chrome.storage.local.set({ selectedShopId: panelState.selectedShopId });
  }

  // ============== AI SHOP SUGGESTION ==============
  async function suggestShop() {
    if (panelState.suggestingShop || panelState.shops.length < 2) return;
    panelState.suggestingShop = true;

    try {
      if (!panelState.productData) {
        panelState.productData = await extractProductData();
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
          source_type: 'printables',
        },
      });

      if (data.success && data.shop_id) {
        const select = document.getElementById('lesrats-pri-shop-select');
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

  // ============== STEPS ==============
  function clearSteps() {
    document.getElementById('lesrats-pri-steps').innerHTML = '';
    document.getElementById('lesrats-pri-result').classList.remove('visible');
    document.getElementById('lesrats-pri-error-result').classList.remove('visible');
    document.getElementById('lesrats-pri-form').style.display = '';
  }

  function resetPanel() {
    clearSteps();
    panelState.importResult = null;
    panelState.productData = null;
    const btn = document.getElementById('lesrats-pri-import-btn');
    btn.disabled = false;
    btn.textContent = '\u{1F680} Importer vers LesRats';
  }

  function addStep(id, text) {
    const container = document.getElementById('lesrats-pri-steps');
    const el = document.createElement('div');
    el.className = 'lesrats-pri-step';
    el.id = `lesrats-pri-step-${id}`;
    el.innerHTML = `
      <div class="lesrats-pri-step-icon pending">\u25CB</div>
      <div class="lesrats-pri-step-body">
        <div class="lesrats-pri-step-text">${text}</div>
        <div class="lesrats-pri-step-detail"></div>
      </div>
    `;
    container.appendChild(el);
    const body = el.closest('.lesrats-pri-body');
    if (body) body.scrollTop = body.scrollHeight;
  }

  function updateStep(id, status, detail) {
    const el = document.getElementById(`lesrats-pri-step-${id}`);
    if (!el) return;

    const iconEl = el.querySelector('.lesrats-pri-step-icon');
    const detailEl = el.querySelector('.lesrats-pri-step-detail');

    iconEl.className = 'lesrats-pri-step-icon ' + status;
    el.classList.toggle('active', status === 'running');
    switch (status) {
      case 'running': iconEl.textContent = '\u25C9'; break;
      case 'success': iconEl.textContent = '\u2713'; break;
      case 'error':   iconEl.textContent = '\u2717'; break;
      default:        iconEl.textContent = '\u25CB';
    }
    if (detail !== undefined && detail !== null) detailEl.textContent = detail;

    const body = el.closest('.lesrats-pri-body');
    if (body) body.scrollTop = body.scrollHeight;
  }

  // ============== IMPORT ==============
  async function startImport() {
    if (panelState.isImporting) return;

    const shopId = document.getElementById('lesrats-pri-shop-select').value;
    if (!shopId) { showError('Selectionnez une boutique'); return; }

    const { apiUrl, apiToken } = await getApiConfig();
    if (!apiUrl || !apiToken) { showError('Configurez l\'extension (Settings)'); return; }

    panelState.isImporting = true;
    panelState.importResult = null;
    const btn = document.getElementById('lesrats-pri-import-btn');
    btn.disabled = true;
    btn.textContent = '\u23F3 Import en cours...';
    clearSteps();

    try {
      // === Step 1: Page ready ===
      addStep('page', 'Verification de la page');
      updateStep('page', 'success', 'Page Printables prete');

      // === Step 2: Extract title & price ===
      addStep('title', 'Extraction du titre et prix');
      updateStep('title', 'running');
      let productData;
      try {
        // extractProductData is from printables.js (same content script entry)
        productData = await extractProductData();
        panelState.productData = productData;
      } catch (e) {
        updateStep('title', 'error', e.message);
        throw e;
      }
      const title = productData.title || '?';
      const price = productData.price ? `${productData.price}\u00A0\u20AC` : 'Gratuit';
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

      // === Step 4: Tags ===
      addStep('tags', 'Extraction des tags');
      updateStep('tags', 'running');
      const tags = productData.tags || [];
      if (tags.length === 0) {
        updateStep('tags', 'success', 'Aucun tag');
      } else {
        const preview = tags.slice(0, 4).join(', ') + (tags.length > 4 ? ` +${tags.length - 4}` : '');
        updateStep('tags', 'success', `${tags.length}: ${preview}`);
      }

      // === Step 5: Send to API (via service worker) ===
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

      // === Step 6: AI optimization ===
      addStep('ai', 'Optimisation IA (titre, description, tags, categorie)');
      updateStep('ai', 'success', 'Traite cote serveur');

      // === Show result ===
      panelState.importResult = data;
      document.getElementById('lesrats-pri-form').style.display = 'none';
      const resultEl = document.getElementById('lesrats-pri-result');
      const linkEl = document.getElementById('lesrats-pri-result-link');
      const subEl = document.getElementById('lesrats-pri-result-sub');
      const baseUrl = apiUrl.replace(/\/+$/, '');
      linkEl.href = data.product_url || `${baseUrl}/products/${data.product_id}/edit`;
      linkEl.textContent = '\u2192 Voir le produit dans LesRats';
      subEl.textContent = data.is_existing ? 'Ce produit existait deja' : `Produit #${data.product_id} importe avec succes`;
      resultEl.classList.add('visible');
      // Scroll to top
      const body = document.querySelector('.lesrats-pri-body');
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

    const btn = document.getElementById('lesrats-pri-etsy-link');
    btn.textContent = '\u23F3 Preparation...';
    btn.style.pointerEvents = 'none';

    try {
      const { apiUrl, apiToken } = await getApiConfig();
      const productId = panelState.importResult.product_id;

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

        await chrome.storage.local.set({
          pendingEtsyCategoryName: categoryName,
          pendingEtsyIsDigital: isDigital,
          pendingEtsyProductId: productId,
          pendingEtsyApiUrl: apiUrl,
        });

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
    const el = document.getElementById('lesrats-pri-error-result');
    document.getElementById('lesrats-pri-error-msg').textContent = msg;
    el.classList.add('visible');
  }

  // ============== KEYBOARD SHORTCUT ==============
  chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'openLesratsPanel') {
      openPanel();
      sendResponse({ success: true });
    }
  });

  // ============== INIT ==============
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', createPanel);
  } else {
    createPanel();
  }

  console.log('\u{1F400} LesRats Printables Panel loaded');
})();
