<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="{{ route('products.index', ['shop_id' => $product->shop_id]) }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Modifier le produit
                    </h2>
                    <p class="text-sm text-gray-500">{{ $product->shop->name }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <x-ui.source-badge :type="$product->source_type ?? 'manual'" />
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Main Form --}}
                <div class="lg:col-span-2 space-y-6">
                    <form method="POST" action="{{ route('products.update', $product) }}" id="product-form">
                        @csrf
                        @method('PUT')

                        {{-- Basic Info --}}
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations generales</h3>

                            <div class="space-y-4">
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="title" class="block text-sm font-medium text-gray-700">Titre du produit</label>
                                        <button type="button"
                                                onclick="copyToClipboard('{{ addslashes($product->title) }}')"
                                                class="text-gray-400 hover:text-blue-600 transition-colors"
                                                title="Copier le titre">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <input type="text" name="title" id="title" value="{{ old('title', $product->title) }}" required
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    @error('title')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                        <button type="button"
                                                onclick="copyToClipboard(`{{ addslashes($product->description) }}`)"
                                                class="text-gray-400 hover:text-blue-600 transition-colors"
                                                title="Copier la description">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <textarea name="description" id="description" rows="5"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('description', $product->description) }}</textarea>
                                    @error('description')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                @php
                                    $tagsArray = is_array($product->tags) ? $product->tags : (is_string($product->tags) ? json_decode($product->tags, true) : []);
                                    $tagsString = is_array($tagsArray) ? implode(', ', $tagsArray) : '';
                                @endphp
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="tags" class="block text-sm font-medium text-gray-700">Tags (13 max pour Etsy)</label>
                                        <button type="button"
                                                onclick="copyToClipboard('{{ addslashes($tagsString) }}')"
                                                class="text-gray-400 hover:text-blue-600 transition-colors"
                                                title="Copier les tags">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <input type="text" name="tags" id="tags" value="{{ old('tags', $tagsString) }}"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                        placeholder="tag1, tag2, tag3...">
                                    <p class="mt-1 text-xs text-gray-500">Separes par des virgules, 20 caracteres max par tag</p>
                                    @error('tags')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Etsy Category --}}
                                @php
                                    $shopCategories = $product->shop->etsy_categories ?? [];
                                @endphp
                                @if(count($shopCategories) > 0)
                                <div>
                                    <label for="etsy_category" class="block text-sm font-medium text-gray-700 mb-1">Categorie Etsy</label>
                                    <select name="etsy_category" id="etsy_category"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        <option value="">-- Selectionnez une categorie --</option>
                                        @foreach($shopCategories as $cat)
                                            <option value="{{ $cat['name'] }}" {{ old('etsy_category', $product->etsy_category) == $cat['name'] ? 'selected' : '' }}>
                                                {{ $cat['name'] }} ({{ $cat['etsy_name'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Utilisee lors de la publication sur Etsy</p>
                                    @error('etsy_category')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                @endif

                            </div>
                        </div>

                        {{-- Pricing --}}
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Prix et rentabilite</h3>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="price" class="block text-sm font-medium text-gray-700">Prix de vente</label>
                                    <div class="mt-1 relative">
                                        <input type="number" name="price" id="price" step="0.01" min="0" 
                                               value="{{ old('price', number_format($product->price, 2, '.', '')) }}" required
                                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 pr-12">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm">{{ $product->shop->currency }}</span>
                                        </div>
                                    </div>
                                    @error('price')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="cost_price" class="block text-sm font-medium text-gray-700">
                                        Cout d'achat
                                    </label>
                                    <div class="mt-1 relative">
                                        <input type="number" name="cost_price" id="cost_price" step="0.01" min="0" 
                                               value="{{ old('cost_price', number_format($product->cost_price ?? 0, 2, '.', '')) }}"
                                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 pr-12">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm">{{ $product->shop->currency }}</span>
                                        </div>
                                    </div>
                                    @if($product->source_type === 'printables' || $product->is_digital)
                                        <p class="mt-1 text-xs text-gray-500">Fichier digital = generalement 0</p>
                                    @endif
                                    @error('cost_price')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Margin Input --}}
                            @php
                                $initialProfit = round($product->price - ($product->cost_price ?? 0), 2);
                                $initialMargin = $product->price > 0 ? round((($product->price - ($product->cost_price ?? 0)) / $product->price) * 100, 1) : 0;
                            @endphp
                            <div class="mt-4" x-data="{
                                price: {{ round($product->price, 2) }},
                                cost: {{ round($product->cost_price ?? 0, 2) }},
                                margin: {{ $initialMargin }},
                                profit: {{ $initialProfit }},
                                
                                updateFromPrice() {
                                    this.profit = Math.round((this.price - this.cost) * 100) / 100;
                                    this.margin = this.price > 0 ? Math.round(((this.price - this.cost) / this.price) * 1000) / 10 : 0;
                                },
                                
                                updateFromMargin() {
                                    if (this.margin < 100) {
                                        this.price = (this.cost * 100) / (100 - this.margin);
                                        this.price = Math.round(this.price * 100) / 100;
                                        this.profit = this.price - this.cost;
                                        document.getElementById('price').value = this.price.toFixed(2);
                                    }
                                },
                                
                                setMargin(val) {
                                    this.margin = val;
                                    this.updateFromMargin();
                                }
                            }" x-init="
                                document.getElementById('price').addEventListener('input', (e) => { price = parseFloat(e.target.value) || 0; updateFromPrice(); });
                                document.getElementById('cost_price').addEventListener('input', (e) => { cost = parseFloat(e.target.value) || 0; updateFromPrice(); });
                            ">
                                <label for="margin_input" class="block text-sm font-medium text-gray-700">Marge souhaitee (%)</label>
                                <div class="mt-1 flex items-center gap-3">
                                    <div class="relative flex-1">
                                        <input type="number" id="margin_input" step="1" min="0" max="100"
                                               x-model.number="margin"
                                               @input="updateFromMargin()"
                                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 pr-8">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm">%</span>
                                        </div>
                                    </div>
                                    <div class="flex gap-1">
                                        <button type="button" @click="setMargin(20)" 
                                                class="px-2 py-1 text-xs rounded border hover:bg-gray-100"
                                                :class="Math.round(margin) === 20 ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-300 text-gray-600'">
                                            20%
                                        </button>
                                        <button type="button" @click="setMargin(30)" 
                                                class="px-2 py-1 text-xs rounded border hover:bg-gray-100"
                                                :class="Math.round(margin) === 30 ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-300 text-gray-600'">
                                            30%
                                        </button>
                                        <button type="button" @click="setMargin(50)" 
                                                class="px-2 py-1 text-xs rounded border hover:bg-gray-100"
                                                :class="Math.round(margin) === 50 ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-300 text-gray-600'">
                                            50%
                                        </button>
                                    </div>
                                </div>

                                {{-- Profit Calculator --}}
                                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Profit par vente</span>
                                        <span class="text-lg font-bold" 
                                              :class="profit >= 0 ? 'text-green-600' : 'text-red-600'" 
                                              x-text="(profit >= 0 ? '+' : '') + profit.toFixed(2) + ' {{ $product->shop->currency }}'"></span>
                                    </div>
                                    <div class="flex items-center justify-between mt-1">
                                        <span class="text-sm text-gray-600">Marge effective</span>
                                        <span class="text-sm font-medium" 
                                              :class="margin >= 30 ? 'text-green-600' : (margin >= 15 ? 'text-yellow-600' : 'text-red-600')" 
                                              x-text="margin.toFixed(1) + '%'"></span>
                                    </div>
                                    <p x-show="margin < 15" class="mt-2 text-xs text-red-600">Attention: marge faible</p>
                                </div>
                            </div>
                        </div>

                        {{-- Source Info --}}
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Source du produit</h3>

                            {{-- Source Type Display --}}
                            <div class="flex items-center space-x-3 mb-4">
                                <x-ui.source-badge :type="$product->source_type ?? 'manual'" size="md" />
                                @if($product->is_digital)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded text-sm font-medium bg-blue-100 text-blue-800">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        Digital
                                    </span>
                                @endif
                            </div>

                            <div>
                                <label for="source_url" class="block text-sm font-medium text-gray-700">URL Source</label>
                                <input type="url" name="source_url" id="source_url" value="{{ old('source_url', $product->source_url) }}"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                    placeholder="https://...">
                                <p class="mt-1 text-xs text-gray-500">Lien vers le produit chez le fournisseur</p>
                                @error('source_url')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            @if($product->source_url)
                                <a href="{{ $product->source_url }}" target="_blank" 
                                   class="mt-3 inline-flex items-center text-sm text-orange-600 hover:text-orange-700">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    Ouvrir la source
                                </a>
                            @endif
                        </div>

                        {{-- Stock Management --}}
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6" x-data="{
                            isUnlimited: {{ ($product->quantity ?? 999) >= 999 || ($product->is_digital ?? false) ? 'true' : 'false' }},
                            stockQty: {{ $product->quantity ?? 10 }},
                            isDigital: {{ ($product->is_digital ?? false) ? 'true' : 'false' }}
                        }">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Gestion du stock</h3>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="quantity" class="block text-sm font-medium text-gray-700">Quantite en stock</label>
                                    <input type="number" name="quantity" id="quantity"
                                           x-model="stockQty"
                                           :disabled="isUnlimited"
                                           min="0"
                                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 disabled:bg-gray-100 disabled:text-gray-500">
                                    <p x-show="isDigital" class="mt-1 text-xs text-green-600">Produit digital = Stock illimite</p>
                                    @error('quantity')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex items-end pb-2">
                                    <label class="flex items-center" x-show="!isDigital">
                                        <input type="checkbox"
                                               x-model="isUnlimited"
                                               @change="if(isUnlimited) stockQty = 999"
                                               class="w-4 h-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                        <span class="ml-2 text-sm text-gray-700">Stock illimite</span>
                                    </label>
                                </div>
                            </div>

                            {{-- Low Stock Alert --}}
                            <div x-show="!isUnlimited && !isDigital" class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Alerte stock bas</label>
                                <div class="mt-1 flex items-center gap-3">
                                    <input type="number"
                                           name="low_stock_threshold"
                                           value="{{ $product->low_stock_threshold ?? 5 }}"
                                           min="0"
                                           max="100"
                                           class="w-24 rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500">
                                    <span class="text-sm text-gray-500">unites</span>
                                </div>
                            </div>

                            {{-- Stock Status Indicator --}}
                            @if(!($product->is_digital ?? false) && ($product->quantity ?? 999) < 999)
                                @php
                                    $qty = $product->quantity ?? 0;
                                    $threshold = $product->low_stock_threshold ?? 5;
                                @endphp
                                <div class="p-3 rounded-lg {{ $qty <= 0 ? 'bg-red-50 border border-red-200' : ($qty <= $threshold ? 'bg-yellow-50 border border-yellow-200' : 'bg-green-50 border border-green-200') }}">
                                    <div class="flex items-center">
                                        @if($qty <= 0)
                                            <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-sm font-medium text-red-800">Rupture de stock!</span>
                                        @elseif($qty <= $threshold)
                                            <svg class="w-5 h-5 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-sm font-medium text-yellow-800">Stock bas: {{ $qty }} unites restantes</span>
                                        @else
                                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-sm font-medium text-green-800">En stock: {{ $qty }} unites</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Settings --}}
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Parametres</h3>

                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                                        class="w-4 h-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                    <span class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">Produit actif</span>
                                        <span class="block text-xs text-gray-500">Desactiver pour masquer le produit</span>
                                    </span>
                                </label>


                            </div>
                        </div>
                    </form>

                    {{-- Form Actions --}}
                    <div class="flex items-center justify-between">
                        <a href="{{ route('products.index', ['shop_id' => $product->shop_id]) }}" 
                           class="text-sm text-gray-500 hover:text-gray-700">
                            Annuler
                        </a>
                        <button type="submit" form="product-form"
                                class="inline-flex items-center px-6 py-3 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Enregistrer
                        </button>
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="space-y-6">
                    {{-- Product Preview --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">Apercu</h3>
                        
                        @php
                            $images = is_string($product->images) ? json_decode($product->images, true) : $product->images;
                            $images = is_array($images) ? $images : [];
                        @endphp

                        @if(count($images) > 0)
                            <div class="relative mb-4" x-data="{ currentImageIndex: 0 }">
                                <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                                    @foreach($images as $index => $image)
                                        <img x-show="currentImageIndex === {{ $index }}"
                                             src="{{ $image }}"
                                             alt="{{ $product->title }}"
                                             class="w-full h-full object-cover">
                                    @endforeach
                                </div>

                                @if(count($images) > 1)
                                    {{-- Navigation Arrows --}}
                                    <button type="button"
                                            @click="currentImageIndex = currentImageIndex > 0 ? currentImageIndex - 1 : {{ count($images) - 1 }}"
                                            class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-2 shadow-lg transition-all">
                                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                        </svg>
                                    </button>

                                    <button type="button"
                                            @click="currentImageIndex = currentImageIndex < {{ count($images) - 1 }} ? currentImageIndex + 1 : 0"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-2 shadow-lg transition-all">
                                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>

                                    {{-- Indicator Dots --}}
                                    <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1 bg-black/30 backdrop-blur-sm px-2 py-1 rounded-full">
                                        @foreach($images as $index => $image)
                                            <button type="button"
                                                    @click="currentImageIndex = {{ $index }}"
                                                    :class="currentImageIndex === {{ $index }} ? 'bg-white w-6' : 'bg-white/50 w-2 hover:bg-white/75'"
                                                    class="h-2 rounded-full transition-all"></button>
                                        @endforeach
                                    </div>

                                    {{-- Image Counter --}}
                                    <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-sm text-white px-2 py-1 rounded-full text-xs font-medium">
                                        <span x-text="currentImageIndex + 1"></span>/{{ count($images) }}
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden mb-4 flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif

                        <p class="text-sm font-medium text-gray-900 line-clamp-2">{{ $product->title }}</p>
                        <p class="text-lg font-bold text-gray-900 mt-1">{{ number_format($product->price, 2) }} {{ $product->shop->currency }}</p>
                    </div>

                    {{-- Publish to Etsy --}}
                    <div class="bg-white rounded-lg shadow-sm border border-orange-200 p-6">
                        <h3 class="text-sm font-semibold text-orange-600 mb-4">Publier sur Etsy</h3>
                        
                        <p class="text-xs text-gray-500 mb-3">
                            Ouvrez Etsy et remplissez automatiquement le formulaire avec les donnees de ce produit.
                        </p>
                        
                        <button type="button" 
                                data-product-id="{{ $product->id }}"
                                data-shop-name="{{ $product->shop->name }}"
                                onclick="publishToEtsy(this.dataset.productId, this.dataset.shopName)"
                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-orange-100 text-orange-700 rounded-lg text-sm font-medium hover:bg-orange-200 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Ouvrir Etsy & Remplir
                        </button>

                        <p class="text-xs text-gray-400 mt-3">
                            <strong>Rappel:</strong> Vous devrez ajouter les images et la categorie manuellement sur Etsy.
                        </p>
                    </div>

                    {{-- AI Image Generation --}}
                    @php
                        $hasImages = is_array($product->images) ? !empty($product->images) : !empty(json_decode($product->images, true));
                        $hasImagePrompt = !empty($product->shop->ai_image_prompt);
                    @endphp
                    @if($hasImages)
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4">Generation IA d'images</h3>
                            
                            @if($hasImagePrompt)
                                <p class="text-xs text-gray-500 mb-3">
                                    Transformez les images avec Fal.ai en utilisant le prompt configure dans la boutique.
                                </p>
                                <form action="{{ route('products.generate-ai-images', $product) }}" method="POST" 
                                      onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = '<svg class=\'animate-spin w-4 h-4 mr-2\' fill=\'none\' viewBox=\'0 0 24 24\'><circle class=\'opacity-25\' cx=\'12\' cy=\'12\' r=\'10\' stroke=\'currentColor\' stroke-width=\'4\'></circle><path class=\'opacity-75\' fill=\'currentColor\' d=\'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\'></path></svg> Generation en cours...';">
                                    @csrf
                                    <button type="submit" 
                                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-purple-100 text-purple-700 rounded-lg text-sm font-medium hover:bg-purple-200 transition-colors">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        Generer images IA
                                    </button>
                                </form>
                                <p class="text-xs text-gray-400 mt-2">
                                    Prompt: "{{ Str::limit($product->shop->ai_image_prompt, 50) }}"
                                </p>
                            @else
                                <p class="text-xs text-gray-500 mb-3">
                                    Pour generer des images IA, configurez d'abord un prompt dans les parametres de la boutique.
                                </p>
                                <a href="{{ route('shops.edit', $product->shop) }}" 
                                   class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Configurer le prompt
                                </a>
                            @endif
                        </div>
                    @endif

                    {{-- Stats --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">Statistiques</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Ventes totales</span>
                                <span class="font-medium text-gray-900">{{ $product->total_sold ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Revenus</span>
                                <span class="font-medium text-gray-900">{{ number_format($product->total_revenue ?? 0, 2) }} {{ $product->shop->currency }}</span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Cree le</span>
                                <span class="font-medium text-gray-900">{{ $product->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Danger Zone --}}
                    <div class="bg-white rounded-lg shadow-sm border border-red-200 p-6">
                        <h3 class="text-sm font-semibold text-red-600 mb-4">Zone de danger</h3>
                        
                        <x-ui.confirm-modal 
                            id="delete-product"
                            title="Supprimer ce produit"
                            message="Cette action est irreversible. Le produit sera supprime de votre boutique."
                            confirmLabel="Supprimer"
                            type="danger"
                            :formAction="route('products.destroy', $product)"
                            formMethod="DELETE"
                        >
                            <x-slot name="trigger">
                                <button type="button" 
                                        class="w-full inline-flex items-center justify-center px-4 py-2 border border-red-300 text-red-600 rounded-lg text-sm font-medium hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Supprimer le produit
                                </button>
                            </x-slot>
                        </x-ui.confirm-modal>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        // Simple discrete copy function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Show simple toast notification
                const toast = document.createElement('div');
                toast.textContent = 'Copie';
                toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 2000);
            }).catch(err => {
                alert('Erreur lors de la copie: ' + err);
            });
        }

        // Publish to Etsy function
        function publishToEtsy(productId, shopName) {
            // Show toast that we're preparing
            const toast = document.createElement('div');
            toast.textContent = 'Ouverture d\'Etsy...';
            toast.className = 'fixed bottom-4 right-4 bg-orange-500 text-white px-4 py-2 rounded shadow-lg z-50';
            document.body.appendChild(toast);

            // Try to communicate with extension via postMessage
            // The extension will pick this up and store it
            const message = {
                type: 'LESRATS_PUBLISH_TO_ETSY',
                productId: productId,
                shopName: shopName,
                apiUrl: window.location.origin
            };

            // Try localStorage as fallback for extension communication
            localStorage.setItem('lesrats_pending_etsy', JSON.stringify({
                productId: productId,
                shopName: shopName,
                apiUrl: window.location.origin,
                timestamp: Date.now()
            }));

            // Open Etsy in new tab
            const etsyUrl = 'https://www.etsy.com/your/shops/me/listing-editor/create';
            
            // Update toast and open Etsy
            setTimeout(() => {
                toast.textContent = 'Utilisez l\'extension LesRats sur la page Etsy pour remplir le formulaire!';
                toast.className = 'fixed bottom-4 right-4 bg-blue-500 text-white px-4 py-2 rounded shadow-lg z-50 max-w-sm';
                
                // Open Etsy
                window.open(etsyUrl, '_blank');

                // Remove toast after a longer delay
                setTimeout(() => toast.remove(), 5000);
            }, 500);

            // Also try to trigger extension via custom event (if extension has content script on this page)
            window.postMessage(message, '*');
        }

    </script>
    @endpush
</x-app-layout>
