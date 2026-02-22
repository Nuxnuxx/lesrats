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
                        {{ $product->title }}
                    </h2>
                    <p class="text-sm text-gray-500">{{ $product->shop->name }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <span id="autosave-status" class="text-xs text-gray-400 hidden">Sauvegarde...</span>
                <x-ui.source-badge :type="$product->source_type ?? 'manual'" />
            </div>
        </div>
    </x-slot>

    @php
        $images = is_array($product->images) ? $product->images : json_decode($product->images, true) ?? [];
        $tagsArray = is_array($product->tags) ? $product->tags : (is_string($product->tags) ? json_decode($product->tags, true) : []);
        $tagsString = is_array($tagsArray) ? implode(', ', $tagsArray) : '';
        $shopCategories = $product->shop->etsy_categories ?? [];
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
        $etsyCategory = $product->etsy_category;
        $etsyCategoryData = null;
        if ($etsyCategory && !empty($shopCategories)) {
            foreach ($shopCategories as $cat) {
                if ($cat['name'] === $etsyCategory) {
                    $etsyCategoryData = $cat;
                    break;
                }
            }
        }
    @endphp

    <div class="py-6" x-data="productAutoSave({
        productId: {{ $product->id }},
        csrfToken: '{{ csrf_token() }}'
    })">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- 3-column grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                {{-- Column 1: Images (Source + AI + Generate) --}}
                <div class="lg:col-span-4 space-y-4">

                    {{-- Source Images with AI Editor --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"
                         x-data="aiImageEditor({
                            images: {{ json_encode($images) }},
                            realImages: {{ json_encode($product->real_image_urls) }},
                            productId: {{ $product->id }},
                            defaultPrompt: {{ json_encode($product->shop->getEffectiveAiImagePrompt()) }},
                            specificPrompts: {{ json_encode($product->shop->ai_specific_prompts ?? []) }},
                            csrfToken: '{{ csrf_token() }}',
                            applyLogo: {{ $product->apply_logo ? 'true' : 'false' }},
                            defaultBackground: '{{ $product->shop->default_ai_background ? Storage::disk("public")->url($product->shop->default_ai_background) : '' }}'
                         })">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-900">Images source</h3>
                            @if($product->cost_price > 0)
                                <span class="text-xs text-gray-500">Achat: <span class="text-red-600 font-bold">{{ number_format($product->cost_price, 2) }} {{ $product->shop->currency }}</span></span>
                            @endif
                        </div>

                        @if(count($images) > 0)
                            <div class="grid grid-cols-3 gap-2 mb-3">
                                @foreach($images as $index => $img)
                                    <div class="relative group aspect-square rounded-lg overflow-hidden bg-gray-100">
                                        <img src="{{ $img }}" class="w-full h-full object-cover cursor-pointer"
                                             @click="$dispatch('lightbox-open', { images: images, index: {{ $index }} })">
                                        <button type="button"
                                                @click="removeImage({{ $index }})"
                                                class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4 text-gray-400 text-sm">Aucune image source</div>
                        @endif

                        {{-- Generate AI button --}}
                        @if(count($images) > 0)
                            <button type="button" @click="openModal()"
                                    class="w-full inline-flex items-center justify-center px-3 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                                Generer images IA
                            </button>
                        @endif

                        {{-- AI Image Generation Modal --}}
                        <div x-show="showModal"
                             x-cloak
                             class="fixed inset-0 z-50 overflow-y-auto"
                             x-transition:enter="ease-out duration-300"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="ease-in duration-200"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0">
                            <div class="fixed inset-0 bg-gray-500/75" @click="closeModal()"></div>
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl"
                                     @click.away="closeModal()">

                                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900">Generer des images IA</h3>
                                        <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="p-6 space-y-4">
                                        {{-- Image Selection --}}
                                        <div>
                                            <div class="flex items-center justify-between mb-2">
                                                <label class="text-sm font-medium text-gray-700">Selectionnez les images:</label>
                                                <button type="button" @click="selectAll()" class="text-xs font-medium text-purple-600 hover:text-purple-800">
                                                    <span x-text="allSelected ? 'Tout deselectionner' : 'Tout selectionner'"></span>
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-4 gap-2">
                                                <template x-for="(img, index) in images" :key="index">
                                                    <button type="button" @click="toggleImageSelection(index)"
                                                            :class="isSelected(index) ? 'ring-2 ring-purple-500 ring-offset-2' : 'hover:opacity-75'"
                                                            class="relative aspect-square rounded-lg overflow-hidden bg-gray-100 transition-all">
                                                        <img :src="img" class="w-full h-full object-cover">
                                                        <div class="absolute top-1 right-1 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                                                             :class="isSelected(index) ? 'bg-purple-600 border-purple-600' : 'bg-white/80 border-gray-400'">
                                                            <svg x-show="isSelected(index)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                        </div>
                                                    </button>
                                                </template>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-500" x-show="selectedIndexes.length > 0">
                                                <span x-text="selectedIndexes.length"></span> image(s) selectionnee(s)
                                            </p>
                                        </div>

                                        {{-- Specific Prompt --}}
                                        <div x-show="specificPrompts.length > 0">
                                            <label class="text-sm font-medium text-gray-700 mb-1 block">Prompt specifique:</label>
                                            <select x-model="selectedSpecificPromptIndex"
                                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                                                <option value="">Aucun</option>
                                                <template x-for="(sp, index) in specificPrompts" :key="index">
                                                    <option :value="index" x-text="sp.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        {{-- Background --}}
                                        <div>
                                            <label class="text-sm font-medium text-gray-700 mb-1 block">Background:</label>
                                            <select x-model="selectedBackground"
                                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                                                <option value="">Aucun</option>
                                                @foreach($backgrounds as $index => $bg)
                                                    <option value="{{ Storage::disk('public')->url($bg['path']) }}">{{ $bg['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Logo Toggle --}}
                                        @if($product->shop->logo_path)
                                        <div class="flex items-center justify-between p-3 rounded-lg border"
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

                                        {{-- Error --}}
                                        <div x-show="errorMessage" class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                            <p class="text-sm text-red-600" x-text="errorMessage"></p>
                                        </div>
                                    </div>

                                    {{-- Footer --}}
                                    <div class="flex items-center justify-end gap-3 p-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                                        <button type="button" @click="closeModal()" :disabled="isGenerating"
                                                class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 disabled:opacity-50">
                                            Annuler
                                        </button>
                                        <button type="button" @click="generateImages()"
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
                            </div>
                        </div>
                    </div>

                    {{-- AI Generation Progress --}}
                    <div x-data="aiBatchProgress({ productId: {{ $product->id }}, csrfToken: '{{ csrf_token() }}' })"
                         x-show="batchId" x-cloak
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
                            <div class="bg-purple-600 h-2 rounded-full transition-all duration-500" :style="'width: ' + progress + '%'"></div>
                        </div>
                        <button x-show="finished" @click="dismiss()" class="mt-2 text-xs text-purple-600 hover:text-purple-800 underline">Fermer</button>
                    </div>

                    {{-- Real Images (AI Generated) --}}
                    <div class="bg-white rounded-lg shadow-sm border border-purple-200 p-4"
                         x-data="realImagesManager({
                            images: {{ json_encode($product->real_image_urls) }},
                            productId: {{ $product->id }},
                            csrfToken: '{{ csrf_token() }}'
                         })">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-purple-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                                Images reelles (IA)
                            </h3>
                            <span class="text-xs text-gray-500" x-text="images.length + ' image(s)'"></span>
                        </div>

                        <template x-if="images.length === 0">
                            <p class="text-sm text-gray-400 text-center py-4">Aucune image generee</p>
                        </template>

                        <template x-if="images.length > 0">
                            <div class="grid grid-cols-3 gap-2">
                                <template x-for="(img, index) in images" :key="index">
                                    <div class="relative group aspect-square rounded-lg overflow-hidden bg-gray-100">
                                        <img :src="img" class="w-full h-full object-cover cursor-pointer"
                                             @click="$dispatch('lightbox-open', { images: images, index: index })">
                                        <button type="button" @click="removeImage(index)"
                                                class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <p class="text-xs text-gray-400 mt-2">Ces images seront envoyees a Etsy.</p>
                    </div>

                    {{-- Download --}}
                    <a href="{{ route('products.download-images', $product) }}"
                       class="w-full inline-flex items-center justify-center px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Telecharger les images
                    </a>
                </div>

                {{-- Column 2: Product Info (auto-saved) --}}
                <div class="lg:col-span-5 space-y-4">

                    {{-- Title --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <div class="space-y-4">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="title" class="text-sm font-medium text-gray-700">Titre</label>
                                    <button type="button" onclick="copyToClipboard(document.getElementById('title').value)"
                                            class="text-gray-400 hover:text-blue-600 transition-colors" title="Copier">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <input type="text" name="title" id="title" value="{{ old('title', $product->title) }}"
                                       @input.debounce.800ms="save({ title: $el.value })"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="description" class="text-sm font-medium text-gray-700">Description</label>
                                    <button type="button" onclick="copyToClipboard(document.getElementById('description').value)"
                                            class="text-gray-400 hover:text-blue-600 transition-colors" title="Copier">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <textarea name="description" id="description" rows="4"
                                          @input.debounce.800ms="save({ description: $el.value })"
                                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">{{ old('description', $product->description) }}</textarea>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="tags" class="text-sm font-medium text-gray-700">Tags (13 max)</label>
                                    <button type="button" onclick="copyToClipboard(document.getElementById('tags').value)"
                                            class="text-gray-400 hover:text-blue-600 transition-colors" title="Copier">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012-2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <input type="text" name="tags" id="tags" value="{{ old('tags', $tagsString) }}"
                                       @input.debounce.800ms="save({ tags: $el.value })"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm"
                                       placeholder="tag1, tag2, tag3...">
                                <p class="mt-1 text-xs text-gray-500">Separes par des virgules, 20 car max/tag</p>
                            </div>

                            {{-- Etsy Category --}}
                            @if(count($shopCategories) > 0)
                            <div>
                                <label for="etsy_category" class="text-sm font-medium text-gray-700 mb-1 block">Categorie Etsy</label>
                                <select name="etsy_category" id="etsy_category"
                                        @change="save({ etsy_category: $el.value })"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                                    <option value="">-- Categorie --</option>
                                    @foreach($shopCategories as $cat)
                                        <option value="{{ $cat['name'] }}" {{ old('etsy_category', $product->etsy_category) == $cat['name'] ? 'selected' : '' }}>
                                            {{ $cat['name'] }} ({{ $cat['etsy_name'] }})
                                        </option>
                                    @endforeach
                                </select>
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
                                        $dispatch('autosave-sizes', { sizes: JSON.stringify(this.sizes) });
                                    }
                                    this.newSize = '';
                                },
                                removeSize(index) {
                                    this.sizes.splice(index, 1);
                                    $dispatch('autosave-sizes', { sizes: JSON.stringify(this.sizes) });
                                }
                            }" @autosave-sizes.window="save({ sizes: $event.detail.sizes })">
                                <label class="text-sm font-medium text-gray-700 mb-1 block">Tailles</label>
                                <div class="flex flex-wrap gap-1 mb-2 min-h-[32px] p-2 border border-gray-200 rounded-lg bg-gray-50">
                                    <span x-show="sizes.length === 0" class="text-xs text-gray-400 italic">Aucune</span>
                                    <template x-for="(size, index) in sizes" :key="index">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <span x-text="size"></span>
                                            <button type="button" @click="removeSize(index)" class="ml-1 text-blue-600 hover:text-blue-800">
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
                                           placeholder="S, M, L, XL...">
                                    <button type="button" @click="addSize()"
                                            class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-sm font-medium hover:bg-blue-200">+</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Country-specific Pricing (AliExpress only) --}}
                    @if($product->country_prices && count($product->country_prices) > 0)
                    <div class="bg-white rounded-lg shadow-sm border border-blue-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Prix par pays (AliExpress)</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-1 px-2 font-medium text-gray-600">Pays</th>
                                        <th class="text-right py-1 px-2 font-medium text-gray-600">Prix</th>
                                        <th class="text-right py-1 px-2 font-medium text-gray-600">Livr.</th>
                                        <th class="text-right py-1 px-2 font-medium text-gray-600">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $countryNames = ['US' => 'USA', 'DE' => 'DE', 'AT' => 'AT', 'FR' => 'FR', 'CA' => 'CA', 'ES' => 'ES'];
                                        $countryFlags = ['US' => "\xF0\x9F\x87\xBA\xF0\x9F\x87\xB8", 'DE' => "\xF0\x9F\x87\xA9\xF0\x9F\x87\xAA", 'AT' => "\xF0\x9F\x87\xA6\xF0\x9F\x87\xB9", 'FR' => "\xF0\x9F\x87\xAB\xF0\x9F\x87\xB7", 'CA' => "\xF0\x9F\x87\xA8\xF0\x9F\x87\xA6", 'ES' => "\xF0\x9F\x87\xAA\xF0\x9F\x87\xB8"];
                                        $highestOther = $product->getHighestOtherCountry();
                                    @endphp
                                    @foreach($product->country_prices as $code => $data)
                                        <tr class="border-b border-gray-100 {{ $code === 'US' ? 'bg-blue-50' : ($code === $highestOther ? 'bg-orange-50' : '') }}">
                                            <td class="py-1 px-2">{{ $countryFlags[$code] ?? '' }} {{ $countryNames[$code] ?? $code }}</td>
                                            <td class="text-right py-1 px-2">{{ number_format($data['price'] ?? 0, 2) }}</td>
                                            <td class="text-right py-1 px-2">{{ number_format($data['shipping'] ?? 0, 2) }}</td>
                                            <td class="text-right py-1 px-2 font-medium">{{ number_format($data['total'] ?? 0, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 pt-3 border-t border-gray-200 grid grid-cols-2 gap-3">
                            <div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                <span class="text-xs font-medium text-blue-800 block mb-1">US</span>
                                <input type="number" name="price_us" id="price_us" step="0.01" min="0"
                                       value="{{ old('price_us', number_format($product->price_us ?? 0, 2, '.', '')) }}"
                                       @input.debounce.800ms="save({ price_us: $el.value })"
                                       class="block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                @php $usCost = $product->getUsTotalCost() ?? 0; @endphp
                                <span class="text-xs text-gray-500 mt-1 block">Cout: {{ number_format($usCost, 2) }} | Profit: <span class="{{ ($product->price_us ?? 0) - $usCost >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format(($product->price_us ?? 0) - $usCost, 2) }}</span></span>
                            </div>
                            <div class="p-3 bg-orange-50 rounded-lg border border-orange-200">
                                <span class="text-xs font-medium text-orange-800 block mb-1">Autres pays</span>
                                <input type="number" name="price_other" id="price_other" step="0.01" min="0"
                                       value="{{ old('price_other', number_format($product->price_other ?? 0, 2, '.', '')) }}"
                                       @input.debounce.800ms="save({ price_other: $el.value })"
                                       class="block w-full rounded border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                                @php $otherCost = $product->getHighestOtherCountryTotal() ?? 0; @endphp
                                <span class="text-xs text-gray-500 mt-1 block">Cout max: {{ number_format($otherCost, 2) }} | Profit: <span class="{{ ($product->price_other ?? 0) - $otherCost >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format(($product->price_other ?? 0) - $otherCost, 2) }}</span></span>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Source Info --}}
                    @if($product->source_type === 'aliexpress' || $product->aliexpress_url || $product->source_url)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <x-ui.source-badge :type="$product->source_type ?? 'manual'" size="md" />
                                @if($product->is_digital)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Digital</span>
                                @endif
                            </div>
                            @if($product->aliexpress_url ?? $product->source_url)
                                <a href="{{ $product->aliexpress_url ?? $product->source_url }}" target="_blank"
                                   class="text-xs text-orange-600 hover:text-orange-700 underline">Ouvrir source</a>
                            @endif
                        </div>
                        @if($product->source_type === 'aliexpress')
                        <input type="text" name="aliexpress_url" value="{{ old('aliexpress_url', $product->aliexpress_url) }}"
                               @input.debounce.800ms="save({ aliexpress_url: $el.value })"
                               class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm"
                               placeholder="https://aliexpress.com/item/...">
                        @endif
                    </div>
                    @endif
                </div>

                {{-- Column 3: Pricing, Etsy Publish, Actions --}}
                <div class="lg:col-span-3 space-y-4">

                    {{-- Price & Profit --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Prix & Rentabilite</h3>

                        <div>
                            <label for="price" class="text-sm font-medium text-gray-700">Prix de vente</label>
                            <div class="mt-1 relative">
                                <input type="number" name="price" id="price" step="0.01" min="0"
                                       value="{{ old('price', number_format($product->price, 2, '.', '')) }}"
                                       @input.debounce.800ms="save({ price: $el.value })"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 pr-12 text-sm">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-xs">{{ $product->shop->currency }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Profit Breakdown --}}
                        <div class="mt-3 p-3 bg-gray-50 rounded-lg text-xs" x-data="{
                            k: {{ $k }}, f: {{ $f }}, u: {{ $u }},
                            shipping: {{ $shippingFee }}, cost: {{ $initCost }},
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
                        }" x-init="document.getElementById('price').addEventListener('input', () => recalc())">
                            <div class="space-y-1.5">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Livraison</span>
                                    <span>+{{ number_format($shippingFee, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Etsy ({{ round($k * 100, 1) }}%+{{ number_format($f, 2) }})</span>
                                    <span class="text-red-600" x-text="'-' + etsyFees.toFixed(2)"></span>
                                </div>
                                @if($initCost > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Cout achat</span>
                                    <span class="text-red-600">-{{ number_format($initCost, 2) }}</span>
                                </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-gray-500">URSSAF ({{ round($u * 100, 1) }}%)</span>
                                    <span class="text-red-600" x-text="'-' + urssaf.toFixed(2)"></span>
                                </div>
                                <div class="border-t border-gray-200 pt-1.5 flex justify-between">
                                    <span class="font-medium text-gray-700">Profit net</span>
                                    <span class="font-bold text-sm"
                                          :class="profit >= 0 ? 'text-green-600' : 'text-red-600'"
                                          x-text="(profit >= 0 ? '+' : '') + profit.toFixed(2)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Stock --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4" x-data="{
                        isUnlimited: {{ ($product->quantity ?? 999) >= 999 || ($product->is_digital ?? false) ? 'true' : 'false' }},
                        stockQty: {{ $product->quantity ?? 10 }},
                        isDigital: {{ ($product->is_digital ?? false) ? 'true' : 'false' }}
                    }">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Stock</h3>
                        <div class="flex items-center gap-3">
                            <input type="number" name="quantity" x-model="stockQty" :disabled="isUnlimited" min="0"
                                   @input.debounce.800ms="save({ quantity: $el.value })"
                                   class="w-20 rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm disabled:bg-gray-100">
                            <label class="flex items-center text-sm" x-show="!isDigital">
                                <input type="checkbox" x-model="isUnlimited"
                                       @change="if(isUnlimited) { stockQty = 999; save({ quantity: 999 }); }"
                                       class="w-4 h-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="ml-2 text-gray-600">Illimite</span>
                            </label>
                        </div>
                        <p x-show="isDigital" class="mt-1 text-xs text-green-600">Digital = stock illimite</p>
                    </div>

                    {{-- Publish to Etsy --}}
                    <div class="bg-white rounded-lg shadow-sm border border-orange-200 p-4">
                        <h3 class="text-sm font-semibold text-orange-600 mb-3">Publier sur Etsy</h3>
                        <button type="button"
                                data-product-id="{{ $product->id }}"
                                data-category-name="{{ $etsyCategoryData['etsy_name'] ?? '' }}"
                                data-is-digital="{{ $product->is_digital ? 'true' : 'false' }}"
                                onclick="publishToEtsy(this.dataset)"
                                class="w-full inline-flex items-center justify-center px-3 py-2 bg-orange-100 text-orange-700 rounded-lg text-sm font-medium hover:bg-orange-200 transition-colors"
                                {{ !$etsyCategoryData ? 'disabled' : '' }}>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Ouvrir Etsy & Remplir
                        </button>
                        @if(!$etsyCategoryData)
                            <p class="text-xs text-red-500 mt-2">Selectionnez une categorie Etsy.</p>
                        @else
                            <p class="text-xs text-green-600 mt-2">{{ $etsyCategoryData['etsy_name'] }}</p>
                        @endif
                    </div>

                    {{-- Info + Delete --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <div class="flex justify-between text-xs text-gray-500 mb-3">
                            <span>Cree le {{ $product->created_at->format('d/m/Y') }}</span>
                        </div>
                        <x-ui.confirm-modal
                            id="delete-product"
                            title="Supprimer ce produit"
                            message="Cette action est irreversible."
                            confirmLabel="Supprimer"
                            type="danger"
                            :formAction="route('products.destroy', $product)"
                            formMethod="DELETE"
                        >
                            <x-slot name="trigger">
                                <button type="button"
                                        class="w-full inline-flex items-center justify-center px-3 py-2 border border-red-300 text-red-600 rounded-lg text-xs font-medium hover:bg-red-50 transition-colors">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Supprimer
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
        // Auto-save component
        function productAutoSave(config) {
            return {
                productId: config.productId,
                csrfToken: config.csrfToken,
                saveTimeout: null,

                async save(data) {
                    const status = document.getElementById('autosave-status');
                    status.classList.remove('hidden');
                    status.textContent = 'Sauvegarde...';
                    status.className = 'text-xs text-gray-400';

                    try {
                        const response = await fetch(`/products/${this.productId}/autosave`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(data),
                        });

                        const result = await response.json();

                        if (result.success) {
                            status.textContent = 'Sauvegarde!';
                            status.className = 'text-xs text-green-500';
                        } else {
                            status.textContent = 'Erreur';
                            status.className = 'text-xs text-red-500';
                        }
                    } catch (error) {
                        status.textContent = 'Erreur';
                        status.className = 'text-xs text-red-500';
                        console.error('Autosave error:', error);
                    }

                    setTimeout(() => status.classList.add('hidden'), 2000);
                }
            };
        }

        // AI Image Editor
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

                showModal: false,
                selectedIndexes: [],
                prompt: config.defaultPrompt || '',
                selectedSpecificPromptIndex: '',
                selectedBackground: config.defaultBackground || '',
                isGenerating: false,
                errorMessage: null,

                openModal() {
                    this.showModal = true;
                    this.selectedIndexes = [];
                    this.prompt = this.defaultPrompt || '';
                    this.selectedSpecificPromptIndex = '';
                    this.selectedBackground = this.defaultBackground;
                    this.errorMessage = null;
                    document.body.classList.add('overflow-hidden');
                },

                closeModal() {
                    this.showModal = false;
                    document.body.classList.remove('overflow-hidden');
                },

                toggleImageSelection(index) {
                    const pos = this.selectedIndexes.indexOf(index);
                    if (pos === -1) { this.selectedIndexes.push(index); }
                    else { this.selectedIndexes.splice(pos, 1); }
                },

                isSelected(index) { return this.selectedIndexes.includes(index); },

                selectAll() {
                    if (this.selectedIndexes.length === this.images.length) { this.selectedIndexes = []; }
                    else { this.selectedIndexes = this.images.map((_, i) => i); }
                },

                get allSelected() { return this.selectedIndexes.length === this.images.length && this.images.length > 0; },

                async generateImages() {
                    if (!this.prompt.trim()) { this.errorMessage = 'Veuillez entrer un prompt.'; return; }
                    if (this.selectedIndexes.length === 0) { this.errorMessage = 'Selectionnez au moins une image.'; return; }

                    this.isGenerating = true;
                    this.errorMessage = null;

                    let finalPrompt = this.prompt;
                    if (this.selectedSpecificPromptIndex !== '' && this.specificPrompts[this.selectedSpecificPromptIndex]) {
                        finalPrompt = this.prompt + "\n\n" + this.specificPrompts[this.selectedSpecificPromptIndex].prompt;
                    }

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
                            this.defaultBackground = this.selectedBackground;
                            window.dispatchEvent(new CustomEvent('ai-batch-started', {
                                detail: { batchId: data.batch_id, total: data.total }
                            }));
                            this.closeModal();
                        } else {
                            this.errorMessage = data.message || 'Erreur.';
                        }
                    } catch (error) {
                        this.errorMessage = 'Erreur de connexion.';
                    }
                    this.isGenerating = false;
                },

                async removeRealImage(index) {
                    if (!confirm('Supprimer cette image?')) return;
                    try {
                        const response = await fetch(`/products/${this.productId}/remove-real-image`, {
                            method: 'DELETE',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                            body: JSON.stringify({ image_index: index }),
                        });
                        const data = await response.json();
                        if (data.success) { this.realImages = data.data.real_images; }
                        else { alert(data.message || 'Erreur.'); }
                    } catch (error) { alert('Erreur de connexion.'); }
                },

                async removeImage(index) {
                    if (!confirm('Supprimer cette image ?')) return;
                    try {
                        const response = await fetch(`/products/${this.productId}/remove-image`, {
                            method: 'DELETE',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                            body: JSON.stringify({ image_index: index }),
                        });
                        const data = await response.json();
                        if (data.success) { window.location.reload(); }
                        else { alert(data.message || 'Erreur.'); }
                    } catch (error) { alert('Erreur de connexion.'); }
                }
            };
        }

        // Real Images Manager
        function realImagesManager(config) {
            return {
                images: config.images || [],
                productId: config.productId,
                csrfToken: config.csrfToken,

                init() {
                    window.addEventListener('real-images-updated', (event) => {
                        this.images = event.detail.images;
                    });
                },

                async removeImage(index) {
                    if (!confirm('Supprimer cette image?')) return;
                    try {
                        const response = await fetch(`/products/${this.productId}/remove-real-image`, {
                            method: 'DELETE',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                            body: JSON.stringify({ image_index: index }),
                        });
                        const data = await response.json();
                        if (data.success) { this.images = data.data.real_images; }
                        else { alert(data.message || 'Erreur.'); }
                    } catch (error) { alert('Erreur de connexion.'); }
                }
            };
        }

        // AI Batch Progress
        function aiBatchProgress(config) {
            return {
                productId: config.productId,
                csrfToken: config.csrfToken,
                batchId: null, total: 0, processed: 0, failed: 0, progress: 0, finished: false, pollInterval: null,

                startPolling(detail) {
                    this.batchId = detail.batchId;
                    this.total = detail.total;
                    this.processed = 0; this.failed = 0; this.progress = 0; this.finished = false;
                    this.pollInterval = setInterval(() => this.checkStatus(), 2000);
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
                            window.dispatchEvent(new CustomEvent('real-images-updated', { detail: { images: data.real_images } }));
                            if (data.finished) {
                                this.finished = true;
                                clearInterval(this.pollInterval);
                                this.pollInterval = null;
                            }
                        }
                    } catch (error) { console.error('Batch status error:', error); }
                },

                dismiss() {
                    this.batchId = null;
                    if (this.pollInterval) { clearInterval(this.pollInterval); this.pollInterval = null; }
                }
            };
        }

        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                const toast = document.createElement('div');
                toast.textContent = 'Copie!';
                toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 2000);
            }).catch(err => alert('Erreur: ' + err));
        }

        // Publish to Etsy
        function publishToEtsy(dataset) {
            const categoryName = dataset.categoryName;
            const isDigital = dataset.isDigital === 'true';
            const productId = dataset.productId;

            if (!categoryName) { alert('Selectionnez une categorie Etsy.'); return; }

            const toast = document.createElement('div');
            toast.textContent = 'Preparation...';
            toast.className = 'fixed bottom-4 right-4 bg-orange-500 text-white px-4 py-2 rounded shadow-lg z-50';
            document.body.appendChild(toast);

            const productData = {
                categoryName, isDigital, productId,
                apiUrl: window.location.origin,
                timestamp: Date.now()
            };

            window.postMessage({ type: 'LESRATS_PUBLISH_TO_ETSY', ...productData }, '*');
            localStorage.setItem('lesrats_pending_etsy', JSON.stringify(productData));

            setTimeout(() => {
                toast.textContent = 'Ouverture Etsy...';
                toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50';
                window.open('https://www.etsy.com/your/shops/me/listing-editor/create', '_blank');
                setTimeout(() => toast.remove(), 2000);
            }, 200);
        }
    </script>
    @endpush

    {{-- Image Lightbox --}}
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
            close() { this.images = []; this.currentIndex = 0; document.body.classList.remove('overflow-hidden'); },
            prev() { this.currentIndex = this.currentIndex > 0 ? this.currentIndex - 1 : this.images.length - 1; },
            next() { this.currentIndex = this.currentIndex < this.images.length - 1 ? this.currentIndex + 1 : 0; }
         }"
         x-cloak
         @lightbox-open.window="open($event.detail)"
         @keydown.escape.window="close()"
         @keydown.left.window="if (isOpen) prev()"
         @keydown.right.window="if (isOpen) next()">
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[60] flex items-center justify-center p-4"
             @click.self="close()">
            <div class="fixed inset-0 bg-black/80"></div>
            <img :src="currentImage" class="relative z-10 max-w-[90vw] max-h-[90vh] object-contain rounded-lg shadow-2xl" @click.stop>
            <template x-if="hasMultiple">
                <button type="button" @click.stop="prev()" class="absolute left-4 z-20 bg-white/10 hover:bg-white/20 text-white rounded-full p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
            </template>
            <template x-if="hasMultiple">
                <button type="button" @click.stop="next()" class="absolute right-4 z-20 bg-white/10 hover:bg-white/20 text-white rounded-full p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </template>
            <div x-show="hasMultiple" class="absolute bottom-4 z-20 bg-black/60 text-white px-3 py-1 rounded-full text-sm">
                <span x-text="(currentIndex + 1) + ' / ' + images.length"></span>
            </div>
            <button type="button" @click="close()" class="absolute top-4 right-4 z-20 bg-white/10 hover:bg-white/20 text-white rounded-full p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
</x-app-layout>
