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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('products.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="title" class="block text-sm font-medium text-gray-700">Titre du produit</label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700">Prix ({{ $shop->currency }})</label>
                                <input type="number" name="price" id="price" step="0.01" min="0" value="{{ old('price') }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('price')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="quantity" class="block text-sm font-medium text-gray-700">Quantité en stock</label>
                                <input type="number" name="quantity" id="quantity" min="0" value="{{ old('quantity', 0) }}" required
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

                        <div class="mb-4">
                            <label for="aliexpress_url" class="block text-sm font-medium text-gray-700">URL AliExpress (optionnel)</label>
                            <div class="flex gap-2">
                                <input type="url" name="aliexpress_url" id="aliexpress_url" value="{{ old('aliexpress_url') }}"
                                    placeholder="https://www.aliexpress.com/item/..."
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <button type="button" id="analyze-btn" class="mt-1 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 disabled:opacity-50">
                                    Analyser
                                </button>
                            </div>
                            @error('aliexpress_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Collez l'URL AliExpress et cliquez sur "Analyser" pour auto-remplir le formulaire avec des données optimisées pour Etsy</p>
                            <div id="analyze-status" class="mt-2 hidden"></div>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" checked
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Produit actif</span>
                            </label>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="auto_sync" value="1"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Synchronisation automatique avec Etsy</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('products.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                                Annuler
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Créer le produit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('analyze-btn').addEventListener('click', async function() {
            const url = document.getElementById('aliexpress_url').value;
            const btn = this;
            const statusDiv = document.getElementById('analyze-status');

            if (!url) {
                statusDiv.className = 'mt-2 p-3 bg-red-100 border border-red-400 text-red-700 rounded';
                statusDiv.textContent = 'Veuillez entrer une URL AliExpress';
                statusDiv.classList.remove('hidden');
                return;
            }

            // Disable button and show loading
            btn.disabled = true;
            btn.textContent = 'Analyse en cours...';
            statusDiv.className = 'mt-2 p-3 bg-blue-100 border border-blue-400 text-blue-700 rounded';
            statusDiv.textContent = 'Analyse du produit AliExpress en cours... Cela peut prendre 10-30 secondes.';
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

                    if (result.data.price) {
                        document.getElementById('price').value = result.data.price.toFixed(2);
                    }

                    // Show success message
                    statusDiv.className = 'mt-2 p-3 bg-green-100 border border-green-400 text-green-700 rounded';
                    statusDiv.innerHTML = `
                        <strong>✅ ${result.message}</strong><br>
                        ${result.data.original_price ? `<span class="text-sm">Prix AliExpress: $${result.data.original_price.toFixed(2)} → Prix suggéré: ${result.data.price ? result.data.price.toFixed(2) : 'N/A'} {{ $shop->currency }}</span>` : ''}
                    `;

                    // Scroll to title field
                    document.getElementById('title').scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    statusDiv.className = 'mt-2 p-3 bg-red-100 border border-red-400 text-red-700 rounded';
                    statusDiv.textContent = '❌ ' + result.message;
                }
            } catch (error) {
                statusDiv.className = 'mt-2 p-3 bg-red-100 border border-red-400 text-red-700 rounded';
                statusDiv.textContent = '❌ Erreur lors de l\'analyse. Veuillez réessayer ou entrer les données manuellement.';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Analyser';
            }
        });
    </script>
</x-app-layout>
