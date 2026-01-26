<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('shops.show', $shop) }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Parametres - {{ $shop->name }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Shop Settings --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de la boutique</h3>

                <form method="POST" action="{{ route('shops.update', $shop) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom de la boutique</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $shop->name) }}" required
                            class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description / Niche</label>
                        <textarea id="description" name="description" rows="3"
                            class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-sm"
                            placeholder="Ex: outdoor equipment and garden tools, 3D printed figurines, handmade jewelry...">{{ old('description', $shop->description) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Decrivez votre niche/specialite. Sera utilise dans les prompts IA pour personnaliser les contenus.</p>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Logo de la boutique</label>
                        <div class="flex items-start gap-4">
                            @if($shop->logo_path)
                                <div class="flex-shrink-0">
                                    <img src="{{ $shop->logo_url }}" alt="Logo" class="w-20 h-20 object-contain rounded-lg border border-gray-200 bg-gray-50">
                                </div>
                            @endif
                            <div class="flex-1">
                                <input type="file" name="logo" id="logo" accept="image/*"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                                <p class="mt-1 text-xs text-gray-500">PNG ou JPG recommande. Sera utilise pour la generation d'images IA.</p>
                                @if($shop->logo_path)
                                    <label class="mt-2 inline-flex items-center text-sm text-red-600 cursor-pointer">
                                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300 text-red-600 mr-2">
                                        Supprimer le logo actuel
                                    </label>
                                @endif
                            </div>
                        </div>
                        @error('logo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="currency" class="block text-sm font-medium text-gray-700">Devise</label>
                        <select id="currency" name="currency" required
                            class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm">
                            <option value="EUR" {{ old('currency', $shop->currency) == 'EUR' ? 'selected' : '' }}>EUR (Euro)</option>
                            <option value="USD" {{ old('currency', $shop->currency) == 'USD' ? 'selected' : '' }}>USD (Dollar US)</option>
                            <option value="GBP" {{ old('currency', $shop->currency) == 'GBP' ? 'selected' : '' }}>GBP (Livre Sterling)</option>
                            <option value="CAD" {{ old('currency', $shop->currency) == 'CAD' ? 'selected' : '' }}>CAD (Dollar Canadien)</option>
                        </select>
                        @error('currency')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $shop->is_active) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-orange-600 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Boutique active</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('shops.show', $shop) }}" 
                           class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">
                            Annuler
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>

            {{-- AI Settings --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Parametres IA</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Personnalisez les prompts utilises pour generer les titres, descriptions et images de vos produits.
                    <br><span class="text-orange-600">Utilisez [SHOP_NICHE] pour inserer automatiquement la description de votre boutique.</span>
                </p>

                <form method="POST" action="{{ route('shops.update', $shop) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="name" value="{{ $shop->name }}">
                    <input type="hidden" name="currency" value="{{ $shop->currency }}">
                    <input type="hidden" name="is_active" value="{{ $shop->is_active ? '1' : '0' }}">

                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="ai_description_prompt" class="block text-sm font-medium text-gray-700">Prompt pour titres/descriptions/tags</label>
                                <button type="button" onclick="document.getElementById('ai_description_prompt').value = ''; this.closest('form').submit();"
                                    class="text-xs text-orange-600 hover:text-orange-700">Restaurer par defaut</button>
                            </div>
                            <textarea id="ai_description_prompt" name="ai_description_prompt" rows="8"
                                class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-sm font-mono"
                                placeholder="Laissez vide pour utiliser le prompt par defaut...">{{ old('ai_description_prompt', $shop->ai_description_prompt) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                @if(!$shop->ai_description_prompt)
                                    <span class="text-green-600">Utilise le prompt par defaut (expert SEO Etsy)</span>
                                @else
                                    Prompt personnalise actif
                                @endif
                            </p>
                            @error('ai_description_prompt')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <label for="ai_image_enabled" class="text-sm font-medium text-gray-700">Transformation d'images IA</label>
                                    <p class="text-xs text-gray-500">Transforme les images des produits lors de l'import (necessite une cle API Fal.ai)</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="ai_image_enabled" value="1" {{ old('ai_image_enabled', $shop->ai_image_enabled) ? 'checked' : '' }}
                                        class="sr-only peer" id="ai_image_enabled">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                                </label>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="ai_image_prompt" class="block text-sm font-medium text-gray-700">Prompt pour les images</label>
                                    <button type="button" onclick="document.getElementById('ai_image_prompt').value = ''; this.closest('form').submit();"
                                        class="text-xs text-orange-600 hover:text-orange-700">Restaurer par defaut</button>
                                </div>
                                <textarea id="ai_image_prompt" name="ai_image_prompt" rows="6"
                                    class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-sm font-mono"
                                    placeholder="Laissez vide pour utiliser le prompt par defaut...">{{ old('ai_image_prompt', $shop->ai_image_prompt) }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">
                                    @if(!$shop->ai_image_prompt)
                                        <span class="text-green-600">Utilise le prompt par defaut (generateur images Etsy)</span>
                                    @else
                                        Prompt personnalise actif
                                    @endif
                                </p>
                                @error('ai_image_prompt')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">
                            Enregistrer les prompts IA
                        </button>
                    </div>
                </form>
            </div>

            {{-- Etsy Categories --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" x-data="etsyCategoriesManager()">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Categories Etsy</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Definissez les categories Etsy disponibles pour cette boutique. Chaque categorie peut avoir des mots-cles pour l'auto-detection.
                </p>

                <form method="POST" action="{{ route('shops.update-categories', $shop) }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-3" id="categories-container">
                        <template x-for="(category, index) in categories" :key="index">
                            <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                                <div class="flex items-start justify-between mb-3">
                                    <span class="text-sm font-medium text-gray-700" x-text="'Categorie ' + (index + 1)"></span>
                                    <button type="button" @click="removeCategory(index)" class="text-red-500 hover:text-red-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Nom (interne)</label>
                                        <input type="text" x-model="category.name" 
                                            class="block w-full text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500"
                                            placeholder="Ex: Fichiers 3D">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Nom Etsy (exact)</label>
                                        <input type="text" x-model="category.etsy_name" 
                                            class="block w-full text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500"
                                            placeholder="Ex: Fichiers pour imprimante 3D">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">ID Checkbox Etsy</label>
                                        <input type="text" x-model="category.etsy_id" 
                                            class="block w-full text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500"
                                            placeholder="Ex: category-12380">
                                        <p class="mt-1 text-xs text-gray-400">Inspecter l'element sur Etsy pour trouver l'ID</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Mots-cles (auto-detect)</label>
                                        <input type="text" x-model="category.keywords" 
                                            class="block w-full text-sm border-gray-300 rounded-lg focus:border-orange-500 focus:ring-orange-500"
                                            placeholder="Ex: 3d, stl, print, figurine">
                                        <p class="mt-1 text-xs text-gray-400">Separes par virgules</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <input type="hidden" name="etsy_categories" :value="JSON.stringify(categories)">

                    <div class="mt-4 flex items-center justify-between">
                        <button type="button" @click="addCategory()" 
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-orange-600 hover:text-orange-700">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Ajouter une categorie
                        </button>
                        <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">
                            Enregistrer les categories
                        </button>
                    </div>
                </form>
            </div>

            @push('scripts')
            <script>
                function etsyCategoriesManager() {
                    return {
                        categories: @json($shop->etsy_categories ?? []),
                        
                        addCategory() {
                            this.categories.push({
                                name: '',
                                etsy_name: '',
                                etsy_id: '',
                                keywords: ''
                            });
                        },
                        
                        removeCategory(index) {
                            this.categories.splice(index, 1);
                        }
                    }
                }
            </script>
            @endpush

            {{-- Danger Zone --}}
            <div class="bg-white rounded-lg shadow-sm border border-red-200 p-6">
                <h3 class="text-lg font-semibold text-red-600 mb-4">Zone dangereuse</h3>
                
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Supprimer cette boutique</p>
                        <p class="text-xs text-gray-500">Cette action supprimera tous les produits et commandes associes.</p>
                    </div>
                    <form action="{{ route('shops.destroy', $shop) }}" method="POST"
                          onsubmit="return confirm('ATTENTION: Cette action est irreversible. Tous les produits et commandes seront supprimes. Continuer ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Supprimer
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
