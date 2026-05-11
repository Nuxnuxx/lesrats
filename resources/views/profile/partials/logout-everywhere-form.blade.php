<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Déconnexion globale
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Termine toutes vos autres sessions et révoque tous vos tokens API
            (y compris l'extension navigateur). À utiliser en cas de doute :
            appareil perdu, mot de passe partagé, connexion suspecte.
            Vous resterez connecté sur cet appareil.
        </p>
    </header>

    @if (session('status') === 'logged-out-everywhere')
        <p class="text-sm font-medium text-green-700">
            Toutes les autres sessions et tous les tokens API ont été révoqués.
        </p>
    @endif

    <x-secondary-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-logout-everywhere')"
    >Déconnecter tous les appareils</x-secondary-button>

    <x-modal name="confirm-logout-everywhere" :show="$errors->get('password') && request()->routeIs('profile.edit')" focusable>
        <form method="post" action="{{ route('profile.logout-everywhere') }}" class="p-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900">
                Confirmer la déconnexion globale
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Entrez votre mot de passe actuel pour confirmer. Toutes les autres
                sessions et tokens API seront immédiatement invalidés.
            </p>

            <div class="mt-6">
                <x-input-label for="logout_everywhere_password" value="Mot de passe actuel" class="sr-only" />

                <x-text-input
                    id="logout_everywhere_password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Mot de passe actuel"
                />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Annuler
                </x-secondary-button>

                <x-primary-button class="ms-3">
                    Déconnecter partout
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</section>
