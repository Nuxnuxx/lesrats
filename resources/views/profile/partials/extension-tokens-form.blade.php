<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Token Extension') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Gerez vos tokens pour l\'extension navigateur.') }}
        </p>
    </header>

    <div class="mt-6 space-y-6">
        {{-- Existing tokens --}}
        @if($tokens->count() > 0)
            <div class="space-y-3">
                <h3 class="text-sm font-medium text-gray-700">{{ __('Tokens actifs') }}</h3>
                @foreach($tokens as $token)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $token->name }}</p>
                            <p class="text-xs text-gray-500">
                                Cree le {{ $token->created_at->format('d/m/Y H:i') }}
                                @if($token->last_used_at)
                                    &bull; Derniere utilisation: {{ $token->last_used_at->diffForHumans() }}
                                @else
                                    &bull; Jamais utilise
                                @endif
                            </p>
                        </div>
                        <form method="post" action="{{ route('profile.revoke-token', $token) }}" class="inline">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800" onclick="return confirm('Revoquer ce token ?')">
                                {{ __('Revoquer') }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Show newly created token --}}
        @if(session('new_token'))
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm font-medium text-green-800 mb-2">
                    {{ __('Token cree ! Copiez-le maintenant, il ne sera plus affiche.') }}
                </p>
                <div class="flex items-center gap-2">
                    <code id="new-token" class="flex-1 p-2 bg-white rounded border text-sm font-mono break-all">{{ session('new_token') }}</code>
                    <button type="button" onclick="copyToken()" class="px-3 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                        {{ __('Copier') }}
                    </button>
                </div>
            </div>
            <script>
                function copyToken() {
                    const token = document.getElementById('new-token').textContent;
                    navigator.clipboard.writeText(token).then(() => {
                        alert('Token copie !');
                    });
                }
            </script>
        @endif

        {{-- Create new token --}}
        <form method="post" action="{{ route('profile.create-token') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="token_name" :value="__('Nom du token')" />
                <x-text-input id="token_name" name="token_name" type="text" class="mt-1 block w-full" 
                    placeholder="Ex: Extension Chrome" required />
                <x-input-error class="mt-2" :messages="$errors->get('token_name')" />
            </div>

            <x-primary-button>{{ __('Creer un token') }}</x-primary-button>
        </form>
    </div>
</section>
