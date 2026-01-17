<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Nouveau Produit - {{ $shop->name }}
            </h2>
            <a href="{{ route('products.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                Retour
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <!-- Step 1: Choose Product Type -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">🎯 Étape 1 : Type de produit</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- AliExpress Option -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="product_type" value="aliexpress" class="peer sr-only" checked>
                            <div class="p-4 border-2 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:bg-gray-50 transition-all">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-2xl">📦</span>
                                    <span class="font-semibold text-gray-800">Dropshipping AliExpress</span>
                                </div>
                                <p class="text-sm text-gray-600">Importer un produit depuis AliExpress pour le revendre sur Etsy.</p>
                            </div>
                        </label>

                        <!-- Printables Option -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="product_type" value="printables" class="peer sr-only">
                            <div class="p-4 border-2 rounded-lg peer-checked:border-green-500 peer-checked:bg-green-50 hover:bg-gray-50 transition-all">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-2xl">📁</span>
                                    <span class="font-semibold text-gray-800">Fichier STL (Printables)</span>
                                </div>
                                <p class="text-sm text-gray-600">Importer un modèle depuis Printables pour vendre des fichiers STL en téléchargement.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- AliExpress Import Section -->
            <div id="aliexpress-section" class="bg-gradient-to-r from-blue-500 to-blue-600 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-white mb-2">📦 Importer depuis AliExpress</h3>
                    <p class="text-blue-100 text-sm mb-4">Collez le lien du produit AliExpress pour remplir automatiquement le formulaire avec un contenu optimisé pour Etsy.</p>

                    <div class="flex gap-2 mb-3">
                        <input type="url" id="aliexpress_url" name="aliexpress_url" value="{{ old('aliexpress_url') }}"
                            placeholder="https://fr.aliexpress.com/item/123456789.html"
                            class="block w-full rounded-md border-0 shadow-sm focus:ring-2 focus:ring-white text-gray-900 placeholder-gray-400">
                        <button type="button" id="analyze-aliexpress-btn" class="inline-flex items-center px-6 py-2 bg-white border border-transparent rounded-md font-semibold text-sm text-blue-600 uppercase tracking-widest hover:bg-blue-50 disabled:opacity-50 whitespace-nowrap">
                            🔍 Analyser
                        </button>
                    </div>

                    <div class="flex gap-2 items-center">
                        <label class="text-white text-sm whitespace-nowrap">💰 Prix AliExpress (€) :</label>
                        <input type="number" id="aliexpress_price" step="0.01" min="0"
                            placeholder="Ex: 12.99"
                            class="block w-32 rounded-md border-0 shadow-sm text-gray-900 placeholder-gray-400">
                        <span class="text-blue-100 text-xs">→ Prix Etsy = x3</span>
                    </div>

                    <div id="aliexpress-status" class="mt-3 hidden"></div>
                </div>
            </div>

            <!-- Printables Import Section -->
            <div id="printables-section" class="bg-gradient-to-r from-green-500 to-green-600 overflow-hidden shadow-sm sm:rounded-lg mb-6 hidden">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-white mb-2">📁 Importer depuis Printables</h3>
                    <p class="text-green-100 text-sm mb-4">Collez le lien du modèle 3D Printables pour générer un listing de fichier STL en téléchargement digital.</p>

                    <div class="flex gap-2 mb-3">
                        <input type="url" id="printables_url" name="printables_url" value="{{ old('printables_url') }}"
                            placeholder="https://www.printables.com/model/123456-nom-du-modele"
                            class="block w-full rounded-md border-0 shadow-sm focus:ring-2 focus:ring-white text-gray-900 placeholder-gray-400">
                        <button type="button" id="analyze-printables-btn" class="inline-flex items-center px-6 py-2 bg-white border border-transparent rounded-md font-semibold text-sm text-green-600 uppercase tracking-widest hover:bg-green-50 disabled:opacity-50 whitespace-nowrap">
                            🔍 Analyser
                        </button>
                    </div>

                    <div class="flex gap-2 items-center">
                        <label class="text-white text-sm whitespace-nowrap">💰 Prix STL (€) :</label>
                        <input type="number" id="printing_cost" step="0.01" min="0"
                            placeholder="Ex: 3.99"
                            class="block w-24 rounded-md border-0 shadow-sm text-gray-900 placeholder-gray-400">
                        <span class="text-green-100 text-xs">Prix de vente du fichier digital</span>
                    </div>

                    <div id="printables-status" class="mt-3 hidden"></div>

                    <!-- License Warning -->
                    <div id="license-warning" class="mt-3 hidden p-3 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded-md">
                        <strong>⚠️ Attention Licence :</strong>
                        <span id="license-text"></span>
                    </div>
                </div>
            </div>

            <!-- Image Preview Section -->
            <div id="images-preview-section" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 hidden">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">🖼️ Images du produit</h3>
                    <p class="text-sm text-gray-600 mb-4">Images récupérées depuis la source. Cliquez sur une image pour la sélectionner pour votre listing Etsy.</p>

                    <div id="images-grid" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <!-- Images will be inserted here by JavaScript -->
                    </div>

                    <div id="selected-images-info" class="mt-4 text-sm text-gray-600 hidden">
                        <span class="font-semibold">Images sélectionnées:</span> <span id="selected-count">0</span>/10
                    </div>
                </div>
            </div>

            <!-- Product Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">📝 Étape 2 : Détails du produit</h3>

                    <form method="POST" action="{{ route('products.store') }}" id="product-form">
                        @csrf
                        <input type="hidden" name="aliexpress_url" id="aliexpress_url_hidden">
                        <input type="hidden" name="printables_url" id="printables_url_hidden">
                        <input type="hidden" name="product_source" id="product_source" value="aliexpress">
                        <input type="hidden" name="license" id="license_hidden">
                        <input type="hidden" name="attribution" id="attribution_hidden">
                        <input type="hidden" name="images" id="images_hidden">

                        <div class="mb-4">
                            <label for="title" class="block text-sm font-medium text-gray-700">Titre du produit (optimisé pour Etsy)</label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Le titre sera généré automatiquement...">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description (optimisée SEO)</label>
                            <textarea name="description" id="description" rows="8"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="La description sera générée automatiquement...">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="tags" class="block text-sm font-medium text-gray-700">Tags Etsy (13 tags SEO)</label>
                            <input type="text" name="tags" id="tags" value="{{ old('tags') }}"
                                placeholder="Les tags seront générés automatiquement..."
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-1 text-xs text-gray-500">13 tags maximum, 20 caractères chacun, séparés par des virgules.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700">Prix Etsy ({{ $shop->currency }})</label>
                                <input type="number" name="price" id="price" step="0.01" min="0" value="{{ old('price') }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="0.00">
                                <p class="mt-1 text-xs text-gray-500" id="price-info"></p>
                                @error('price')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="cost-price-container">
                                <label for="cost_price" class="block text-sm font-medium text-gray-700">
                                    <span id="cost-label">Cout fournisseur</span> ({{ $shop->currency }})
                                </label>
                                <input type="number" name="cost_price" id="cost_price" step="0.01" min="0" value="{{ old('cost_price', 0) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="0.00">
                                <p class="mt-1 text-xs text-gray-500" id="cost-info">Prix d'achat chez AliExpress</p>
                                @error('cost_price')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Hidden quantity field - always 999 for dropship/digital -->
                        <input type="hidden" name="quantity" id="quantity" value="999">

                        <div class="mb-4">
                            <label for="sku" class="block text-sm font-medium text-gray-700">SKU (optionnel)</label>
                            <input type="text" name="sku" id="sku" value="{{ old('sku') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('sku')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Manual AI Optimization (fallback) -->
                        <div class="mb-4 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                            <h4 class="text-sm font-semibold text-purple-800 mb-2">🤖 Re-optimiser avec l'IA</h4>
                            <p class="text-xs text-purple-600 mb-3">Si vous modifiez le titre manuellement, cliquez ici pour régénérer la description et les tags.</p>
                            <button type="button" id="optimize-btn" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 disabled:opacity-50">
                                Optimiser avec l'IA
                            </button>
                            <div id="optimize-status" class="mt-2 hidden"></div>
                        </div>

                        <div class="mb-4 flex gap-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" checked
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Produit actif</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="auto_sync" value="1"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Sync auto Etsy</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <a href="{{ route('products.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                                Annuler
                            </a>
                            <button type="submit" class="inline-flex items-center px-6 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                ✓ Créer le produit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Image selection state
        let selectedImages = [];

        // Function to display images in the preview grid
        function displayImages(images) {
            const grid = document.getElementById('images-grid');
            const section = document.getElementById('images-preview-section');
            const selectedInfo = document.getElementById('selected-images-info');

            if (!images || images.length === 0) {
                section.classList.add('hidden');
                return;
            }

            // Clear previous images
            grid.innerHTML = '';
            selectedImages = [];

            // Add each image to the grid
            images.forEach((imageUrl, index) => {
                const imageContainer = document.createElement('div');
                imageContainer.className = 'relative cursor-pointer group';
                imageContainer.innerHTML = `
                    <div class="aspect-square overflow-hidden rounded-lg border-2 border-gray-200 hover:border-blue-400 transition-all" data-index="${index}">
                        <img src="${imageUrl}" alt="Image ${index + 1}" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-blue-500 bg-opacity-0 group-hover:bg-opacity-10 transition-all"></div>
                        <div class="absolute top-2 right-2 w-6 h-6 rounded-full border-2 border-white bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600 shadow check-indicator">
                            ${index + 1}
                        </div>
                    </div>
                `;

                imageContainer.addEventListener('click', () => toggleImageSelection(index, imageUrl, imageContainer));
                grid.appendChild(imageContainer);
            });

            // Show the section
            section.classList.remove('hidden');
            selectedInfo.classList.remove('hidden');
            updateSelectedCount();
        }

        // Toggle image selection
        function toggleImageSelection(index, imageUrl, container) {
            const imgContainer = container.querySelector('[data-index]');
            const checkIndicator = container.querySelector('.check-indicator');

            if (selectedImages.includes(imageUrl)) {
                // Deselect
                selectedImages = selectedImages.filter(url => url !== imageUrl);
                imgContainer.classList.remove('border-green-500', 'ring-2', 'ring-green-300');
                imgContainer.classList.add('border-gray-200');
                checkIndicator.classList.remove('bg-green-500', 'text-white');
                checkIndicator.classList.add('bg-gray-200', 'text-gray-600');
                checkIndicator.innerHTML = index + 1;
            } else {
                // Select (max 10)
                if (selectedImages.length >= 10) {
                    alert('Maximum 10 images autorisées sur Etsy');
                    return;
                }
                selectedImages.push(imageUrl);
                imgContainer.classList.remove('border-gray-200');
                imgContainer.classList.add('border-green-500', 'ring-2', 'ring-green-300');
                checkIndicator.classList.remove('bg-gray-200', 'text-gray-600');
                checkIndicator.classList.add('bg-green-500', 'text-white');
                checkIndicator.innerHTML = '✓';
            }

            updateSelectedCount();
            document.getElementById('images_hidden').value = JSON.stringify(selectedImages);
        }

        // Update selected count display
        function updateSelectedCount() {
            document.getElementById('selected-count').textContent = selectedImages.length;
        }

        // Toggle between AliExpress and Printables sections
        const productTypeRadios = document.querySelectorAll('input[name="product_type"]');
        const aliexpressSection = document.getElementById('aliexpress-section');
        const printablesSection = document.getElementById('printables-section');
        const productSourceInput = document.getElementById('product_source');

        productTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'aliexpress') {
                    aliexpressSection.classList.remove('hidden');
                    printablesSection.classList.add('hidden');
                    productSourceInput.value = 'aliexpress';
                    updateCostLabel('aliexpress');
                } else {
                    aliexpressSection.classList.add('hidden');
                    printablesSection.classList.remove('hidden');
                    productSourceInput.value = 'printables';
                    updateCostLabel('printables');
                }
            });
        });

        // Auto-calculate Etsy price when AliExpress price is entered
        document.getElementById('aliexpress_price').addEventListener('input', function() {
            const aliPrice = parseFloat(this.value);
            if (aliPrice > 0) {
                const etsyPrice = (aliPrice * 3).toFixed(2);
                document.getElementById('price').value = etsyPrice;
                document.getElementById('cost_price').value = aliPrice.toFixed(2);
                document.getElementById('price-info').innerHTML =
                    `💰 Prix AliExpress: <strong>€${aliPrice.toFixed(2)}</strong> × 3 = <strong>€${etsyPrice}</strong>`;
            }
        });

        // Update cost label based on product type
        function updateCostLabel(type) {
            const costLabel = document.getElementById('cost-label');
            const costInfo = document.getElementById('cost-info');
            if (type === 'printables') {
                costLabel.textContent = 'Cout (optionnel)';
                costInfo.textContent = 'Fichier digital = cout 0€';
                document.getElementById('cost_price').value = '0';
            } else {
                costLabel.textContent = 'Cout fournisseur';
                costInfo.textContent = 'Prix d\'achat chez AliExpress';
            }
        }

        // Set Etsy price when STL price is entered
        function calculatePrintingPrice() {
            const price = parseFloat(document.getElementById('printing_cost').value) || 0;
            if (price > 0) {
                document.getElementById('price').value = price.toFixed(2);
                document.getElementById('price-info').innerHTML =
                    `📁 Prix fichier STL: <strong>€${price.toFixed(2)}</strong>`;
            }
        }

        document.getElementById('printing_cost').addEventListener('input', calculatePrintingPrice);

        // Analyze AliExpress URL
        document.getElementById('analyze-aliexpress-btn').addEventListener('click', async function() {
            const url = document.getElementById('aliexpress_url').value;
            const btn = this;
            const statusDiv = document.getElementById('aliexpress-status');

            if (!url) {
                statusDiv.className = 'mt-3 p-3 bg-red-100 border border-red-300 text-red-700 rounded-md';
                statusDiv.textContent = '❌ Veuillez entrer une URL AliExpress';
                statusDiv.classList.remove('hidden');
                return;
            }

            // Save URL to hidden field
            document.getElementById('aliexpress_url_hidden').value = url;

            // Disable button and show loading
            btn.disabled = true;
            btn.innerHTML = '⏳ Analyse...';
            statusDiv.className = 'mt-3 p-3 bg-blue-100 border border-blue-300 text-blue-800 rounded-md';
            statusDiv.innerHTML = '🔄 Extraction des données et optimisation IA en cours... <br><small>Cela peut prendre 20-40 secondes.</small>';
            statusDiv.classList.remove('hidden');

            try {
                const response = await fetch('{{ route('products.analyze-aliexpress') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ aliexpress_url: url })
                });

                const result = await response.json();

                if (result.success) {
                    // Fill form with optimized data
                    document.getElementById('title').value = result.data.title || '';
                    document.getElementById('description').value = result.data.description || '';

                    if (result.data.tags_string) {
                        document.getElementById('tags').value = result.data.tags_string;
                    }

                    // Get price from scraper or manual input
                    let aliPrice = result.data.original_price;
                    const manualPrice = parseFloat(document.getElementById('aliexpress_price').value);
                    if (!aliPrice && manualPrice > 0) {
                        aliPrice = manualPrice;
                    }

                    if (aliPrice && aliPrice > 0) {
                        const etsyPrice = (aliPrice * 3).toFixed(2);
                        document.getElementById('price').value = etsyPrice;
                        document.getElementById('price-info').innerHTML =
                            `💰 Prix AliExpress: <strong>€${aliPrice.toFixed(2)}</strong> × 3 = <strong>€${etsyPrice}</strong>`;
                    } else if (result.data.price) {
                        document.getElementById('price').value = result.data.price.toFixed(2);
                    }

                    // Display images if available
                    if (result.data.images && result.data.images.length > 0) {
                        displayImages(result.data.images);
                    }

                    // Show success message
                    statusDiv.className = 'mt-3 p-3 bg-green-100 border border-green-300 text-green-800 rounded-md';
                    let priceMsg = aliPrice ? '' : '<br><small class="text-orange-600">⚠️ Prix non détecté - entrez le prix AliExpress ci-dessus et recliquez Analyser</small>';
                    let imgMsg = result.data.images && result.data.images.length > 0 ? `<br><small>🖼️ ${result.data.images.length} images récupérées</small>` : '';
                    statusDiv.innerHTML = `
                        <strong>✅ Produit importé et optimisé !</strong><br>
                        <small>Titre, description et ${result.data.tags ? result.data.tags.length : 0} tags générés.</small>${priceMsg}${imgMsg}
                    `;

                    // Scroll to images or form
                    const scrollTarget = result.data.images && result.data.images.length > 0
                        ? document.getElementById('images-preview-section')
                        : document.getElementById('title');
                    scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (!result.data.images || result.data.images.length === 0) {
                        document.getElementById('title').focus();
                    }
                } else {
                    statusDiv.className = 'mt-3 p-3 bg-orange-100 border border-orange-300 text-orange-800 rounded-md';
                    if (result.use_manual) {
                        statusDiv.innerHTML = `
                            <strong>⚠️ Extraction partielle</strong><br>
                            <small>${result.message}</small>
                        `;
                    } else {
                        statusDiv.innerHTML = '❌ ' + result.message;
                    }
                }
            } catch (error) {
                statusDiv.className = 'mt-3 p-3 bg-red-100 border border-red-300 text-red-700 rounded-md';
                statusDiv.innerHTML = '❌ Erreur de connexion. Veuillez réessayer.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '🔍 Analyser';
            }
        });

        // Analyze Printables URL
        document.getElementById('analyze-printables-btn').addEventListener('click', async function() {
            const url = document.getElementById('printables_url').value;
            const btn = this;
            const statusDiv = document.getElementById('printables-status');
            const licenseWarning = document.getElementById('license-warning');

            if (!url) {
                statusDiv.className = 'mt-3 p-3 bg-red-100 border border-red-300 text-red-700 rounded-md';
                statusDiv.textContent = '❌ Veuillez entrer une URL Printables';
                statusDiv.classList.remove('hidden');
                return;
            }

            // Save URL to hidden field
            document.getElementById('printables_url_hidden').value = url;

            // Disable button and show loading
            btn.disabled = true;
            btn.innerHTML = '⏳ Analyse...';
            statusDiv.className = 'mt-3 p-3 bg-green-100 border border-green-300 text-green-800 rounded-md';
            statusDiv.innerHTML = '🔄 Extraction des données et optimisation IA en cours... <br><small>Cela peut prendre 20-40 secondes.</small>';
            statusDiv.classList.remove('hidden');
            licenseWarning.classList.add('hidden');

            try {
                const response = await fetch('{{ route('products.analyze-printables') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ printables_url: url })
                });

                const result = await response.json();

                if (result.success) {
                    // Fill form with optimized data
                    document.getElementById('title').value = result.data.title || '';
                    document.getElementById('description').value = result.data.description || '';

                    if (result.data.tags_string) {
                        document.getElementById('tags').value = result.data.tags_string;
                    }

                    // Store license info
                    document.getElementById('license_hidden').value = result.data.license || '';
                    document.getElementById('attribution_hidden').value = result.data.attribution || '';

                    // Show license warning if needed
                    if (result.data.license) {
                        const licenseText = document.getElementById('license-text');
                        if (result.data.commercial_allowed === false) {
                            licenseWarning.className = 'mt-3 p-3 bg-red-100 border border-red-400 text-red-800 rounded-md';
                            licenseText.innerHTML = `<strong>${result.data.license}</strong> - Cette licence interdit l'usage commercial ! Ne vendez pas ce modèle.`;
                        } else {
                            licenseWarning.className = 'mt-3 p-3 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded-md';
                            licenseText.innerHTML = `<strong>${result.data.license}</strong> - Vérifiez que l'usage commercial est autorisé. Attribution: ${result.data.author || 'Inconnu'}`;
                        }
                        licenseWarning.classList.remove('hidden');
                    }

                    // Calculate price if printing cost is set
                    calculatePrintingPrice();

                    // Display images if available
                    if (result.data.images && result.data.images.length > 0) {
                        displayImages(result.data.images);
                    }

                    // Show success message
                    statusDiv.className = 'mt-3 p-3 bg-green-100 border border-green-300 text-green-800 rounded-md';
                    let imgMsg = result.data.images && result.data.images.length > 0 ? `<br><small>🖼️ ${result.data.images.length} images récupérées</small>` : '';
                    statusDiv.innerHTML = `
                        <strong>✅ Fichier STL importé et optimisé !</strong><br>
                        <small>Titre, description et ${result.data.tags ? result.data.tags.length : 0} tags générés.</small><br>
                        <small>Auteur: ${result.data.author || 'Inconnu'} | Licence: ${result.data.license || 'Inconnue'}</small>${imgMsg}
                    `;

                    // Scroll to images or form
                    const scrollTarget = result.data.images && result.data.images.length > 0
                        ? document.getElementById('images-preview-section')
                        : document.getElementById('title');
                    scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (!result.data.images || result.data.images.length === 0) {
                        document.getElementById('title').focus();
                    }
                } else {
                    statusDiv.className = 'mt-3 p-3 bg-orange-100 border border-orange-300 text-orange-800 rounded-md';
                    statusDiv.innerHTML = '❌ ' + result.message;
                }
            } catch (error) {
                statusDiv.className = 'mt-3 p-3 bg-red-100 border border-red-300 text-red-700 rounded-md';
                statusDiv.innerHTML = '❌ Erreur de connexion. Veuillez réessayer.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '🔍 Analyser';
            }
        });

        // Manual optimization button
        document.getElementById('optimize-btn').addEventListener('click', async function() {
            const title = document.getElementById('title').value;
            const description = document.getElementById('description').value;
            const price = document.getElementById('price').value;
            const btn = this;
            const statusDiv = document.getElementById('optimize-status');

            if (!title || title.length < 3) {
                statusDiv.className = 'mt-2 p-3 bg-red-100 border border-red-400 text-red-700 rounded';
                statusDiv.textContent = '❌ Veuillez entrer un titre (minimum 3 caractères)';
                statusDiv.classList.remove('hidden');
                return;
            }

            btn.disabled = true;
            btn.textContent = '⏳ Optimisation...';
            statusDiv.className = 'mt-2 p-3 bg-blue-100 border border-blue-400 text-blue-700 rounded';
            statusDiv.textContent = '🔄 Génération du contenu optimisé SEO...';
            statusDiv.classList.remove('hidden');

            try {
                const response = await fetch('{{ route('products.optimize-content') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ title, description, price })
                });

                const result = await response.json();

                if (result.success) {
                    document.getElementById('title').value = result.data.title || '';
                    document.getElementById('description').value = result.data.description || '';

                    if (result.data.tags_string) {
                        document.getElementById('tags').value = result.data.tags_string;
                    }

                    if (result.data.price) {
                        document.getElementById('price').value = result.data.price.toFixed(2);
                    }

                    statusDiv.className = 'mt-2 p-3 bg-green-100 border border-green-400 text-green-700 rounded';
                    statusDiv.innerHTML = `✅ Contenu optimisé ! ${result.data.tags ? result.data.tags.length : 0} tags générés.`;
                } else {
                    statusDiv.className = 'mt-2 p-3 bg-red-100 border border-red-400 text-red-700 rounded';
                    statusDiv.textContent = '❌ ' + result.message;
                }
            } catch (error) {
                statusDiv.className = 'mt-2 p-3 bg-red-100 border border-red-400 text-red-700 rounded';
                statusDiv.textContent = '❌ Erreur. Veuillez réessayer.';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Optimiser avec l\'IA';
            }
        });
    </script>
</x-app-layout>
