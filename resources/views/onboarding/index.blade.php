<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bienvenue sur LesRats
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex items-center justify-center">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $step >= 1 ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                            @if($step > 1)
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                1
                            @endif
                        </div>
                        <span class="ml-2 text-sm font-medium {{ $step >= 1 ? 'text-orange-500' : 'text-gray-500' }}">Boutique</span>
                    </div>
                    <div class="w-16 h-1 mx-4 {{ $step >= 2 ? 'bg-orange-500' : 'bg-gray-200' }}"></div>
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $step >= 2 ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                            2
                        </div>
                        <span class="ml-2 text-sm font-medium {{ $step >= 2 ? 'text-orange-500' : 'text-gray-500' }}">Etsy</span>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Step 1: Create Shop -->
            @if($step === 1)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-center mb-6">
                            <h3 class="text-2xl font-bold text-gray-900">Créez votre boutique</h3>
                            <p class="mt-2 text-gray-600">Commençons par créer votre première boutique pour gérer vos produits.</p>
                        </div>

                        <form method="POST" action="{{ route('onboarding.store-shop') }}">
                            @csrf

                            <div class="mb-4">
                                <label for="name" class="block font-medium text-sm text-gray-700">Nom de la boutique</label>
                                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus 
                                    placeholder="Ma Boutique Etsy"
                                    class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm">
                                @error('name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-6">
                                <label for="currency" class="block font-medium text-sm text-gray-700">Devise</label>
                                <select id="currency" name="currency" required 
                                    class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm">
                                    <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR (Euro)</option>
                                    <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD (Dollar US)</option>
                                    <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP (Livre Sterling)</option>
                                    <option value="CAD" {{ old('currency') == 'CAD' ? 'selected' : '' }}>CAD (Dollar Canadien)</option>
                                </select>
                                @error('currency')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center justify-center">
                                <button type="submit" class="inline-flex items-center px-6 py-3 bg-orange-500 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-orange-600 focus:bg-orange-600 active:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Continuer
                                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Step 2: Connect Etsy -->
            @if($step === 2)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-center mb-6">
                            <h3 class="text-2xl font-bold text-gray-900">Connectez votre compte Etsy</h3>
                            <p class="mt-2 text-gray-600">Liez votre boutique <strong>{{ $shop->name }}</strong> à Etsy pour synchroniser vos produits.</p>
                        </div>

                        <div class="flex flex-col items-center space-y-4">
                            <!-- Etsy Logo/Icon -->
                            <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center">
                                <svg class="w-10 h-10 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8.559 2.002c-.404 0-.759.291-.833.69L6.108 12.31c-.082.435.234.847.677.847h3.648l-.96 7.155c-.08.597.404 1.119.979 1.119.341 0 .667-.174.854-.464l6.835-10.6c.27-.418-.02-.967-.513-.967h-3.752l1.808-6.622c.118-.435-.238-.876-.694-.876z"/>
                                </svg>
                            </div>

                            @if($shop->etsy_shop_id)
                                <div class="text-center">
                                    <p class="text-green-600 font-medium">Connecté à Etsy</p>
                                    <p class="text-sm text-gray-500">ID Boutique : {{ $shop->etsy_shop_id }}</p>
                                </div>
                                <a href="{{ route('onboarding.complete') }}" 
                                    class="inline-flex items-center px-6 py-3 bg-orange-500 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-orange-600">
                                    Terminer la configuration
                                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </a>
                            @else
                                <p class="text-sm text-gray-500 text-center max-w-md">
                                    En connectant Etsy, vous pourrez publier et synchroniser vos produits directement depuis l'application.
                                </p>

                                <a href="{{ route('onboarding.connect-etsy', $shop) }}" 
                                    class="inline-flex items-center px-6 py-3 bg-orange-500 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-orange-600 focus:bg-orange-600 active:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="mr-2 w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm0 22C6.486 22 2 17.514 2 12S6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/>
                                        <path d="M13 7h-2v4H7v2h4v4h2v-4h4v-2h-4z"/>
                                    </svg>
                                    Connecter à Etsy
                                </a>

                                <div class="pt-4 border-t border-gray-200 w-full text-center">
                                    <a href="{{ route('onboarding.skip') }}" class="text-sm text-gray-500 hover:text-gray-700 underline">
                                        Passer cette étape et connecter plus tard
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
