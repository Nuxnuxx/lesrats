<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bienvenue sur LesRats
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                    @if (session('error_link'))
                        <a href="{{ session('error_link') }}" target="_blank" class="underline font-medium ml-1">
                            Créer une boutique sur Etsy
                        </a>
                    @endif
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8">
                    <div class="text-center">
                        <!-- Etsy Logo -->
                        <div class="mx-auto w-24 h-24 bg-orange-100 rounded-full flex items-center justify-center mb-6">
                            <svg class="w-12 h-12 text-orange-500" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M8.559 2.002c-.404 0-.759.291-.833.69L6.108 12.31c-.082.435.234.847.677.847h3.648l-.96 7.155c-.08.597.404 1.119.979 1.119.341 0 .667-.174.854-.464l6.835-10.6c.27-.418-.02-.967-.513-.967h-3.752l1.808-6.622c.118-.435-.238-.876-.694-.876z"/>
                            </svg>
                        </div>

                        <h3 class="text-2xl font-bold text-gray-900 mb-2">
                            Connectez votre boutique Etsy
                        </h3>
                        <p class="text-gray-600 mb-8 max-w-md mx-auto">
                            Pour commencer, connectez votre compte Etsy. Votre boutique sera automatiquement importée avec toutes ses informations.
                        </p>

                        <a href="{{ route('onboarding.connect-etsy') }}" 
                            class="inline-flex items-center px-8 py-4 bg-orange-500 border border-transparent rounded-lg font-semibold text-base text-white uppercase tracking-widest hover:bg-orange-600 focus:bg-orange-600 active:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-lg">
                            <svg class="w-6 h-6 mr-3" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm0 22C6.486 22 2 17.514 2 12S6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/>
                                <path d="M13 7h-2v4H7v2h4v4h2v-4h4v-2h-4z"/>
                            </svg>
                            Connecter mon compte Etsy
                        </a>

                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Ce qui va se passer :</h4>
                            <ul class="text-sm text-gray-500 space-y-2">
                                <li class="flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Vous serez redirigé vers Etsy pour autoriser l'accès
                                </li>
                                <li class="flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Votre boutique sera automatiquement créée
                                </li>
                                <li class="flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Vous pourrez gérer vos produits depuis l'application
                                </li>
                            </ul>
                        </div>

                        <div class="mt-6 text-xs text-gray-400">
                            Vous n'avez pas encore de boutique Etsy ?
                            <a href="https://www.etsy.com/sell" target="_blank" class="text-orange-500 hover:text-orange-600 underline">
                                Créez-en une ici
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
