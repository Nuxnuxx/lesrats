<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Extension Navigateur') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Connectez votre extension Chrome pour importer des produits.') }}
        </p>
    </header>

    <div class="mt-6 space-y-6">
        {{-- Auto-connect button (detected by lesrats.js content script) --}}
        <div id="extension-connect-section">
            <div id="extension-not-detected" class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-sm text-yellow-800">
                    {{ __('Extension non detectee. Installez l\'extension LesRats puis rechargez cette page.') }}
                </p>
            </div>

            <div id="extension-detected" class="hidden">
                <div id="extension-disconnected" class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-800">{{ __('Extension detectee') }}</p>
                            <p class="text-xs text-blue-600 mt-1">{{ __('Cliquez pour connecter automatiquement.') }}</p>
                        </div>
                        <button type="button" id="btn-connect-extension"
                            class="px-4 py-2 bg-orange-500 text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition">
                            {{ __('Connecter') }}
                        </button>
                    </div>
                </div>

                <div id="extension-connected" class="hidden p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <p class="text-sm font-medium text-green-800">{{ __('Extension connectee !') }}</p>
                    </div>
                    <p class="text-xs text-green-600 mt-1">{{ __('L\'extension est prete a importer.') }}</p>
                </div>

                <div id="extension-connect-error" class="hidden p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-800" id="extension-connect-error-msg"></p>
                </div>
            </div>
        </div>

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

        {{-- Manual token creation (fallback) --}}
        <details class="text-sm">
            <summary class="cursor-pointer text-gray-500 hover:text-gray-700">{{ __('Creer un token manuellement') }}</summary>
            <div class="mt-3 space-y-4">
                {{-- Show newly created token --}}
                @if(session('new_token'))
                    <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm font-medium text-green-800 mb-2">
                            {{ __('Token cree ! Copiez-le maintenant, il ne sera plus affiche.') }}
                        </p>
                        <div class="flex items-center gap-2">
                            <code id="new-token" class="flex-1 p-2 bg-white rounded border text-sm font-mono break-all">{{ session('new_token') }}</code>
                            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('new-token').textContent).then(() => alert('Token copie !'))" class="px-3 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                                {{ __('Copier') }}
                            </button>
                        </div>
                    </div>
                @endif

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
        </details>
    </div>
</section>

@push('scripts')
<script>
(function() {
    const connectUrl = @json(route('profile.extension-connect'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Check if the extension is installed by listening for its ping
    let extensionDetected = false;

    window.addEventListener('message', function(event) {
        if (event.source !== window) return;

        // Extension announces itself
        if (event.data?.type === 'LESRATS_EXTENSION_PRESENT') {
            extensionDetected = true;
            document.getElementById('extension-not-detected').classList.add('hidden');
            document.getElementById('extension-detected').classList.remove('hidden');

            // If already connected, show that
            if (event.data.connected) {
                document.getElementById('extension-disconnected').classList.add('hidden');
                document.getElementById('extension-connected').classList.remove('hidden');
            }
        }

        // Extension confirms connection saved
        if (event.data?.type === 'LESRATS_CONNECT_SAVED') {
            document.getElementById('extension-disconnected').classList.add('hidden');
            document.getElementById('extension-connect-error').classList.add('hidden');
            document.getElementById('extension-connected').classList.remove('hidden');
        }
    });

    // Ask the extension if it's there
    window.postMessage({ type: 'LESRATS_PING' }, '*');

    // Connect button
    document.getElementById('btn-connect-extension')?.addEventListener('click', async function() {
        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Connexion...';

        try {
            const response = await fetch(connectUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            const data = await response.json();

            if (data.success && data.token) {
                // Send token to the extension via postMessage
                window.postMessage({
                    type: 'LESRATS_CONNECT',
                    token: data.token,
                    apiUrl: document.querySelector('meta[name="lesrats-api-url"]')?.content || window.location.origin,
                }, '*');
            } else {
                showConnectError('Erreur lors de la creation du token.');
            }
        } catch (e) {
            console.error('Extension connect error:', e);
            showConnectError('Erreur de connexion au serveur.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Connecter';
        }
    });

    function showConnectError(msg) {
        const el = document.getElementById('extension-connect-error');
        document.getElementById('extension-connect-error-msg').textContent = msg;
        el.classList.remove('hidden');
    }
})();
</script>
@endpush
