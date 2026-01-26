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

                <form method="POST" action="{{ route('shops.update', $shop) }}">
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
                <p class="text-sm text-gray-500 mb-4">Personnalisez les prompts utilises pour generer les titres, descriptions et images de vos produits lors de l'import.</p>

                <form method="POST" action="{{ route('shops.update', $shop) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="name" value="{{ $shop->name }}">
                    <input type="hidden" name="currency" value="{{ $shop->currency }}">
                    <input type="hidden" name="is_active" value="{{ $shop->is_active ? '1' : '0' }}">

                    <div class="space-y-4">
                        <div>
                            <label for="ai_title_prompt" class="block text-sm font-medium text-gray-700">Prompt pour les titres</label>
                            <textarea id="ai_title_prompt" name="ai_title_prompt" rows="3"
                                class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-sm"
                                placeholder="Ex: This shop sells vintage jewelry. Make titles elegant and romantic. Always mention the material (gold, silver, etc.)">{{ old('ai_title_prompt', $shop->ai_title_prompt) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Instructions supplementaires pour la generation des titres de produits.</p>
                            @error('ai_title_prompt')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="ai_description_prompt" class="block text-sm font-medium text-gray-700">Prompt pour les descriptions</label>
                            <textarea id="ai_description_prompt" name="ai_description_prompt" rows="4"
                                class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-sm"
                                placeholder="Ex: Write descriptions for a luxury brand targeting women 25-45. Mention handcrafted quality and sustainability. Include care instructions.">{{ old('ai_description_prompt', $shop->ai_description_prompt) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Instructions supplementaires pour la generation des descriptions de produits.</p>
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
                                <label for="ai_image_prompt" class="block text-sm font-medium text-gray-700">Prompt pour les images</label>
                                <textarea id="ai_image_prompt" name="ai_image_prompt" rows="3"
                                    class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-sm"
                                    placeholder="Ex: Professional product photography on pure white background, studio lighting, high quality, commercial photography style">{{ old('ai_image_prompt', $shop->ai_image_prompt) }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Style de transformation pour les images (img2img avec Fal.ai).</p>
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
