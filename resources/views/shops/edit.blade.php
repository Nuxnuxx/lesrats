<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $shop->name }}</h2>
                    <p class="text-sm text-gray-500">Parametres de la boutique</p>
                </div>
            </div>
            <span id="autosave-status" class="text-xs text-gray-400 hidden">Sauvegarde...</span>
        </div>
    </x-slot>

    <div class="py-4" x-data="shopAutoSave()">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                {{-- LEFT COLUMN --}}
                <div class="space-y-4">

                    {{-- Shop Info --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Boutique</h3>

                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label for="name" class="text-xs font-medium text-gray-700">Nom</label>
                                <input id="name" type="text" value="{{ $shop->name }}"
                                    @input.debounce.800ms="save({ name: $el.value })"
                                    class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-sm">
                            </div>
                            <div>
                                <label for="currency" class="text-xs font-medium text-gray-700">Devise</label>
                                <select id="currency" @change="save({ currency: $el.value })"
                                    class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-sm">
                                    <option value="EUR" {{ $shop->currency == 'EUR' ? 'selected' : '' }}>EUR</option>
                                    <option value="USD" {{ $shop->currency == 'USD' ? 'selected' : '' }}>USD</option>
                                    <option value="GBP" {{ $shop->currency == 'GBP' ? 'selected' : '' }}>GBP</option>
                                    <option value="CAD" {{ $shop->currency == 'CAD' ? 'selected' : '' }}>CAD</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="text-xs font-medium text-gray-700">Niche / Description</label>
                            <textarea id="description" rows="2"
                                @input.debounce.800ms="save({ description: $el.value })"
                                class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-sm"
                                placeholder="Ex: bijoux fantaisie, fichiers 3D...">{{ $shop->description }}</textarea>
                            <p class="mt-1 text-xs text-gray-400">Utilisee dans les prompts IA.</p>
                        </div>

                        <div class="mb-3" x-data="{ type: '{{ $shop->product_type ?? 'physical' }}' }">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="product_type" class="text-xs font-medium text-gray-700">Type de produits</label>
                                    <select id="product_type" x-model="type"
                                        @change="save({ product_type: $el.value })"
                                        class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-sm">
                                        <option value="physical">Physiques (dropshipping)</option>
                                        <option value="virtual">Virtuels (fichiers)</option>
                                    </select>
                                </div>
                                <div x-show="type === 'virtual'" x-cloak>
                                    <label for="default_price" class="text-xs font-medium text-gray-700">Prix par defaut</label>
                                    <div class="mt-1 relative">
                                        <input type="number" id="default_price" step="0.01" min="0"
                                               value="{{ number_format($shop->default_price ?? 0, 2, '.', '') }}"
                                               @input.debounce.800ms="save({ default_price: $el.value })"
                                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 pr-12 text-sm">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-xs">{{ $shop->currency }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Logo (file upload — needs traditional form) --}}
                        <div class="pt-3 border-t border-gray-100">
                            <label class="text-xs font-medium text-gray-700">Logo</label>
                            <form method="POST" action="{{ route('shops.update', $shop) }}" enctype="multipart/form-data" class="mt-1">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="name" value="{{ $shop->name }}">
                                <input type="hidden" name="currency" value="{{ $shop->currency }}">
                                <div class="flex items-center gap-3">
                                    @if($shop->logo_path)
                                        <img src="{{ $shop->logo_url }}" alt="Logo" class="w-12 h-12 object-contain rounded-lg border border-gray-200 bg-gray-50">
                                    @endif
                                    <input type="file" name="logo" accept="image/*"
                                        class="flex-1 text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100"
                                        onchange="this.closest('form').submit()">
                                    @if($shop->logo_path)
                                        <label class="inline-flex items-center text-xs text-red-500 cursor-pointer whitespace-nowrap">
                                            <input type="checkbox" name="remove_logo" value="1"
                                                class="rounded border-gray-300 text-red-600 mr-1 w-3 h-3"
                                                onchange="this.closest('form').submit()">
                                            Suppr.
                                        </label>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Etsy Settings --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Parametres Etsy</h3>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="shipping_fee" class="text-xs font-medium text-gray-700">Frais de livraison</label>
                                <div class="mt-1 relative">
                                    <input type="number" id="shipping_fee" step="0.01" min="0"
                                           value="{{ number_format($shop->shipping_fee ?? 0, 2, '.', '') }}"
                                           @input.debounce.800ms="save({ shipping_fee: $el.value })"
                                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 pr-12 text-sm">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-xs">{{ $shop->currency }}</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-400">Facture au client sur Etsy</p>
                            </div>
                            <div>
                                <label for="discount_percentage" class="text-xs font-medium text-gray-700">Reduction boutique</label>
                                <div class="mt-1 relative">
                                    <input type="number" id="discount_percentage" step="1" min="0" max="99"
                                           value="{{ $shop->discount_percentage ?? 0 }}"
                                           @input.debounce.800ms="save({ discount_percentage: $el.value })"
                                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 pr-10 text-sm">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-xs">%</span>
                                    </div>
                                </div>
                                @php $discount = (float)($shop->discount_percentage ?? 0); @endphp
                                @if($discount > 0)
                                    <p class="mt-1 text-xs text-green-600">10EUR &rarr; {{ number_format(10 / (1 - $discount/100), 2) }}EUR sur Etsy</p>
                                @else
                                    <p class="mt-1 text-xs text-gray-400">0 = pas de reduction</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Mode Expert — Pricing optimal --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"
                         x-data="{
                             expertMode: {{ $shop->expert_mode ? 'true' : 'false' }},
                             pricingK: {{ (float)($shop->pricing_k ?? 1.0) }},
                             pricingT: {{ (float)($shop->pricing_t ?? 0.20) }},
                             pricingT0: {{ (float)($shop->pricing_t0 ?? 0.00) }},
                             get preview() {
                                 if (this.pricingK <= 0 || this.pricingT >= 1) return '—';
                                 return ((1 / this.pricingK) + (this.pricingT0 + 10) / (1 - this.pricingT)).toFixed(2);
                             }
                         }">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Mode expert — Pricing optimal</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Calcul automatique du prix à l'import AliExpress</p>
                            </div>
                            <button type="button"
                                    @click="expertMode = !expertMode; _shopAutoSave.save({ expert_mode: expertMode })"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                    :class="expertMode ? 'bg-orange-500' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                      :class="expertMode ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                        </div>

                        <div x-show="expertMode" x-transition>
                            {{-- Formule --}}
                            <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 mb-4 text-center">
                                <p class="font-mono text-sm text-gray-700">P<sub>opt</sub> = 1/k + (T<sub>0</sub> + C) / (1 − T)</p>
                                <p class="text-xs text-gray-400 mt-1">C = coût AliExpress &nbsp;·&nbsp; k = élasticité &nbsp;·&nbsp; T = taux taxe &nbsp;·&nbsp; T₀ = offset taxe</p>
                            </div>

                            <div class="grid grid-cols-3 gap-3 mb-3">
                                <div>
                                    <label class="text-xs font-medium text-gray-700">k — élasticité</label>
                                    <input type="number" step="0.01" min="0.01" max="100"
                                           x-model="pricingK"
                                           @input.debounce.800ms="_shopAutoSave.save({ pricing_k: pricingK })"
                                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-700">T — taux taxe (ex: 0.20)</label>
                                    <input type="number" step="0.01" min="0" max="0.99"
                                           x-model="pricingT"
                                           @input.debounce.800ms="_shopAutoSave.save({ pricing_t: pricingT })"
                                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-700">T₀ — offset taxe (€)</label>
                                    <input type="number" step="0.01" min="0"
                                           x-model="pricingT0"
                                           @input.debounce.800ms="_shopAutoSave.save({ pricing_t0: pricingT0 })"
                                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                                </div>
                            </div>

                            {{-- Preview live --}}
                            <div class="bg-orange-50 border border-orange-200 rounded-lg px-3 py-2 text-xs text-orange-800">
                                Exemple pour C = 10 € →
                                <span class="font-semibold font-mono" x-text="preview + ' €'"></span>
                                <span class="text-orange-500 ml-2">(sans mode expert : 25.00 €)</span>
                            </div>
                        </div>
                    </div>

                    {{-- Etsy Categories --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4" x-data="etsyCategoriesManager(@js($shop->etsy_categories ?? []))">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Categories Etsy</h3>

                        <div class="space-y-2">
                            <template x-for="(category, index) in categories" :key="index">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-gray-400 w-5" x-text="(index + 1)"></span>
                                    <input type="text" x-model="categories[index]" placeholder="Nom de la categorie"
                                        @input.debounce.800ms="saveCategories()"
                                        class="flex-1 text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500">
                                    <button type="button" @click="removeCategory(index)" class="text-red-400 hover:text-red-600 shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div class="mt-3">
                            <button type="button" @click="addCategory()"
                                class="inline-flex items-center text-xs font-medium text-orange-600 hover:text-orange-700">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Ajouter
                            </button>
                        </div>
                    </div>

                    {{-- Available Tags --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4" x-data="availableTagsManager(@js($shop->available_tags ?? []))">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Tags disponibles</h3>
                        <p class="text-xs text-gray-400 mb-3">L'IA selectionnera les 13 plus pertinents parmi cette liste. Vide = tags libres.</p>

                        <div class="flex flex-wrap gap-1.5 mb-3 min-h-[36px] p-2 border border-gray-200 rounded-lg bg-gray-50">
                            <template x-if="tags.length === 0">
                                <span class="text-xs text-gray-400 italic">Aucun tag - IA libre</span>
                            </template>
                            <template x-for="(tag, index) in tags" :key="index">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    <span x-text="tag"></span>
                                    <button type="button" @click="removeTag(index)" class="ml-1 text-orange-600 hover:text-orange-800">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </span>
                            </template>
                        </div>

                        <div class="flex gap-2 mb-2">
                            <input type="text" x-model="newTag"
                                @keydown.enter.prevent="addTag()"
                                @keydown.comma.prevent="addTag()"
                                class="flex-1 text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500"
                                placeholder="Ajouter un tag...">
                            <button type="button" @click="addTag()" class="px-3 py-1.5 bg-orange-100 text-orange-700 rounded-lg text-sm font-medium hover:bg-orange-200">+</button>
                        </div>

                        <div class="flex gap-2 mb-3">
                            <input type="text" x-model="bulkTags"
                                class="flex-1 text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500"
                                placeholder="Ajout en masse: tag1, tag2, tag3...">
                            <button type="button" @click="addBulkTags()" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">Ajouter</button>
                        </div>

                        <div class="pt-3 border-t border-gray-100">
                            <span class="text-xs text-gray-500" x-text="tags.length + ' tags'"></span>
                        </div>
                    </div>
                </div>

                {{-- RIGHT COLUMN --}}
                <div class="space-y-4">

                    {{-- AI Prompts --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4" x-data="promptEditor({
                        descriptionPrompt: @js($shop->ai_description_prompt ?? ''),
                        imagePrompt: @js($shop->ai_image_prompt ?? '')
                    })">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Prompts IA</h3>
                        <p class="text-xs text-gray-400 mb-3">
                            <span class="text-orange-600">[SHOP_NICHE]</span> = description boutique.
                            Cliquer pour modifier.
                        </p>

                        <div class="space-y-3">
                            {{-- Description prompt summary --}}
                            <div @click="openModal('description')" class="p-3 bg-gray-50 rounded-lg border border-gray-200 cursor-pointer hover:border-orange-300 hover:bg-orange-50/30 transition-colors group">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-700">Titres / descriptions / tags</span>
                                    <span class="text-xs" :class="descriptionPrompt ? 'text-orange-600' : 'text-green-600'" x-text="descriptionPrompt ? 'Personnalise' : 'Par defaut'"></span>
                                </div>
                                <p class="text-xs text-gray-500 line-clamp-2 font-mono" x-show="descriptionPrompt" x-text="descriptionPrompt"></p>
                                <p class="text-xs text-gray-400 italic" x-show="!descriptionPrompt">Prompt par defaut utilise</p>
                            </div>

                            {{-- Image prompt summary --}}
                            <div @click="openModal('image')" class="p-3 bg-gray-50 rounded-lg border border-gray-200 cursor-pointer hover:border-orange-300 hover:bg-orange-50/30 transition-colors group">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-700">Transformation images IA</span>
                                    <span class="text-xs" :class="imagePrompt ? 'text-orange-600' : 'text-green-600'" x-text="imagePrompt ? 'Personnalise' : 'Par defaut'"></span>
                                </div>
                                <p class="text-xs text-gray-500 line-clamp-2 font-mono" x-show="imagePrompt" x-text="imagePrompt"></p>
                                <p class="text-xs text-gray-400 italic" x-show="!imagePrompt">Prompt par defaut utilise</p>
                            </div>
                        </div>

                        {{-- Prompt edit modal --}}
                        <template x-teleport="body">
                            <div x-show="showModal" x-cloak
                                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                 @keydown.escape.window="closeModal()">
                                <div class="fixed inset-0 bg-black/50" @click="closeModal()"></div>
                                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col"
                                     @click.stop>
                                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900" x-text="modalTitle"></h3>
                                        <div class="flex items-center gap-3">
                                            <button type="button" @click="resetPrompt()"
                                                class="text-sm text-orange-600 hover:text-orange-700 font-medium">Reset</button>
                                            <button type="button" @click="closeModal()"
                                                class="text-gray-400 hover:text-gray-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="p-6 flex-1 overflow-y-auto">
                                        <textarea x-ref="modalTextarea" x-model="editValue"
                                            @input.debounce.800ms="saveCurrentPrompt()"
                                            class="w-full h-[60vh] border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-sm font-mono"
                                            placeholder="Vide = prompt par defaut..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Specific Prompts --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4" x-data="specificPromptsManager(@js($shop->ai_specific_prompts ?? []))">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Prompts specifiques (images)</h3>
                        <p class="text-xs text-gray-400 mb-3">Ajoutes en complement du prompt general. Cliquer pour modifier.</p>

                        <div class="space-y-2 mb-3">
                            <template x-for="(sp, index) in prompts" :key="index">
                                <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg border border-gray-200 cursor-pointer hover:border-orange-300 hover:bg-orange-50/30 transition-colors"
                                     @click="openEdit(index)">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900" x-text="sp.name"></p>
                                        <p class="text-xs text-gray-500 truncate" x-text="sp.prompt"></p>
                                    </div>
                                    <button type="button" @click.stop="removePrompt(index)"
                                        class="text-red-400 hover:text-red-600 p-1 shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                            <template x-if="prompts.length === 0">
                                <p class="text-xs text-gray-400 italic text-center py-2">Aucun prompt specifique</p>
                            </template>
                        </div>

                        <button type="button" @click="addPrompt()"
                            class="inline-flex items-center text-xs font-medium text-orange-600 hover:text-orange-700">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Ajouter
                        </button>

                        {{-- Specific prompt edit modal --}}
                        <template x-teleport="body">
                            <div x-show="showModal" x-cloak
                                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                 @keydown.escape.window="closeEdit()">
                                <div class="fixed inset-0 bg-black/50" @click="closeEdit()"></div>
                                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col"
                                     @click.stop>
                                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900" x-text="editIndex === -1 ? 'Nouveau prompt specifique' : 'Modifier le prompt'"></h3>
                                        <button type="button" @click="closeEdit()"
                                            class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="p-6 flex-1 overflow-y-auto space-y-4">
                                        <div>
                                            <label class="text-xs font-medium text-gray-700 mb-1 block">Nom</label>
                                            <input type="text" x-model="editName"
                                                class="w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-sm"
                                                placeholder="Ex: Style minimaliste, Photo lifestyle...">
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-gray-700 mb-1 block">Prompt</label>
                                            <textarea x-model="editPromptText"
                                                class="w-full h-[50vh] border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-sm font-mono"
                                                placeholder="Instructions specifiques pour la generation d'images..."></textarea>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200">
                                        <button type="button" @click="closeEdit()" class="text-sm text-gray-500 hover:text-gray-700">Annuler</button>
                                        <button type="button" @click="saveEdit()"
                                            class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">
                                            <span x-text="editIndex === -1 ? 'Ajouter' : 'Enregistrer'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Backgrounds --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Backgrounds (images IA)</h3>

                        @php $backgrounds = $shop->ai_backgrounds ?? []; @endphp
                        @if(count($backgrounds) > 0)
                            <div class="grid grid-cols-4 gap-2 mb-3">
                                @foreach($backgrounds as $index => $bg)
                                    <div class="relative group">
                                        <div class="aspect-square rounded-lg overflow-hidden border border-gray-200 bg-gray-100">
                                            <img src="{{ Storage::disk('public')->url($bg['path']) }}" alt="{{ $bg['name'] }}"
                                                 class="w-full h-full object-cover">
                                        </div>
                                        <p class="text-xs text-gray-600 mt-1 truncate" title="{{ $bg['name'] }}">{{ $bg['name'] }}</p>
                                        <form method="POST" action="{{ route('shops.delete-background', $shop) }}"
                                              onsubmit="return confirm('Supprimer ?')" class="absolute top-1 right-1">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="index" value="{{ $index }}">
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 text-center py-3 mb-3">Aucun background</p>
                        @endif

                        <form method="POST" action="{{ route('shops.upload-background', $shop) }}" enctype="multipart/form-data"
                              class="pt-3 border-t border-gray-100">
                            @csrf
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <input type="text" name="name" required
                                       class="text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500"
                                       placeholder="Nom du background">
                                <input type="file" name="background" accept="image/*" required
                                       class="text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-1.5 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700">
                                    Ajouter
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Logos IA --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Logos (images IA)</h3>

                        @php $aiLogos = $shop->ai_logos ?? []; @endphp
                        @if(count($aiLogos) > 0)
                            <div class="grid grid-cols-4 gap-2 mb-3">
                                @foreach($aiLogos as $index => $logo)
                                    <div class="relative group">
                                        <div class="aspect-square rounded-lg overflow-hidden border border-gray-200 bg-gray-100">
                                            <img src="{{ Storage::disk('public')->url($logo['path']) }}" alt="{{ $logo['name'] }}"
                                                 class="w-full h-full object-cover">
                                        </div>
                                        <p class="text-xs text-gray-600 mt-1 truncate" title="{{ $logo['name'] }}">{{ $logo['name'] }}</p>
                                        <form method="POST" action="{{ route('shops.delete-logo', $shop) }}"
                                              onsubmit="return confirm('Supprimer ?')" class="absolute top-1 right-1">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="index" value="{{ $index }}">
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 text-center py-3 mb-3">Aucun logo</p>
                        @endif

                        <form method="POST" action="{{ route('shops.upload-logo', $shop) }}" enctype="multipart/form-data"
                              class="pt-3 border-t border-gray-100">
                            @csrf
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <input type="text" name="name" required
                                       class="text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500"
                                       placeholder="Nom du logo">
                                <input type="file" name="logo" accept="image/*" required
                                       class="text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-1.5 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700">
                                    Ajouter
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Danger Zone --}}
                    <div class="bg-white rounded-lg shadow-sm border border-red-200 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-red-600">Supprimer la boutique</p>
                                <p class="text-xs text-gray-500">Irreversible. Supprime tous les produits.</p>
                            </div>
                            <form action="{{ route('shops.destroy', $shop) }}" method="POST"
                                  onsubmit="return confirm('ATTENTION: Tous les produits seront supprimes. Continuer ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Shared autosave helper — used by all components on this page
        const _shopAutoSave = {
            shopId: {{ $shop->id }},
            csrfToken: '{{ csrf_token() }}',
            async save(data) {
                const status = document.getElementById('autosave-status');
                status.classList.remove('hidden');
                status.textContent = 'Sauvegarde...';
                status.className = 'text-xs text-gray-400';

                try {
                    const response = await fetch(`/shops/${this.shopId}/autosave`, {
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

        function shopAutoSave() {
            return {
                save(data) { return _shopAutoSave.save(data); }
            };
        }

        function promptEditor(config) {
            return {
                descriptionPrompt: config.descriptionPrompt,
                imagePrompt: config.imagePrompt,
                showModal: false,
                editingType: null, // 'description' or 'image'
                editValue: '',
                modalTitle: '',

                openModal(type) {
                    this.editingType = type;
                    if (type === 'description') {
                        this.editValue = this.descriptionPrompt;
                        this.modalTitle = 'Prompt: Titres / descriptions / tags';
                    } else {
                        this.editValue = this.imagePrompt;
                        this.modalTitle = 'Prompt: Transformation images IA';
                    }
                    this.showModal = true;
                    document.body.classList.add('overflow-hidden');
                    this.$nextTick(() => this.$refs.modalTextarea?.focus());
                },

                closeModal() {
                    this.saveCurrentPrompt();
                    this.showModal = false;
                    this.editingType = null;
                    document.body.classList.remove('overflow-hidden');
                },

                resetPrompt() {
                    this.editValue = '';
                    this.saveCurrentPrompt();
                },

                saveCurrentPrompt() {
                    if (this.editingType === 'description') {
                        this.descriptionPrompt = this.editValue;
                        _shopAutoSave.save({ ai_description_prompt: this.editValue });
                    } else if (this.editingType === 'image') {
                        this.imagePrompt = this.editValue;
                        _shopAutoSave.save({ ai_image_prompt: this.editValue });
                    }
                }
            };
        }

        function specificPromptsManager(initialPrompts) {
            return {
                prompts: initialPrompts,
                showModal: false,
                editIndex: -1, // -1 = new, >= 0 = editing existing
                editName: '',
                editPromptText: '',

                openEdit(index) {
                    this.editIndex = index;
                    this.editName = this.prompts[index].name;
                    this.editPromptText = this.prompts[index].prompt;
                    this.showModal = true;
                    document.body.classList.add('overflow-hidden');
                },

                addPrompt() {
                    this.editIndex = -1;
                    this.editName = '';
                    this.editPromptText = '';
                    this.showModal = true;
                    document.body.classList.add('overflow-hidden');
                },

                saveEdit() {
                    if (!this.editName.trim()) return;
                    if (this.editIndex === -1) {
                        this.prompts.push({ name: this.editName, prompt: this.editPromptText });
                    } else {
                        this.prompts[this.editIndex].name = this.editName;
                        this.prompts[this.editIndex].prompt = this.editPromptText;
                    }
                    this.savePrompts();
                    this.closeEdit();
                },

                removePrompt(index) {
                    if (!confirm('Supprimer ce prompt ?')) return;
                    this.prompts.splice(index, 1);
                    this.savePrompts();
                },

                closeEdit() {
                    this.showModal = false;
                    document.body.classList.remove('overflow-hidden');
                },

                savePrompts() {
                    _shopAutoSave.save({ ai_specific_prompts: JSON.stringify(this.prompts) });
                }
            };
        }

        function etsyCategoriesManager(initialCategories) {
            // Migrate old format [{name, etsy_name, ...}] to simple strings
            const cats = (initialCategories || []).map(c => typeof c === 'object' ? (c.name || c.etsy_name || '') : c).filter(c => c);
            return {
                categories: cats,
                addCategory() {
                    this.categories.push('');
                },
                removeCategory(index) {
                    this.categories.splice(index, 1);
                    this.saveCategories();
                },
                saveCategories() {
                    _shopAutoSave.save({ etsy_categories: JSON.stringify(this.categories) });
                }
            }
        }

        function availableTagsManager(initialTags) {
            return {
                tags: initialTags,
                newTag: '',
                bulkTags: '',
                addTag() {
                    const tag = this.newTag.trim().toLowerCase();
                    if (tag && !this.tags.includes(tag)) { this.tags.push(tag); }
                    this.newTag = '';
                    this.saveTags();
                },
                addBulkTags() {
                    const newTags = this.bulkTags.split(',').map(t => t.trim().toLowerCase()).filter(t => t && !this.tags.includes(t));
                    this.tags.push(...newTags);
                    this.bulkTags = '';
                    this.saveTags();
                },
                removeTag(index) {
                    this.tags.splice(index, 1);
                    this.saveTags();
                },
                saveTags() {
                    _shopAutoSave.save({ available_tags: JSON.stringify(this.tags) });
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
