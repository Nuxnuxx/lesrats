<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Connexions Etsy
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Gérez les connexions Etsy de vos boutiques. Vous pouvez connecter plusieurs comptes Etsy.
        </p>
    </header>

    @if (session('error'))
        <div class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ session('error') }}
            @if (session('error_link'))
                <a href="{{ session('error_link') }}" target="_blank" class="underline font-medium ml-1">
                    Créer une boutique sur Etsy
                </a>
            @endif
        </div>
    @endif

    <div class="mt-6 space-y-4">
        @forelse($shops as $shop)
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full {{ $shop->etsy_shop_id ? 'bg-green-100' : 'bg-gray-200' }} flex items-center justify-center">
                            @if($shop->etsy_shop_id)
                                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                            @endif
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">{{ $shop->name }}</h3>
                        @if($shop->etsy_shop_id)
                            <p class="text-xs text-gray-500">
                                Connecté - ID: {{ $shop->etsy_shop_id }}
                                @if($shop->etsy_token_expires_at)
                                    <span class="ml-2 {{ $shop->etsy_token_expires_at->isPast() ? 'text-red-500' : 'text-green-500' }}">
                                        {{ $shop->etsy_token_expires_at->isPast() ? '(Expiré)' : '(Actif)' }}
                                    </span>
                                @endif
                            </p>
                        @else
                            <p class="text-xs text-gray-500">Non connecté à Etsy</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    @if($shop->etsy_shop_id)
                        @if($shop->etsy_token_expires_at && $shop->etsy_token_expires_at->isPast())
                            <a href="{{ route('etsy.connect', $shop) }}" 
                                class="inline-flex items-center px-3 py-1.5 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-600">
                                Reconnecter
                            </a>
                        @endif
                        <form action="{{ route('etsy.disconnect', $shop) }}" method="POST" 
                            onsubmit="return confirm('Êtes-vous sûr de vouloir déconnecter cette boutique d\'Etsy ?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                Déconnecter
                            </button>
                        </form>
                    @else
                        <a href="{{ route('etsy.connect', $shop) }}" 
                            class="inline-flex items-center px-3 py-1.5 bg-orange-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-600">
                            Reconnecter
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune boutique connectée</h3>
                <p class="mt-1 text-sm text-gray-500">Connectez votre première boutique Etsy.</p>
            </div>
        @endforelse

        <!-- Add another Etsy shop -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-medium text-gray-900 mb-4">Ajouter une autre boutique Etsy</h3>
            
            <!-- Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <h4 class="text-xs font-semibold text-blue-800 mb-2">Comment obtenir vos identifiants API Etsy :</h4>
                <ol class="text-xs text-blue-700 space-y-1 list-decimal list-inside">
                    <li>Allez sur <a href="https://www.etsy.com/developers/your-apps" target="_blank" class="underline font-medium">etsy.com/developers/your-apps</a></li>
                    <li>Cliquez sur "Create a new app"</li>
                    <li>Copiez le "Keystring" (Client ID) et le "Shared secret" (Client Secret)</li>
                </ol>
            </div>

            <form method="POST" action="{{ route('profile.connect-etsy') }}" class="space-y-4">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Client ID -->
                    <div>
                        <x-input-label for="profile_etsy_client_id" value="Etsy Client ID" class="text-xs" />
                        <x-text-input 
                            id="profile_etsy_client_id" 
                            name="etsy_client_id" 
                            type="text" 
                            class="mt-1 block w-full text-sm" 
                            :value="old('etsy_client_id')"
                            required
                            placeholder="abc123def456..."
                        />
                        <x-input-error :messages="$errors->get('etsy_client_id')" class="mt-1" />
                    </div>

                    <!-- Client Secret -->
                    <div>
                        <x-input-label for="profile_etsy_client_secret" value="Etsy Client Secret" class="text-xs" />
                        <x-text-input 
                            id="profile_etsy_client_secret" 
                            name="etsy_client_secret" 
                            type="password" 
                            class="mt-1 block w-full text-sm" 
                            required
                            placeholder="xyz789..."
                        />
                        <x-input-error :messages="$errors->get('etsy_client_secret')" class="mt-1" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-orange-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-600 focus:bg-orange-600 active:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Connecter une nouvelle boutique
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
