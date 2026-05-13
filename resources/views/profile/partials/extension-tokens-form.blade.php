@php
    $latestExtensionVersion = config('extension.latest_version');
    $extensionDownloadUrl = url(config('extension.download_path'));
@endphp
<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Extension Navigateur') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Connectez votre extension Chrome pour importer des produits.') }}
        </p>
    </header>

    {{-- Forced update modal — shown when installed extension version differs from server version --}}
    <div id="extension-update-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-start gap-3">
                <div class="shrink-0 mt-0.5">
                    <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Mise a jour de l\'extension requise') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('Votre extension est obsolete. Mettez-la a jour pour continuer.') }}
                    </p>
                    <div class="mt-3 text-xs text-gray-500 space-y-1">
                        <p><span class="font-medium">{{ __('Version installee :') }}</span> <span id="ext-current-version" class="font-mono">—</span></p>
                        <p><span class="font-medium">{{ __('Version requise :') }}</span> <span class="font-mono">{{ $latestExtensionVersion }}</span></p>
                    </div>
                </div>
            </div>

            <a href="{{ $extensionDownloadUrl }}" download
                class="mt-5 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"></path>
                </svg>
                {{ __('Telecharger v') }}{{ $latestExtensionVersion }}
            </a>

            <details class="mt-4 text-xs text-gray-600">
                <summary class="cursor-pointer font-medium text-gray-700">{{ __('Comment installer la mise a jour') }}</summary>
                <ol class="mt-2 space-y-1 list-decimal list-inside text-gray-600">
                    <li>{{ __('Dezippez le fichier telecharge') }}</li>
                    <li>{{ __('Ouvrez chrome://extensions') }}</li>
                    <li>{{ __('Cliquez sur l\'icone de recharge de l\'extension LesRats (ou supprimez puis "Charger l\'extension non empaquetee" pointee sur le dossier)') }}</li>
                    <li>{{ __('Rechargez cette page') }}</li>
                </ol>
            </details>
        </div>
    </div>

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
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <p class="text-sm font-medium text-green-800">{{ __('Extension connectee !') }}</p>
                            </div>
                            <p class="text-xs text-green-600 mt-1">{{ __('L\'extension est prete a importer.') }}</p>
                        </div>
                        <button type="button" id="btn-reconnect-extension"
                            class="shrink-0 px-3 py-1.5 bg-white border border-green-600 text-green-700 text-xs font-semibold rounded-lg hover:bg-green-50 transition"
                            title="Genere un nouveau token et le pousse vers l'extension. Utile si l'extension s'est deconnectee ou si vous changez d'appareil.">
                            {{ __('Reconnecter') }}
                        </button>
                    </div>
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
    const latestExtensionVersion = @json($latestExtensionVersion);
    // Server-side : does THIS user already have an "Extension Auto-Connect" token ?
    // L'extension peut signaler connected=true avec un token d'un autre compte ;
    // dans ce cas on force l'UI sur "a connecter" pour que le user re-paire ce compte.
    const serverHasToken = @json($user?->hasExtensionToken() ?? false);

    // Check if the extension is installed by listening for its ping
    let extensionDetected = false;

    function showUpdateModal(currentVersion) {
        const modal = document.getElementById('extension-update-modal');
        const versionEl = document.getElementById('ext-current-version');
        if (versionEl) versionEl.textContent = currentVersion || '< 2.2.0';
        modal.classList.remove('hidden');
        // Hide the connect UI behind the modal so it can't be used
        document.getElementById('extension-connect-section').classList.add('hidden');
    }

    window.addEventListener('message', function(event) {
        if (event.source !== window) return;

        // Extension announces itself
        if (event.data?.type === 'LESRATS_EXTENSION_PRESENT') {
            extensionDetected = true;
            document.getElementById('extension-not-detected').classList.add('hidden');
            document.getElementById('extension-detected').classList.remove('hidden');

            // Force update if the installed extension version differs from the server's
            // expected version. Pre-2.2.0 extensions don't send a version field — those
            // are treated as outdated as well.
            const installedVersion = event.data.version;
            if (installedVersion !== latestExtensionVersion) {
                showUpdateModal(installedVersion);
                return;
            }

            // Green "connected" UI only if BOTH sides agree : extension has a token AND
            // the server has an Extension Auto-Connect token for this user. Sinon on garde
            // le bouton "Connecter" visible pour pairer correctement ce compte.
            if (event.data.connected && serverHasToken) {
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

    // Shared connect handler — used by both "Connecter" (initial) and "Reconnecter" (refresh).
    // Le backend (ProfileController::createExtensionToken) révoque automatiquement les
    // anciens tokens "Extension Auto-Connect" du user avant d'en créer un nouveau,
    // donc reconnecter = renouveler proprement sans accumuler de tokens.
    async function performConnect(btn, busyLabel, idleLabel) {
        btn.disabled = true;
        btn.textContent = busyLabel;
        document.getElementById('extension-connect-error').classList.add('hidden');

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
                window.postMessage({
                    type: 'LESRATS_CONNECT',
                    token: data.token,
                    apiUrl: document.querySelector('meta[name="lesrats-api-url"]')?.content || window.location.origin,
                    isAdmin: !!data.is_admin,
                }, '*');
            } else {
                showConnectError('Erreur lors de la creation du token.');
            }
        } catch (e) {
            console.error('Extension connect error:', e);
            showConnectError('Erreur de connexion au serveur.');
        } finally {
            btn.disabled = false;
            btn.textContent = idleLabel;
        }
    }

    document.getElementById('btn-connect-extension')?.addEventListener('click', function() {
        performConnect(this, 'Connexion...', 'Connecter');
    });

    document.getElementById('btn-reconnect-extension')?.addEventListener('click', function() {
        performConnect(this, 'Reconnexion...', 'Reconnecter');
    });

    function showConnectError(msg) {
        const el = document.getElementById('extension-connect-error');
        document.getElementById('extension-connect-error-msg').textContent = msg;
        el.classList.remove('hidden');
    }
})();
</script>
@endpush
