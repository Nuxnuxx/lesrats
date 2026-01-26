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

                    {{-- Mode Selection --}}
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Mode de fonctionnement</label>

                        <div class="space-y-3">
                            <label class="flex items-start p-3 border rounded-lg cursor-pointer {{ old('mode', $shop->mode ?? 'manual') === 'manual' ? 'border-orange-500 bg-orange-50' : 'border-gray-300 hover:border-gray-400' }}">
                                <input type="radio" name="mode" value="manual"
                                       {{ old('mode', $shop->mode ?? 'manual') === 'manual' ? 'checked' : '' }}
                                       class="mt-0.5 mr-3">
                                <div>
                                    <p class="font-medium text-gray-900">Mode manuel</p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Creez des produits optimises et copiez-les manuellement dans Etsy. Aucune synchronisation automatique.
                                    </p>
                                </div>
                            </label>

                            <label class="flex items-start p-3 border rounded-lg cursor-pointer {{ old('mode', $shop->mode ?? 'manual') === 'connected' ? 'border-orange-500 bg-orange-50' : 'border-gray-300 hover:border-gray-400' }}">
                                <input type="radio" name="mode" value="connected"
                                       {{ old('mode', $shop->mode ?? 'manual') === 'connected' ? 'checked' : '' }}
                                       class="mt-0.5 mr-3">
                                <div>
                                    <p class="font-medium text-gray-900">Mode synchronise</p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Synchronisation automatique avec Etsy via l'API. Necessite une connexion Etsy active.
                                    </p>
                                </div>
                            </label>
                        </div>

                        @error('mode')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        @if($shop->mode === 'manual' || !$shop->etsy_shop_id)
                            <p class="mt-3 text-sm text-blue-600 flex items-start">
                                <svg class="w-5 h-5 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>
                                    @if(!$shop->etsy_shop_id)
                                        Connectez votre boutique Etsy ci-dessous pour activer le mode synchronise.
                                    @else
                                        Vous pouvez activer la synchronisation automatique maintenant que votre boutique est connectee.
                                    @endif
                                </span>
                            </p>
                        @endif
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

                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="auto_sync_enabled" value="1" {{ old('auto_sync_enabled', $shop->auto_sync_enabled) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-orange-600 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-700">Synchronisation automatique avec Etsy</span>
                        </label>
                        <p class="mt-1 text-xs text-gray-500 ml-6">Les nouveaux produits seront automatiquement publies sur Etsy</p>
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
                    <input type="hidden" name="auto_sync_enabled" value="{{ $shop->auto_sync_enabled ? '1' : '0' }}">

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

            {{-- Etsy API Credentials --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Identifiants API Etsy</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Entrez vos identifiants API Etsy pour connecter cette boutique. 
                    <a href="https://www.etsy.com/developers/your-apps" target="_blank" class="text-orange-600 hover:underline">Creer une application Etsy</a>
                </p>

                <form method="POST" action="{{ route('shops.update', $shop) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="name" value="{{ $shop->name }}">
                    <input type="hidden" name="currency" value="{{ $shop->currency }}">
                    <input type="hidden" name="is_active" value="{{ $shop->is_active ? '1' : '0' }}">
                    <input type="hidden" name="auto_sync_enabled" value="{{ $shop->auto_sync_enabled ? '1' : '0' }}">

                    <div class="space-y-4">
                        <div>
                            <label for="etsy_client_id" class="block text-sm font-medium text-gray-700">Client ID (Keystring)</label>
                            <input type="text" id="etsy_client_id" name="etsy_client_id"
                                class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm"
                                placeholder="{{ $shop->etsy_client_id ? 'Client ID configure (laisser vide pour garder)' : 'Votre Client ID Etsy' }}">
                            @if($shop->etsy_client_id)
                                <p class="mt-1 text-xs text-green-600">
                                    <svg class="inline w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Client ID configure
                                </p>
                            @endif
                            @error('etsy_client_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="etsy_client_secret" class="block text-sm font-medium text-gray-700">Client Secret</label>
                            <input type="password" id="etsy_client_secret" name="etsy_client_secret"
                                class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm"
                                placeholder="{{ $shop->etsy_client_secret ? 'Client Secret configure (laisser vide pour garder)' : 'Votre Client Secret Etsy' }}">
                            @if($shop->etsy_client_secret)
                                <p class="mt-1 text-xs text-green-600">
                                    <svg class="inline w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Client Secret configure
                                </p>
                            @endif
                            @error('etsy_client_secret')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">
                            Enregistrer les identifiants
                        </button>
                    </div>
                </form>
            </div>

            {{-- Etsy Connection --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Connexion Etsy</h3>

                @if(!$shop->etsy_client_id || !$shop->etsy_client_secret)
                    <div class="text-center py-6">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <p class="text-gray-600 mb-2">Identifiants API Etsy requis</p>
                        <p class="text-sm text-gray-500">Veuillez d'abord configurer vos identifiants API Etsy (Client ID et Client Secret) ci-dessus.</p>
                    </div>
                @elseif($shop->etsy_shop_id)
                    <div class="flex items-start space-x-4 mb-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M8.559 3.89c0-.458.442-.706.906-.706h5.07c.464 0 .906.248.906.706 0 .459-.442.707-.906.707h-5.07c-.464 0-.906-.248-.906-.707zm7.559 5.657c0 1.888-1.529 3.418-3.418 3.418H9.282c-.464 0-.906-.248-.906-.707s.442-.706.906-.706H12.7c1.107 0 2.006-.899 2.006-2.005 0-1.107-.899-2.006-2.006-2.006H9.282c-.464 0-.906-.247-.906-.706s.442-.707.906-.707H12.7c1.889 0 3.418 1.53 3.418 3.419z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <span class="font-medium text-gray-900">Boutique connectee</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    {{ $shop->connection_status_color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $shop->connection_status_color === 'red' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $shop->connection_status_color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                                    {{ $shop->connection_status_label }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">
                                ID: {{ $shop->etsy_shop_id }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                Token expire le {{ $shop->etsy_token_expires_at?->format('d/m/Y H:i') ?? 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        @if($shop->connection_status === 'expired')
                            <a href="{{ route('etsy.connect', $shop) }}" 
                               class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-sm rounded-lg hover:bg-orange-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Reconnecter
                            </a>
                        @endif
                        <form action="{{ route('etsy.disconnect', $shop) }}" method="POST" 
                              onsubmit="return confirm('Etes-vous sur de vouloir deconnecter cette boutique d\'Etsy ?');">
                            @csrf
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-red-100 text-red-700 text-sm rounded-lg hover:bg-red-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Deconnecter
                            </button>
                        </form>
                    </div>
                @else
                    <div class="text-center py-6">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                        </div>
                        <p class="text-gray-600 mb-4">Cette boutique n'est pas connectee a Etsy</p>
                        <a href="{{ route('etsy.connect', $shop) }}" 
                           class="inline-flex items-center px-6 py-3 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700">
                            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M8.559 3.89c0-.458.442-.706.906-.706h5.07c.464 0 .906.248.906.706 0 .459-.442.707-.906.707h-5.07c-.464 0-.906-.248-.906-.707zm7.559 5.657c0 1.888-1.529 3.418-3.418 3.418H9.282c-.464 0-.906-.248-.906-.707s.442-.706.906-.706H12.7c1.107 0 2.006-.899 2.006-2.005 0-1.107-.899-2.006-2.006-2.006H9.282c-.464 0-.906-.247-.906-.706s.442-.707.906-.707H12.7c1.889 0 3.418 1.53 3.418 3.419z"/>
                            </svg>
                            Connecter a Etsy
                        </a>
                    </div>
                @endif
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
