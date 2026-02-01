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

                        {{-- Country-specific Pricing (AliExpress only) --}}
                        @if($product->country_prices && count($product->country_prices) > 0)
                        <div class="bg-white rounded-lg shadow-sm border border-blue-200 p-6 mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Prix par pays (AliExpress)</h3>
                            
                            {{-- Country prices table --}}
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200">
                                            <th class="text-left py-2 px-2 font-medium text-gray-600">Pays</th>
                                            <th class="text-right py-2 px-2 font-medium text-gray-600">Prix</th>
                                            <th class="text-right py-2 px-2 font-medium text-gray-600">Livraison</th>
                                            <th class="text-right py-2 px-2 font-medium text-gray-600">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $countryNames = [
                                                'US' => 'Etats-Unis',
                                                'DE' => 'Allemagne',
                                                'AT' => 'Autriche',
                                                'FR' => 'France',
                                                'CA' => 'Canada',
                                                'ES' => 'Espagne',
                                            ];
                                            $countryFlags = [
                                                'US' => '🇺🇸',
                                                'DE' => '🇩🇪',
                                                'AT' => '🇦🇹',
                                                'FR' => '🇫🇷',
                                                'CA' => '🇨🇦',
                                                'ES' => '🇪🇸',
                                            ];
                                            $highestOther = $product->getHighestOtherCountry();
                                        @endphp
                                        @foreach($product->country_prices as $code => $data)
                                            <tr class="border-b border-gray-100 {{ $code === 'US' ? 'bg-blue-50' : ($code === $highestOther ? 'bg-orange-50' : '') }}">
                                                <td class="py-2 px-2">
                                                    <span class="mr-1">{{ $countryFlags[$code] ?? '' }}</span>
                                                    {{ $countryNames[$code] ?? $code }}
                                                    @if($code === 'US')
                                                        <span class="ml-1 text-xs text-blue-600">(Zone US)</span>
                                                    @elseif($code === $highestOther)
                                                        <span class="ml-1 text-xs text-orange-600">(Max autres)</span>
                                                    @endif
                                                </td>
                                                <td class="text-right py-2 px-2">{{ number_format($data['price'] ?? 0, 2) }} EUR</td>
                                                <td class="text-right py-2 px-2">{{ number_format($data['shipping'] ?? 0, 2) }} EUR</td>
                                                <td class="text-right py-2 px-2 font-medium">{{ number_format($data['total'] ?? 0, 2) }} EUR</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Etsy Selling Prices --}}
                            <div class="mt-6 pt-4 border-t border-gray-200">
                                <h4 class="text-sm font-semibold text-gray-900 mb-3">Prix de vente Etsy</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-data="{
                                    marginUs: {{ $product->margin_us ?? $product->shop->default_margin_us ?? 2.5 }},
                                    marginOther: {{ $product->margin_other ?? $product->shop->default_margin_other ?? 2.5 }},
                                    usCost: {{ $product->getUsTotalCost() ?? 0 }},
                                    otherCost: {{ $product->getHighestOtherCountryTotal() ?? 0 }},
                                    priceUs: {{ $product->price_us ?? 0 }},
                                    priceOther: {{ $product->price_other ?? 0 }},
                                    
                                    recalculateUs() {
                                        this.priceUs = Math.round(this.usCost * this.marginUs * 100) / 100;
                                        document.getElementById('price_us').value = this.priceUs.toFixed(2);
                                    },
                                    recalculateOther() {
                                        this.priceOther = Math.round(this.otherCost * this.marginOther * 100) / 100;
                                        document.getElementById('price_other').value = this.priceOther.toFixed(2);
                                    }
                                }">
                                    {{-- US Price --}}
                                    <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-blue-800">🇺🇸 Etats-Unis</span>
                                            <span class="text-xs text-blue-600">Cout: {{ number_format($product->getUsTotalCost() ?? 0, 2) }} EUR</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1">
                                                <label class="block text-xs text-gray-600 mb-1">Prix de vente</label>
                                                <div class="relative">
                                                    <input type="number" name="price_us" id="price_us" step="0.01" min="0"
                                                           x-model.number="priceUs"
                                                           value="{{ old('price_us', number_format($product->price_us ?? 0, 2, '.', '')) }}"
                                                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-12 text-sm">
                                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                        <span class="text-gray-500 text-xs">{{ $product->shop->currency }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="w-20">
                                                <label class="block text-xs text-gray-600 mb-1">Marge</label>
                                                <div class="relative">
                                                    <input type="number" name="margin_us" id="margin_us" step="0.1" min="1" max="10"
                                                           x-model.number="marginUs"
                                                           @input="recalculateUs()"
                                                           value="{{ old('margin_us', $product->margin_us ?? '') }}"
                                                           placeholder="{{ $product->shop->default_margin_us ?? 2.5 }}"
                                                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-6 text-sm">
                                                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none">
                                                        <span class="text-gray-500 text-xs">x</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2 flex justify-between text-xs">
                                            <span class="text-gray-500">Profit: <span class="font-medium" :class="priceUs - usCost >= 0 ? 'text-green-600' : 'text-red-600'" x-text="(priceUs - usCost).toFixed(2) + ' EUR'"></span></span>
                                            <button type="button" @click="recalculateUs()" class="text-blue-600 hover:text-blue-800">Recalculer</button>
                                        </div>
                                    </div>

                                    {{-- Other Countries Price --}}
                                    <div class="p-4 bg-orange-50 rounded-lg border border-orange-200">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-orange-800">🌍 Autres pays</span>
                                            <span class="text-xs text-orange-600">Cout max: {{ number_format($product->getHighestOtherCountryTotal() ?? 0, 2) }} EUR</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1">
                                                <label class="block text-xs text-gray-600 mb-1">Prix de vente</label>
                                                <div class="relative">
                                                    <input type="number" name="price_other" id="price_other" step="0.01" min="0"
                                                           x-model.number="priceOther"
                                                           value="{{ old('price_other', number_format($product->price_other ?? 0, 2, '.', '')) }}"
                                                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 pr-12 text-sm">
                                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                        <span class="text-gray-500 text-xs">{{ $product->shop->currency }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="w-20">
                                                <label class="block text-xs text-gray-600 mb-1">Marge</label>
                                                <div class="relative">
                                                    <input type="number" name="margin_other" id="margin_other" step="0.1" min="1" max="10"
                                                           x-model.number="marginOther"
                                                           @input="recalculateOther()"
                                                           value="{{ old('margin_other', $product->margin_other ?? '') }}"
                                                           placeholder="{{ $product->shop->default_margin_other ?? 2.5 }}"
                                                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 pr-6 text-sm">
                                                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none">
                                                        <span class="text-gray-500 text-xs">x</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2 flex justify-between text-xs">
                                            <span class="text-gray-500">Profit: <span class="font-medium" :class="priceOther - otherCost >= 0 ? 'text-green-600' : 'text-red-600'" x-text="(priceOther - otherCost).toFixed(2) + ' EUR'"></span></span>
                                            <button type="button" @click="recalculateOther()" class="text-orange-600 hover:text-orange-800">Recalculer</button>
                                        </div>
                                    </div>
                                </div>

                                <p class="mt-3 text-xs text-gray-500">
                                    <strong>Note:</strong> "Autres pays" utilise le cout le plus eleve parmi DE, AT, FR, CA, ES pour garantir la marge.
                                    Laissez la marge vide pour utiliser la valeur par defaut de la boutique.
                                </p>
                            </div>
                        </div>
                        @endif

                        {{-- Pricing (Legacy/Manual) --}}
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                @if($product->country_prices && count($product->country_prices) > 0)
                                    Prix de vente general (fallback)
                                @else
                                    Prix et rentabilite
                                @endif
                            </h3>

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
                    {{-- Product Preview with AI Edit --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" 
                         x-data="aiImageEditor({
                            images: {{ json_encode(is_array($product->images) ? $product->images : json_decode($product->images, true) ?? []) }},
                            realImages: {{ json_encode($product->real_images ?? []) }},
                            productId: {{ $product->id }},
                            defaultPrompt: {{ json_encode($product->shop->getEffectiveAiImagePrompt()) }},
                            csrfToken: '{{ csrf_token() }}'
                         })">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">Apercu</h3>
                        
                        @php
                            $images = is_array($product->images) ? $product->images : json_decode($product->images, true) ?? [];
                        @endphp

                        @if(count($images) > 0)
                            <div class="relative mb-4 group">
                                <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                                    @foreach($images as $index => $image)
                                        <img x-show="currentImageIndex === {{ $index }}"
                                             src="{{ $image }}"
                                             alt="{{ $product->title }}"
                                             class="w-full h-full object-cover">
                                    @endforeach
                                </div>

                                {{-- AI Edit Button (appears on hover) --}}
                                <button type="button"
                                        @click="openModal()"
                                        class="absolute top-2 left-2 bg-purple-600 hover:bg-purple-700 text-white rounded-full p-2 shadow-lg transition-all opacity-0 group-hover:opacity-100 focus:opacity-100"
                                        title="Modifier avec l'IA">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                    </svg>
                                </button>

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

                        {{-- AI Image Edit Modal --}}
                        <div x-show="showModal" 
                             x-cloak
                             class="fixed inset-0 z-50 overflow-y-auto"
                             x-transition:enter="ease-out duration-300"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="ease-in duration-200"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0">
                            {{-- Backdrop --}}
                            <div class="fixed inset-0 bg-gray-500/75" @click="closeModal()"></div>
                            
                            {{-- Modal Content --}}
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl"
                                     x-transition:enter="ease-out duration-300"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="ease-in duration-200"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     @click.away="closeModal()">
                                    
                                    {{-- Modal Header --}}
                                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                            </svg>
                                            <span x-text="step === 'select' ? 'Modifier l\'image avec l\'IA' : 'Resultat'"></span>
                                        </h3>
                                        <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Modal Body --}}
                                    <div class="p-6">
                                        {{-- Step 1: Select Image & Configure --}}
                                        <div x-show="step === 'select'">
                                            {{-- Image Selection --}}
                                            <div class="mb-6">
                                                <label class="block text-sm font-medium text-gray-700 mb-3">Selectionnez l'image source:</label>
                                                <div class="grid grid-cols-4 gap-2">
                                                    <template x-for="(img, index) in images" :key="index">
                                                        <button type="button"
                                                                @click="selectedImageIndex = index"
                                                                :class="selectedImageIndex === index ? 'ring-2 ring-purple-500 ring-offset-2' : 'hover:opacity-75'"
                                                                class="aspect-square rounded-lg overflow-hidden bg-gray-100 transition-all">
                                                            <img :src="img" class="w-full h-full object-cover">
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>

                                            {{-- Prompt Input --}}
                                            <div class="mb-6">
                                                <label for="ai-prompt" class="block text-sm font-medium text-gray-700 mb-2">Prompt de transformation:</label>
                                                <textarea id="ai-prompt"
                                                          x-model="prompt"
                                                          rows="4"
                                                          class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm"
                                                          placeholder="Decrivez comment transformer l'image..."></textarea>
                                            </div>

                                            {{-- Background Selection --}}
                                            <div class="mb-6">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Image de reference (Background):</label>
                                                <select x-model="selectedBackground"
                                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                                                    <option value="">Aucun (utiliser l'image originale)</option>
                                                    @foreach($backgrounds as $index => $bg)
                                                        <option value="{{ Storage::disk('public')->url($bg['path']) }}">{{ $bg['name'] }}</option>
                                                    @endforeach
                                                </select>
                                                <p class="mt-1 text-xs text-gray-500">
                                                    @if(count($backgrounds) > 0)
                                                        Selectionnez un background pour l'utiliser comme reference lors de la generation.
                                                    @else
                                                        <span class="text-orange-600">Aucun background configure. <a href="{{ route('shops.edit', $product->shop) }}" class="underline">Ajouter des backgrounds</a></span>
                                                    @endif
                                                </p>
                                            </div>

                                            {{-- Error Message --}}
                                            <div x-show="errorMessage" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                                <p class="text-sm text-red-600" x-text="errorMessage"></p>
                                            </div>
                                        </div>

                                        {{-- Step 2: Show Result --}}
                                        <div x-show="step === 'result'">
                                            <div class="grid grid-cols-2 gap-4 mb-6">
                                                {{-- Original --}}
                                                <div>
                                                    <p class="text-xs font-medium text-gray-500 mb-2 text-center">Original</p>
                                                    <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                                                        <img :src="images[selectedImageIndex]" class="w-full h-full object-cover">
                                                    </div>
                                                </div>
                                                {{-- Generated --}}
                                                <div>
                                                    <p class="text-xs font-medium text-gray-500 mb-2 text-center">Generee par IA</p>
                                                    <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                                                        <img :src="generatedImage" class="w-full h-full object-cover">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                                <p class="text-sm text-green-700">Image ajoutee aux "Images reelles" avec succes!</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Modal Footer --}}
                                    <div class="flex items-center justify-end gap-3 p-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                                        <template x-if="step === 'select'">
                                            <div class="flex items-center gap-3 w-full justify-end">
                                                <button type="button" 
                                                        @click="closeModal()"
                                                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                                                    Annuler
                                                </button>
                                                <button type="button"
                                                        @click="generateImage()"
                                                        :disabled="isGenerating || !prompt.trim()"
                                                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                                    <template x-if="isGenerating">
                                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                    </template>
                                                    <template x-if="!isGenerating">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                                        </svg>
                                                    </template>
                                                    <span x-text="isGenerating ? 'Generation en cours...' : 'Generer l\'image'"></span>
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="step === 'result'">
                                            <div class="flex items-center gap-3 w-full justify-end">
                                                <button type="button"
                                                        @click="step = 'select'; generatedImage = null;"
                                                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                                                    Generer une autre
                                                </button>
                                                <button type="button"
                                                        @click="closeModal()"
                                                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    Terminer
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Real Images Section (AI Generated) --}}
                    <div class="bg-white rounded-lg shadow-sm border border-purple-200 p-6"
                         x-data="realImagesManager({
                            images: {{ json_encode($product->real_images ?? []) }},
                            productId: {{ $product->id }},
                            csrfToken: '{{ csrf_token() }}'
                         })">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-purple-600 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                                Images reelles (IA)
                            </h3>
                            <span class="text-xs text-gray-500" x-text="images.length + ' image(s)'"></span>
                        </div>
                        
                        <template x-if="images.length === 0">
                            <div class="text-center py-6">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-sm text-gray-500">Aucune image reelle generee</p>
                                <p class="text-xs text-gray-400 mt-1">Utilisez le bouton sur l'apercu pour creer des images IA</p>
                            </div>
                        </template>

                        <template x-if="images.length > 0">
                            <div class="grid grid-cols-3 gap-2">
                                <template x-for="(img, index) in images" :key="index">
                                    <div class="relative group aspect-square rounded-lg overflow-hidden bg-gray-100">
                                        <img :src="img" class="w-full h-full object-cover">
                                        <button type="button"
                                                @click="removeImage(index)"
                                                class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                                title="Supprimer">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <p class="text-xs text-gray-400 mt-3">
                            Ces images seront envoyees a Etsy (pas les images AliExpress).
                        </p>
                    </div>

                    {{-- Publish to Etsy --}}
                    @php
                        $etsyCategory = $product->etsy_category;
                        $etsyCategoryData = null;
                        if ($etsyCategory && !empty($product->shop->etsy_categories)) {
                            foreach ($product->shop->etsy_categories as $cat) {
                                if ($cat['name'] === $etsyCategory) {
                                    $etsyCategoryData = $cat;
                                    break;
                                }
                            }
                        }
                    @endphp
                    <div class="bg-white rounded-lg shadow-sm border border-orange-200 p-6">
                        <h3 class="text-sm font-semibold text-orange-600 mb-4">Publier sur Etsy</h3>
                        
                        <p class="text-xs text-gray-500 mb-3">
                            Publication automatique: categorie, titre, description, prix, tags.
                        </p>
                        
                        <button type="button" 
                                data-product-id="{{ $product->id }}"
                                data-category-name="{{ $etsyCategoryData['etsy_name'] ?? '' }}"
                                data-is-digital="{{ $product->is_digital ? 'true' : 'false' }}"
                                onclick="publishToEtsy(this.dataset)"
                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-orange-100 text-orange-700 rounded-lg text-sm font-medium hover:bg-orange-200 transition-colors"
                                {{ !$etsyCategoryData ? 'disabled' : '' }}>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Ouvrir Etsy & Remplir
                        </button>

                        @if(!$etsyCategoryData)
                            <p class="text-xs text-red-500 mt-2">
                                Selectionnez une categorie Etsy pour ce produit.
                            </p>
                        @else
                            <p class="text-xs text-green-600 mt-2">
                                Categorie: {{ $etsyCategoryData['etsy_name'] }}
                            </p>
                        @endif
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
        // AI Image Editor Alpine.js Component
        function aiImageEditor(config) {
            return {
                images: config.images || [],
                realImages: config.realImages || [],
                productId: config.productId,
                defaultPrompt: config.defaultPrompt,
                csrfToken: config.csrfToken,
                
                // State
                showModal: false,
                step: 'select', // 'select' or 'result'
                currentImageIndex: 0,
                selectedImageIndex: 0,
                prompt: config.defaultPrompt || '',
                selectedBackground: '',
                isGenerating: false,
                generatedImage: null,
                errorMessage: null,

                openModal() {
                    this.showModal = true;
                    this.step = 'select';
                    this.selectedImageIndex = this.currentImageIndex;
                    this.prompt = this.defaultPrompt || '';
                    this.selectedBackground = '';
                    this.generatedImage = null;
                    this.errorMessage = null;
                    document.body.classList.add('overflow-hidden');
                },

                closeModal() {
                    this.showModal = false;
                    document.body.classList.remove('overflow-hidden');
                },

                async generateImage() {
                    if (!this.prompt.trim()) {
                        this.errorMessage = 'Veuillez entrer un prompt de transformation.';
                        return;
                    }

                    this.isGenerating = true;
                    this.errorMessage = null;

                    try {
                        const response = await fetch(`/products/${this.productId}/transform-single-image`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                image_url: this.images[this.selectedImageIndex],
                                prompt: this.prompt,
                                background_url: this.selectedBackground || null,
                            }),
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.generatedImage = data.data.transformed_image;
                            this.realImages = data.data.real_images;
                            this.step = 'result';
                            
                            // Dispatch event to update real images section
                            window.dispatchEvent(new CustomEvent('real-images-updated', { 
                                detail: { images: data.data.real_images } 
                            }));
                        } else {
                            this.errorMessage = data.message || 'Une erreur est survenue.';
                        }
                    } catch (error) {
                        console.error('Error generating image:', error);
                        this.errorMessage = 'Erreur de connexion. Veuillez reessayer.';
                    } finally {
                        this.isGenerating = false;
                    }
                },

                async removeRealImage(index) {
                    if (!confirm('Supprimer cette image?')) return;

                    try {
                        const response = await fetch(`/products/${this.productId}/remove-real-image`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ image_index: index }),
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.realImages = data.data.real_images;
                        } else {
                            alert(data.message || 'Erreur lors de la suppression.');
                        }
                    } catch (error) {
                        console.error('Error removing image:', error);
                        alert('Erreur de connexion.');
                    }
                }
            };
        }

        // Real Images Manager Alpine.js Component
        function realImagesManager(config) {
            return {
                images: config.images || [],
                productId: config.productId,
                csrfToken: config.csrfToken,

                init() {
                    // Listen for updates from the AI image editor
                    window.addEventListener('real-images-updated', (event) => {
                        this.images = event.detail.images;
                    });
                },

                async removeImage(index) {
                    if (!confirm('Supprimer cette image?')) return;

                    try {
                        const response = await fetch(`/products/${this.productId}/remove-real-image`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ image_index: index }),
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.images = data.data.real_images;
                        } else {
                            alert(data.message || 'Erreur lors de la suppression.');
                        }
                    } catch (error) {
                        console.error('Error removing image:', error);
                        alert('Erreur de connexion.');
                    }
                }
            };
        }

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

        // Publish to Etsy function - Full automation
        function publishToEtsy(dataset) {
            const categoryName = dataset.categoryName;
            const isDigital = dataset.isDigital === 'true';
            const productId = dataset.productId;
            
            if (!categoryName) {
                alert('Veuillez d\'abord selectionner une categorie Etsy pour ce produit.');
                return;
            }
            
            // Show toast
            const toast = document.createElement('div');
            toast.textContent = 'Preparation des donnees...';
            toast.className = 'fixed bottom-4 right-4 bg-orange-500 text-white px-4 py-2 rounded shadow-lg z-50';
            document.body.appendChild(toast);

            // Full product data for Etsy
            const productData = {
                categoryName: categoryName,
                isDigital: isDigital,
                productId: productId,
                apiUrl: window.location.origin,
                timestamp: Date.now()
            };

            // Send message to extension content script
            window.postMessage({
                type: 'LESRATS_PUBLISH_TO_ETSY',
                ...productData
            }, '*');

            // Also save to localStorage as fallback
            localStorage.setItem('lesrats_pending_etsy', JSON.stringify(productData));

            // Open Etsy
            setTimeout(() => {
                toast.textContent = 'Ouverture d\'Etsy - Remplissage automatique...';
                toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50';
                window.open('https://www.etsy.com/your/shops/me/listing-editor/create', '_blank');
                setTimeout(() => toast.remove(), 2000);
            }, 200);
        }

    </script>
    @endpush
</x-app-layout>
