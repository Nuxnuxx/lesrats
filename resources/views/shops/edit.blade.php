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

                    {{-- Etsy Categories --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4" x-data="etsyCategoriesManager(@js($shop->etsy_categories ?? []))">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Categories Etsy</h3>

                        <div class="space-y-2">
                            <template x-for="(category, index) in categories" :key="index">
                                <div class="p-3 border border-gray-200 rounded-lg bg-gray-50">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-medium text-gray-600" x-text="'#' + (index + 1)"></span>
                                        <button type="button" @click="removeCategory(index)" class="text-red-400 hover:text-red-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="text" x-model="category.name" placeholder="Nom interne"
                                            @input.debounce.800ms="saveCategories()"
                                            class="text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500">
                                        <input type="text" x-model="category.etsy_name" placeholder="Nom Etsy exact"
                                            @input.debounce.800ms="saveCategories()"
                                            class="text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500">
                                        <input type="text" x-model="category.etsy_id" placeholder="ID checkbox Etsy"
                                            @input.debounce.800ms="saveCategories()"
                                            class="text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500">
                                        <input type="text" x-model="category.keywords" placeholder="Mots-cles (auto-detect)"
                                            @input.debounce.800ms="saveCategories()"
                                            class="text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500">
                                    </div>
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
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Prompts IA</h3>
                        <p class="text-xs text-gray-400 mb-3">
                            <span class="text-orange-600">[SHOP_NICHE]</span> = description boutique
                        </p>

                        <div class="space-y-3">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="ai_description_prompt" class="text-xs font-medium text-gray-700">Titres / descriptions / tags</label>
                                    <button type="button"
                                        @click="document.getElementById('ai_description_prompt').value = ''; save({ ai_description_prompt: '' })"
                                        class="text-xs text-orange-600 hover:text-orange-700">Reset</button>
                                </div>
                                <textarea id="ai_description_prompt" rows="6"
                                    @input.debounce.800ms="save({ ai_description_prompt: $el.value })"
                                    class="block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-xs font-mono"
                                    placeholder="Vide = prompt par defaut...">{{ $shop->ai_description_prompt }}</textarea>
                                <p class="mt-1 text-xs {{ $shop->ai_description_prompt ? 'text-orange-600' : 'text-green-600' }}">
                                    {{ $shop->ai_description_prompt ? 'Personnalise' : 'Par defaut' }}
                                </p>
                            </div>

                            <div class="pt-3 border-t border-gray-100">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-3">
                                        <label for="ai_image_enabled" class="text-xs font-medium text-gray-700">Transformation images IA</label>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" value="1" {{ $shop->ai_image_enabled ? 'checked' : '' }}
                                                @change="save({ ai_image_enabled: $el.checked })"
                                                class="sr-only peer" id="ai_image_enabled">
                                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-600"></div>
                                        </label>
                                    </div>
                                    <button type="button"
                                        @click="document.getElementById('ai_image_prompt').value = ''; save({ ai_image_prompt: '' })"
                                        class="text-xs text-orange-600 hover:text-orange-700">Reset</button>
                                </div>
                                <textarea id="ai_image_prompt" rows="6"
                                    @input.debounce.800ms="save({ ai_image_prompt: $el.value })"
                                    class="block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-xs font-mono"
                                    placeholder="Vide = prompt par defaut...">{{ $shop->ai_image_prompt }}</textarea>
                                <p class="mt-1 text-xs {{ $shop->ai_image_prompt ? 'text-orange-600' : 'text-green-600' }}">
                                    {{ $shop->ai_image_prompt ? 'Personnalise' : 'Par defaut' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Specific Prompts --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Prompts specifiques (images)</h3>
                        <p class="text-xs text-gray-400 mb-3">Ajoutes en complement du prompt general. Disponibles dans le menu lors de la generation.</p>

                        @php $specificPrompts = $shop->ai_specific_prompts ?? []; @endphp
                        @if(count($specificPrompts) > 0)
                            <div class="mb-3 space-y-2">
                                @foreach($specificPrompts as $index => $sp)
                                    <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900">{{ $sp['name'] }}</p>
                                            <p class="text-xs text-gray-500 truncate">{{ $sp['prompt'] }}</p>
                                        </div>
                                        <form method="POST" action="{{ route('shops.delete-specific-prompt', $shop) }}"
                                              onsubmit="return confirm('Supprimer ?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="index" value="{{ $index }}">
                                            <button type="submit" class="text-red-400 hover:text-red-600 p-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('shops.add-specific-prompt', $shop) }}" x-data="{ showForm: false }">
                            @csrf
                            <button type="button" @click="showForm = !showForm" x-show="!showForm"
                                    class="inline-flex items-center text-xs font-medium text-orange-600 hover:text-orange-700">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Ajouter
                            </button>

                            <div x-show="showForm" x-cloak class="space-y-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <input type="text" name="name" required
                                       class="w-full text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500"
                                       placeholder="Nom du prompt">
                                <textarea name="prompt" rows="3" required
                                          class="w-full text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500 font-mono"
                                          placeholder="Instructions specifiques..."></textarea>
                                <div class="flex gap-2">
                                    <button type="submit" class="px-3 py-1.5 bg-orange-600 text-white rounded-lg text-xs font-medium hover:bg-orange-700">Ajouter</button>
                                    <button type="button" @click="showForm = false" class="text-xs text-gray-500 hover:text-gray-700">Annuler</button>
                                </div>
                            </div>
                        </form>
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

        function etsyCategoriesManager(initialCategories) {
            return {
                categories: initialCategories,
                addCategory() {
                    this.categories.push({ name: '', etsy_name: '', etsy_id: '', keywords: '' });
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
