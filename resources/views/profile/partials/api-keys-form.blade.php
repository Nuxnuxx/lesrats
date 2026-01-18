<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Cles API') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Configurez vos cles API pour les services externes.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.update-api-keys') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="fal_api_key" :value="__('Cle API Fal.ai')" />
            <div class="mt-1 flex gap-2">
                <x-text-input id="fal_api_key" name="fal_api_key" type="password" class="block w-full" 
                    placeholder="{{ auth()->user()->fal_api_key ? 'Cle configuree (laisser vide pour garder)' : 'Entrez votre cle API Fal.ai' }}" />
            </div>
            @if(auth()->user()->fal_api_key)
                <p class="mt-1 text-xs text-green-600">
                    <svg class="inline w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Cle API configuree. Laissez vide pour conserver ou entrez une nouvelle cle pour la remplacer.
                </p>
            @else
                <p class="mt-1 text-xs text-gray-500">
                    Utilisee pour la transformation d'images avec l'IA. 
                    <a href="https://fal.ai/dashboard/keys" target="_blank" class="text-orange-600 hover:underline">Obtenir une cle</a>
                </p>
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('fal_api_key')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Enregistrer') }}</x-primary-button>

            @if (session('status') === 'api-keys-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Enregistre.') }}</p>
            @endif
        </div>
    </form>
</section>
