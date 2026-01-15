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

            <!-- Step 1: AliExpress Import -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-white mb-2">📦 Étape 1 : Importer depuis AliExpress</h3>
                    <p class="text-blue-100 text-sm mb-4">Collez le lien du produit AliExpress pour remplir automatiquement le formulaire avec un contenu optimisé pour Etsy.</p>

                    <div class="flex gap-2 mb-3">
                        <input type="url" id="aliexpress_url" name="aliexpress_url" value="{{ old('aliexpress_url') }}"
                            placeholder="https://fr.aliexpress.com/item/123456789.html"
                            class="block w-full rounded-md border-0 shadow-sm focus:ring-2 focus:ring-white text-gray-900 placeholder-gray-400">
                        <button type="button" id="analyze-btn" class="inline-flex items-center px-6 py-2 bg-white border border-transparent rounded-md font-semibold text-sm text-blue-600 uppercase tracking-widest hover:bg-blue-50 disabled:opacity-50 whitespace-nowrap">
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

                    <div id="analyze-status" class="mt-3 hidden"></div>
                </div>
            </div>

            <!-- Product Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">📝 Étape 2 : Détails du produit</h3>

                    <form method="POST" action="{{ route('products.store') }}" id="product-form">
                        @csrf
                        <input type="hidden" name="aliexpress_url" id="aliexpress_url_hidden">

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

                            <div>
                                <label for="quantity" class="block text-sm font-medium text-gray-700">Quantité en stock</label>
                                <input type="number" name="quantity" id="quantity" min="0" value="{{ old('quantity', 999) }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('quantity')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

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
        // Auto-calculate Etsy price when AliExpress price is entered
        document.getElementById('aliexpress_price').addEventListener('input', function() {
            const aliPrice = parseFloat(this.value);
            if (aliPrice > 0) {
                const etsyPrice = (aliPrice * 3).toFixed(2);
                document.getElementById('price').value = etsyPrice;
                document.getElementById('price-info').innerHTML =
                    `💰 Prix AliExpress: <strong>€${aliPrice.toFixed(2)}</strong> × 3 = <strong>€${etsyPrice}</strong>`;
            }
        });

        // Analyze AliExpress URL
        document.getElementById('analyze-btn').addEventListener('click', async function() {
            const url = document.getElementById('aliexpress_url').value;
            const btn = this;
            const statusDiv = document.getElementById('analyze-status');

            if (!url) {
                statusDiv.className = 'p-3 bg-red-100 border border-red-300 text-red-700 rounded-md';
                statusDiv.textContent = '❌ Veuillez entrer une URL AliExpress';
                statusDiv.classList.remove('hidden');
                return;
            }

            // Save URL to hidden field
            document.getElementById('aliexpress_url_hidden').value = url;

            // Disable button and show loading
            btn.disabled = true;
            btn.innerHTML = '⏳ Analyse...';
            statusDiv.className = 'p-3 bg-blue-100 border border-blue-300 text-blue-800 rounded-md';
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

                    // Show success message
                    statusDiv.className = 'p-3 bg-green-100 border border-green-300 text-green-800 rounded-md';
                    let priceMsg = aliPrice ? '' : '<br><small class="text-orange-600">⚠️ Prix non détecté - entrez le prix AliExpress ci-dessus et recliquez Analyser</small>';
                    statusDiv.innerHTML = `
                        <strong>✅ Produit importé et optimisé !</strong><br>
                        <small>Titre, description et ${result.data.tags ? result.data.tags.length : 0} tags générés.</small>${priceMsg}
                    `;

                    // Scroll to form
                    document.getElementById('title').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    document.getElementById('title').focus();
                } else {
                    statusDiv.className = 'p-3 bg-orange-100 border border-orange-300 text-orange-800 rounded-md';
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
                statusDiv.className = 'p-3 bg-red-100 border border-red-300 text-red-700 rounded-md';
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
