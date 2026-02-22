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

                                {{-- Sizes --}}
                                <div x-data="{
                                    sizes: {{ Js::from($product->sizes ?? []) }},
                                    newSize: '',
                                    addSize() {
                                        const s = this.newSize.trim().toUpperCase();
                                        if (s && !this.sizes.includes(s)) {
                                            this.sizes.push(s);
                                        }
                                        this.newSize = '';
                                    },
                                    removeSize(index) {
                                        this.sizes.splice(index, 1);
                                    }
                                }">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tailles disponibles</label>
                                    <div class="flex flex-wrap gap-2 mb-2 min-h-[36px] p-2 border border-gray-200 rounded-lg bg-gray-50">
                                        <span x-show="sizes.length === 0" class="text-sm text-gray-400 italic">Aucune taille</span>
                                        <template x-for="(size, index) in sizes" :key="index">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                <span x-text="size"></span>
                                                <button type="button" @click="removeSize(index)" class="ml-1.5 text-blue-600 hover:text-blue-800">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>
                                    </div>
                                    <div class="flex gap-2">
                                        <input type="text" x-model="newSize"
                                            @keydown.enter.prevent="addSize()"
                                            @keydown.comma.prevent="addSize()"
                                            class="flex-1 text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500"
                                            placeholder="Ajouter une taille (ex: S, M, L, XL)">
                                        <button type="button" @click="addSize()"
                                            class="px-3 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm font-medium hover:bg-blue-200">
                                            +
                                        </button>
                                    </div>
                                    <input type="hidden" name="sizes" :value="JSON.stringify(sizes)">
                                    <p class="mt-1 text-xs text-gray-500">Entree ou virgule pour valider. Utilisees comme variations Etsy.</p>
                                </div>

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

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- US Price --}}
                                    <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-blue-800">🇺🇸 Etats-Unis</span>
                                            <span class="text-xs text-blue-600">Cout: {{ number_format($product->getUsTotalCost() ?? 0, 2) }} EUR</span>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-600 mb-1">Prix de vente</label>
                                            <div class="relative">
                                                <input type="number" name="price_us" id="price_us" step="0.01" min="0"
                                                       value="{{ old('price_us', number_format($product->price_us ?? 0, 2, '.', '')) }}"
                                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-12 text-sm">
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 text-xs">{{ $product->shop->currency }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        @php $usCost = $product->getUsTotalCost() ?? 0; @endphp
                                        <div class="mt-2 text-xs">
                                            <span class="text-gray-500">Profit: <span class="font-medium {{ ($product->price_us ?? 0) - $usCost >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format(($product->price_us ?? 0) - $usCost, 2) }} EUR</span></span>
                                        </div>
                                    </div>

                                    {{-- Other Countries Price --}}
                                    <div class="p-4 bg-orange-50 rounded-lg border border-orange-200">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-orange-800">🌍 Autres pays</span>
                                            <span class="text-xs text-orange-600">Cout max: {{ number_format($product->getHighestOtherCountryTotal() ?? 0, 2) }} EUR</span>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-600 mb-1">Prix de vente</label>
                                            <div class="relative">
                                                <input type="number" name="price_other" id="price_other" step="0.01" min="0"
                                                       value="{{ old('price_other', number_format($product->price_other ?? 0, 2, '.', '')) }}"
                                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 pr-12 text-sm">
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 text-xs">{{ $product->shop->currency }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        @php $otherCost = $product->getHighestOtherCountryTotal() ?? 0; @endphp
                                        <div class="mt-2 text-xs">
                                            <span class="text-gray-500">Profit: <span class="font-medium {{ ($product->price_other ?? 0) - $otherCost >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format(($product->price_other ?? 0) - $otherCost, 2) }} EUR</span></span>
                                        </div>
                                    </div>
                                </div>

                                <p class="mt-3 text-xs text-gray-500">
                                    <strong>Note:</strong> "Autres pays" utilise le cout le plus eleve parmi DE, AT, FR, CA, ES pour garantir la marge.
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

                            {{-- Etsy Profit Estimator --}}
                            @php
                                $k = \App\Models\Product::ETSY_FEE_RATE;
                                $f = \App\Models\Product::ETSY_FIXED_FEE;
                                $u = \App\Models\Product::URSSAF_RATE;
                                $shippingFee = (float) ($product->shop->shipping_fee ?? 0);
                                $initPrice = (float) $product->price;
                                $initCost = (float) ($product->cost_price ?? 0);
                                $initEtsyFees = ($initPrice + $shippingFee) * $k + $f;
                                $initRevenue = ($initPrice + $shippingFee) * (1 - $k) - $f;
                                $initUrssaf = $initRevenue * $u;
                                $initProfit = round($initRevenue - $initCost - $initUrssaf, 2);
                            @endphp
                            <div class="mt-4 p-4 bg-gray-50 rounded-lg" x-data="{
                                k: {{ $k }},
                                f: {{ $f }},
                                u: {{ $u }},
                                shipping: {{ $shippingFee }},
                                cost: {{ $initCost }},
                                profit: {{ $initProfit }},
                                etsyFees: {{ round($initEtsyFees, 2) }},
                                urssaf: {{ round($initUrssaf, 2) }},
                                recalc() {
                                    let p = parseFloat(document.getElementById('price').value) || 0;
                                    this.etsyFees = Math.round(((p + this.shipping) * this.k + this.f) * 100) / 100;
                                    let revenue = Math.round(((p + this.shipping) * (1 - this.k) - this.f) * 100) / 100;
                                    this.urssaf = Math.round((revenue * this.u) * 100) / 100;
                                    this.profit = Math.round((revenue - this.cost - this.urssaf) * 100) / 100;
                                }
                            }" x-init="
                                document.getElementById('price').addEventListener('input', () => recalc());
                            ">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">Rentabilite estimee par vente</h4>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Frais de livraison (client)</span>
                                        <span class="text-gray-700">+{{ number_format($shippingFee, 2) }} {{ $product->shop->currency }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Frais Etsy ({{ round($k * 100, 1) }}% + {{ number_format($f, 2) }})</span>
                                        <span class="text-red-600" x-text="'-' + etsyFees.toFixed(2) + ' {{ $product->shop->currency }}'"></span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Cout d'achat (AliExpress)</span>
                                        <span class="text-red-600">-{{ number_format($initCost, 2) }} {{ $product->shop->currency }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">URSSAF ({{ round($u * 100, 1) }}%)</span>
                                        <span class="text-red-600" x-text="'-' + urssaf.toFixed(2) + ' {{ $product->shop->currency }}'"></span>
                                    </div>
                                    <div class="border-t border-gray-200 pt-2 flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-700">Profit net par vente</span>
                                        <span class="text-lg font-bold"
                                              :class="profit >= 0 ? 'text-green-600' : 'text-red-600'"
                                              x-text="(profit >= 0 ? '+' : '') + profit.toFixed(2) + ' {{ $product->shop->currency }}'"></span>
                                    </div>
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

                            @if($product->source_type === 'aliexpress')
                            <div class="mb-4">
                                <label for="aliexpress_url" class="block text-sm font-medium text-gray-700">URL AliExpress</label>
                                <input type="text" name="aliexpress_url" id="aliexpress_url" value="{{ old('aliexpress_url', $product->aliexpress_url) }}"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                    placeholder="https://aliexpress.com/item/...">
                                <p class="mt-1 text-xs text-gray-500">Lien direct vers le produit AliExpress</p>
                                @error('aliexpress_url')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            @endif

                            @if($product->aliexpress_url ?? $product->source_url)
                                <a href="{{ $product->aliexpress_url ?? $product->source_url }}" target="_blank"
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
                            specificPrompts: {{ json_encode($product->shop->ai_specific_prompts ?? []) }},
                            csrfToken: '{{ csrf_token() }}',
                            applyLogo: {{ $product->apply_logo ? 'true' : 'false' }},
                            defaultBackground: '{{ $product->shop->default_ai_background ? Storage::disk("public")->url($product->shop->default_ai_background) : '' }}'
                         })">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-gray-900">Apercu</h3>
                            @if($product->cost_price > 0)
                                <span class="text-sm font-medium text-gray-500">Achat: <span class="text-red-600 font-bold">{{ number_format($product->cost_price, 2) }} {{ $product->shop->currency }}</span></span>
                            @endif
                        </div>

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
                                             class="w-full h-full object-cover cursor-pointer"
                                             @click="$dispatch('lightbox-open', { images: {{ json_encode($images) }}, index: {{ $index }} })">
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

                                {{-- Delete Image Button (always visible) --}}
                                <button type="button"
                                        @click="removeImage(currentImageIndex)"
                                        class="absolute bottom-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-2 shadow-lg z-10"
                                        title="Supprimer cette image">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
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
                        <p class="text-lg font-bold text-gray-900 mt-1">
                            {{ number_format($product->price, 2) }} {{ $product->shop->currency }}
                            @php $disc = (float)($product->shop->discount_percentage ?? 0); @endphp
                            @if($disc > 0)
                                <span class="text-sm font-normal text-gray-400 line-through ml-1">{{ number_format($product->price / (1 - $disc / 100), 2) }}</span>
                            @endif
                        </p>

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
                                            <span x-text="step === 'select' ? 'Generer des images avec l\'IA' : 'Resultat'"></span>
                                        </h3>
                                        <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Modal Body --}}
                                    <div class="p-6">
                                        {{-- Step 1: Select Images & Configure --}}
                                        <div x-show="step === 'select'">
                                            {{-- Image Selection (Multi-select) --}}
                                            <div class="mb-6">
                                                <div class="flex items-center justify-between mb-3">
                                                    <label class="block text-sm font-medium text-gray-700">Selectionnez les images:</label>
                                                    <button type="button" @click="selectAll()"
                                                            class="text-xs font-medium text-purple-600 hover:text-purple-800 transition-colors">
                                                        <span x-text="allSelected ? 'Tout deselectionner' : 'Tout selectionner'"></span>
                                                    </button>
                                                </div>
                                                <div class="grid grid-cols-4 gap-2">
                                                    <template x-for="(img, index) in images" :key="index">
                                                        <button type="button"
                                                                @click="toggleImageSelection(index)"
                                                                :class="isSelected(index) ? 'ring-2 ring-purple-500 ring-offset-2' : 'hover:opacity-75'"
                                                                class="relative aspect-square rounded-lg overflow-hidden bg-gray-100 transition-all">
                                                            <img :src="img" class="w-full h-full object-cover">
                                                            {{-- Checkbox overlay --}}
                                                            <div class="absolute top-1 right-1 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                                                                 :class="isSelected(index) ? 'bg-purple-600 border-purple-600' : 'bg-white/80 border-gray-400'">
                                                                <svg x-show="isSelected(index)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                                </svg>
                                                            </div>
                                                        </button>
                                                    </template>
                                                </div>
                                                <p class="mt-2 text-xs text-gray-500" x-show="selectedIndexes.length > 0">
                                                    <span x-text="selectedIndexes.length"></span> image(s) selectionnee(s)
                                                </p>
                                            </div>

                                            {{-- Specific Prompt Selection --}}
                                            <div class="mb-6" x-show="specificPrompts.length > 0">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Prompt specifique:</label>
                                                <select x-model="selectedSpecificPromptIndex"
                                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                                                    <option value="">Aucun</option>
                                                    <template x-for="(sp, index) in specificPrompts" :key="index">
                                                        <option :value="index" x-text="sp.name"></option>
                                                    </template>
                                                </select>
                                                <div x-show="selectedSpecificPromptIndex !== ''" class="mt-2 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                                                    <p class="text-xs font-medium text-purple-700 mb-1" x-text="specificPrompts[selectedSpecificPromptIndex]?.name"></p>
                                                    <p class="text-xs text-purple-600 whitespace-pre-line" x-text="specificPrompts[selectedSpecificPromptIndex]?.prompt"></p>
                                                </div>
                                                <p class="mt-1 text-xs text-gray-500">
                                                    <span class="text-purple-600">Le prompt specifique sera combine avec le prompt general ci-dessus.</span>
                                                </p>
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

                                            {{-- Logo Toggle --}}
                                            @if($product->shop->logo_path)
                                            <div class="mb-6 flex items-center justify-between p-3 rounded-lg border"
                                                 :class="applyLogo ? 'bg-orange-50 border-orange-200' : 'bg-gray-50 border-gray-200'">
                                                <div class="flex items-center gap-2">
                                                    <img src="{{ Storage::disk('public')->url($product->shop->logo_path) }}"
                                                         class="w-6 h-6 object-contain rounded" alt="Logo">
                                                    <span class="text-sm font-medium text-gray-700">Appliquer le logo</span>
                                                </div>
                                                <button type="button"
                                                        @click="applyLogo = !applyLogo; fetch('/products/{{ $product->id }}/toggle-logo', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }, body: JSON.stringify({ apply_logo: applyLogo }) })"
                                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                                        :class="applyLogo ? 'bg-orange-500' : 'bg-gray-300'">
                                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                                          :class="applyLogo ? 'translate-x-6' : 'translate-x-1'"></span>
                                                </button>
                                            </div>
                                            @endif

                                            {{-- Error Message --}}
                                            <div x-show="errorMessage" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                                <p class="text-sm text-red-600" x-text="errorMessage"></p>
                                            </div>
                                        </div>

                                        {{-- Step 2: Show Results --}}
                                        <div x-show="step === 'result'">
                                            <div class="grid grid-cols-3 gap-3 mb-6">
                                                <template x-for="(img, index) in generatedResults" :key="index">
                                                    <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                                                        <template x-if="img">
                                                            <img :src="img" class="w-full h-full object-cover">
                                                        </template>
                                                        <template x-if="!img">
                                                            <div class="w-full h-full flex items-center justify-center bg-red-50">
                                                                <svg class="w-8 h-8 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                                                </svg>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>

                                            <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                                <p class="text-sm text-green-700">
                                                    <span x-text="generatedResults.filter(r => r !== null).length"></span>/<span x-text="generatedResults.length"></span> image(s) generee(s) et ajoutee(s) aux "Images reelles"!
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Modal Footer --}}
                                    <div class="flex items-center justify-end gap-3 p-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                                        <template x-if="step === 'select'">
                                            <div class="flex items-center gap-3 w-full justify-between">
                                                <div class="flex items-center gap-3 ml-auto">
                                                    <button type="button"
                                                            @click="closeModal()"
                                                            :disabled="isGenerating"
                                                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 disabled:opacity-50">
                                                        Annuler
                                                    </button>
                                                    <button type="button"
                                                            @click="generateImages()"
                                                            :disabled="isGenerating || !prompt.trim() || selectedIndexes.length === 0"
                                                            class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                                        <svg x-show="isGenerating" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        <svg x-show="!isGenerating" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                                        </svg>
                                                        <span x-text="isGenerating ? 'Lancement...' : (selectedIndexes.length > 1 ? 'Generer ' + selectedIndexes.length + ' images' : 'Generer l\'image')"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="step === 'result'">
                                            <div class="flex items-center gap-3 w-full justify-end">
                                                <button type="button"
                                                        @click="step = 'select'; generatedResults = []; selectedIndexes = [];"
                                                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                                                    Generer d'autres
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

                    {{-- AI Generation Progress Banner --}}
                    <div x-data="aiBatchProgress({ productId: {{ $product->id }}, csrfToken: '{{ csrf_token() }}' })"
                         x-show="batchId"
                         x-cloak
                         @ai-batch-started.window="startPolling($event.detail)"
                         class="bg-purple-50 rounded-lg shadow-sm border border-purple-300 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold text-purple-700 flex items-center">
                                <svg class="animate-spin w-4 h-4 mr-2 text-purple-600" x-show="!finished" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg class="w-4 h-4 mr-2 text-green-600" x-show="finished" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span x-text="finished ? 'Generation terminee!' : 'Generation IA en cours...'"></span>
                            </h3>
                            <span class="text-xs text-purple-600" x-text="processed + '/' + total + (failed > 0 ? ' (' + failed + ' echec)' : '')"></span>
                        </div>
                        <div class="w-full bg-purple-200 rounded-full h-2">
                            <div class="bg-purple-600 h-2 rounded-full transition-all duration-500"
                                 :style="'width: ' + progress + '%'"></div>
                        </div>
                        <button x-show="finished" @click="dismiss()" class="mt-2 text-xs text-purple-600 hover:text-purple-800 underline">Fermer</button>
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
                                        <img :src="img" class="w-full h-full object-cover cursor-pointer"
                                             @click="$dispatch('lightbox-open', { images: images, index: index })">
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


                    {{-- Info --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">Informations</h3>

                        <div class="space-y-3">
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
                specificPrompts: config.specificPrompts || [],
                csrfToken: config.csrfToken,
                applyLogo: config.applyLogo ?? false,
                defaultBackground: config.defaultBackground || '',

                // State
                showModal: false,
                step: 'select',
                currentImageIndex: 0,
                selectedIndexes: [],
                prompt: config.defaultPrompt || '',
                selectedSpecificPromptIndex: '',
                selectedBackground: config.defaultBackground || '',
                isGenerating: false,
                generatedResults: [],
                errorMessage: null,

                openModal() {
                    this.showModal = true;
                    this.step = 'select';
                    this.selectedIndexes = [];
                    this.prompt = this.defaultPrompt || '';
                    this.selectedSpecificPromptIndex = '';
                    this.selectedBackground = this.defaultBackground;
                    this.generatedResults = [];
                    this.errorMessage = null;
                    document.body.classList.add('overflow-hidden');
                },

                closeModal() {
                    this.showModal = false;
                    document.body.classList.remove('overflow-hidden');
                },

                toggleImageSelection(index) {
                    const pos = this.selectedIndexes.indexOf(index);
                    if (pos === -1) {
                        this.selectedIndexes.push(index);
                    } else {
                        this.selectedIndexes.splice(pos, 1);
                    }
                },

                isSelected(index) {
                    return this.selectedIndexes.includes(index);
                },

                selectAll() {
                    if (this.selectedIndexes.length === this.images.length) {
                        this.selectedIndexes = [];
                    } else {
                        this.selectedIndexes = this.images.map((_, i) => i);
                    }
                },

                get allSelected() {
                    return this.selectedIndexes.length === this.images.length && this.images.length > 0;
                },

                async generateImages() {
                    if (!this.prompt.trim()) {
                        this.errorMessage = 'Veuillez entrer un prompt de transformation.';
                        return;
                    }
                    if (this.selectedIndexes.length === 0) {
                        this.errorMessage = 'Veuillez selectionner au moins une image.';
                        return;
                    }

                    this.isGenerating = true;
                    this.errorMessage = null;

                    // Combine general prompt + specific prompt
                    let finalPrompt = this.prompt;
                    if (this.selectedSpecificPromptIndex !== '' && this.specificPrompts[this.selectedSpecificPromptIndex]) {
                        finalPrompt = this.prompt + "\n\n" + this.specificPrompts[this.selectedSpecificPromptIndex].prompt;
                    }

                    // Collect image URLs for the selected images
                    const imageUrls = this.selectedIndexes.map(idx => this.images[idx]);

                    try {
                        const response = await fetch(`/products/${this.productId}/dispatch-ai-generation`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                image_urls: imageUrls,
                                prompt: finalPrompt,
                                background_url: this.selectedBackground || null,
                                apply_logo: this.applyLogo,
                            }),
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Update default background client-side
                            this.defaultBackground = this.selectedBackground;

                            // Dispatch event to start the progress banner polling
                            window.dispatchEvent(new CustomEvent('ai-batch-started', {
                                detail: { batchId: data.batch_id, total: data.total }
                            }));

                            // Close modal immediately — work continues in background
                            this.closeModal();
                        } else {
                            this.errorMessage = data.message || 'Erreur lors du lancement de la generation.';
                        }
                    } catch (error) {
                        console.error('Error dispatching AI generation:', error);
                        this.errorMessage = 'Erreur de connexion.';
                    }

                    this.isGenerating = false;
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
                },

                async removeImage(index) {
                    if (!confirm('Supprimer cette image ?')) return;

                    try {
                        const response = await fetch(`/products/${this.productId}/remove-image`, {
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
                            window.location.reload();
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

        // AI Batch Progress component — polls for batch job status
        function aiBatchProgress(config) {
            return {
                productId: config.productId,
                csrfToken: config.csrfToken,
                batchId: null,
                total: 0,
                processed: 0,
                failed: 0,
                progress: 0,
                finished: false,
                pollInterval: null,

                startPolling(detail) {
                    this.batchId = detail.batchId;
                    this.total = detail.total;
                    this.processed = 0;
                    this.failed = 0;
                    this.progress = 0;
                    this.finished = false;

                    // Poll every 2 seconds
                    this.pollInterval = setInterval(() => this.checkStatus(), 2000);
                    // Also check immediately
                    this.checkStatus();
                },

                async checkStatus() {
                    if (!this.batchId) return;

                    try {
                        const response = await fetch(`/products/${this.productId}/ai-generation-status?batch_id=${this.batchId}`, {
                            headers: { 'Accept': 'application/json' },
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.total = data.total;
                            this.processed = data.processed;
                            this.failed = data.failed;
                            this.progress = data.progress;

                            // Update real images in the realImagesManager component
                            window.dispatchEvent(new CustomEvent('real-images-updated', {
                                detail: { images: data.real_images }
                            }));

                            if (data.finished) {
                                this.finished = true;
                                clearInterval(this.pollInterval);
                                this.pollInterval = null;
                            }
                        }
                    } catch (error) {
                        console.error('Error checking batch status:', error);
                    }
                },

                dismiss() {
                    this.batchId = null;
                    if (this.pollInterval) {
                        clearInterval(this.pollInterval);
                        this.pollInterval = null;
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

    {{-- Image Lightbox Modal --}}
    <div x-data="{
            images: [],
            currentIndex: 0,
            get isOpen() { return this.images.length > 0; },
            get currentImage() { return this.images[this.currentIndex] || null; },
            get hasMultiple() { return this.images.length > 1; },
            open(detail) {
                this.images = detail.images || [detail];
                this.currentIndex = detail.index || 0;
                document.body.classList.add('overflow-hidden');
            },
            close() {
                this.images = [];
                this.currentIndex = 0;
                document.body.classList.remove('overflow-hidden');
            },
            prev() {
                this.currentIndex = this.currentIndex > 0 ? this.currentIndex - 1 : this.images.length - 1;
            },
            next() {
                this.currentIndex = this.currentIndex < this.images.length - 1 ? this.currentIndex + 1 : 0;
            }
         }"
         x-cloak
         @lightbox-open.window="open($event.detail)"
         @keydown.escape.window="close()"
         @keydown.left.window="if (isOpen) prev()"
         @keydown.right.window="if (isOpen) next()">
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[60] flex items-center justify-center p-4"
             @click.self="close()">
            <div class="fixed inset-0 bg-black/80"></div>
            <img :src="currentImage"
                 class="relative z-10 max-w-[90vw] max-h-[90vh] object-contain rounded-lg shadow-2xl"
                 @click.stop>

            {{-- Navigation arrows --}}
            <template x-if="hasMultiple">
                <button type="button" @click.stop="prev()"
                        class="absolute left-4 z-20 bg-white/10 hover:bg-white/20 text-white rounded-full p-3 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
            </template>
            <template x-if="hasMultiple">
                <button type="button" @click.stop="next()"
                        class="absolute right-4 z-20 bg-white/10 hover:bg-white/20 text-white rounded-full p-3 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </template>

            {{-- Counter --}}
            <div x-show="hasMultiple" class="absolute bottom-4 z-20 bg-black/60 text-white px-3 py-1 rounded-full text-sm">
                <span x-text="(currentIndex + 1) + ' / ' + images.length"></span>
            </div>

            {{-- Close button --}}
            <button type="button" @click="close()"
                    class="absolute top-4 right-4 z-20 bg-white/10 hover:bg-white/20 text-white rounded-full p-2 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
</x-app-layout>
