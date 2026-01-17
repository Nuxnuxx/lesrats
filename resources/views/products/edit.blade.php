<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Modifier - {{ $product->title }}
            </h2>
            <a href="{{ route('products.show', $product) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                Retour
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('products.update', $product) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="title" class="block text-sm font-medium text-gray-700">Titre du produit</label>
                            <input type="text" name="title" id="title" value="{{ old('title', $product->title) }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $product->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700">Prix de vente ({{ $product->shop->currency }})</label>
                                <input type="number" name="price" id="price" step="0.01" min="0" value="{{ old('price', $product->price) }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('price')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="cost_price" class="block text-sm font-medium text-gray-700">
                                    @if($product->source_type === 'printables' || $product->is_digital)
                                        Cout ({{ $product->shop->currency }})
                                    @else
                                        Cout fournisseur ({{ $product->shop->currency }})
                                    @endif
                                </label>
                                <input type="number" name="cost_price" id="cost_price" step="0.01" min="0" value="{{ old('cost_price', $product->cost_price ?? 0) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @if($product->source_type === 'printables' || $product->is_digital)
                                    <p class="mt-1 text-xs text-gray-500">Fichier digital = cout 0</p>
                                @else
                                    <p class="mt-1 text-xs text-gray-500">Prix d'achat chez le fournisseur</p>
                                @endif
                                @error('cost_price')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        @if($product->price > 0 && $product->cost_price !== null)
                            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Profit par vente:</span>
                                    <span class="font-semibold text-green-600">
                                        +{{ number_format($product->price - ($product->cost_price ?? 0), 2) }} {{ $product->shop->currency }}
                                        @if($product->price > 0)
                                            ({{ number_format((($product->price - ($product->cost_price ?? 0)) / $product->price) * 100, 0) }}% marge)
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endif

                        <!-- Hidden quantity - not tracked for dropship/digital -->
                        <input type="hidden" name="quantity" value="{{ $product->quantity ?? 999 }}">

                        <div class="mb-4">
                            <label for="sku" class="block text-sm font-medium text-gray-700">SKU (optionnel)</label>
                            <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('sku')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Product Source Info --}}
                        @if($product->source_type)
                            <div class="mb-4 p-4 rounded-lg {{ $product->source_type === 'aliexpress' ? 'bg-red-50 border border-red-200' : ($product->source_type === 'printables' ? 'bg-purple-50 border border-purple-200' : 'bg-gray-50 border border-gray-200') }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        @if($product->source_type === 'aliexpress')
                                            <span class="text-red-600 font-medium">Dropshipping AliExpress</span>
                                        @elseif($product->source_type === 'printables')
                                            <span class="text-purple-600 font-medium">Fichier STL (Printables)</span>
                                        @else
                                            <span class="text-gray-600 font-medium">Produit manuel</span>
                                        @endif
                                        @if($product->is_digital)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Digital</span>
                                        @endif
                                    </div>
                                    @if($product->source_url)
                                        <a href="{{ $product->source_url }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800">
                                            Voir la source
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="mb-4">
                            <label for="source_url" class="block text-sm font-medium text-gray-700">URL Source (optionnel)</label>
                            <input type="url" name="source_url" id="source_url" value="{{ old('source_url', $product->source_url) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="https://...">
                            <p class="mt-1 text-xs text-gray-500">Lien vers le produit chez le fournisseur (AliExpress, Printables...)</p>
                            @error('source_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Produit actif</span>
                            </label>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="auto_sync" value="1" {{ old('auto_sync', $product->auto_sync) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Synchronisation automatique avec Etsy</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('products.show', $product) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                                Annuler
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
