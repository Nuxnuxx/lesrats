<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Créer un utilisateur') }}
            </h2>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                &larr; {{ __('Retour') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 max-w-2xl">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <p class="text-sm text-gray-600 mb-6">
                    {{ __('Le compte est créé immédiatement avec le mot de passe choisi. Transmettez les identifiants à l\'utilisateur en privé.') }}
                </p>

                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="name" :value="__('Nom')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                      :value="old('name')" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                      :value="old('email')" required autocomplete="off" />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Mot de passe')" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                                      required autocomplete="new-password" />
                        <x-input-error class="mt-2" :messages="$errors->get('password')" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirmer le mot de passe')" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full"
                                      required autocomplete="new-password" />
                    </div>

                    <div>
                        <x-input-label for="role" :value="__('Rôle')" />
                        <select id="role" name="role"
                                class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm">
                            <option value="{{ \App\Models\User::ROLE_BETA_TESTER }}" {{ old('role') === \App\Models\User::ROLE_ADMIN ? '' : 'selected' }}>
                                {{ __('Beta tester') }}
                            </option>
                            <option value="{{ \App\Models\User::ROLE_ADMIN }}" {{ old('role') === \App\Models\User::ROLE_ADMIN ? 'selected' : '' }}>
                                {{ __('Admin') }}
                            </option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('role')" />
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <x-primary-button>{{ __('Créer le compte') }}</x-primary-button>
                        <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                            {{ __('Annuler') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
