@php
    $latestExtensionVersion = config('extension.latest_version');
    $extensionDownloadUrl = url(config('extension.download_path'));
@endphp

{{-- Full-app blocker shown when the LesRats extension is missing or outdated.
     Admins bypass this gate (see layouts/app.blade.php). Guest pages don't include it. --}}
<div id="extension-gate" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
        <div class="flex items-start gap-3">
            <div class="shrink-0 mt-0.5">
                <svg class="w-7 h-7 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z"></path>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <h3 id="extension-gate-title" class="text-lg font-semibold text-gray-900">{{ __('Extension requise') }}</h3>
                <p id="extension-gate-message" class="mt-1 text-sm text-gray-600">
                    {{ __('L\'extension LesRats est requise pour utiliser l\'application.') }}
                </p>
                <div id="extension-gate-versions" class="hidden mt-3 text-xs text-gray-500 space-y-1">
                    <p><span class="font-medium">{{ __('Version installee :') }}</span> <span id="ext-gate-current-version" class="font-mono">—</span></p>
                    <p><span class="font-medium">{{ __('Version requise :') }}</span> <span class="font-mono">{{ $latestExtensionVersion }}</span></p>
                </div>
            </div>
        </div>

        <a href="{{ $extensionDownloadUrl }}" download
            class="mt-5 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"></path>
            </svg>
            <span id="extension-gate-button-label">{{ __('Telecharger l\'extension v') }}{{ $latestExtensionVersion }}</span>
        </a>

        <details class="mt-4 text-xs text-gray-600">
            <summary class="cursor-pointer font-medium text-gray-700">{{ __('Comment installer') }}</summary>
            <ol class="mt-2 space-y-1 list-decimal list-inside text-gray-600">
                <li>{{ __('Dezippez le fichier telecharge') }}</li>
                <li>{{ __('Ouvrez chrome://extensions') }}</li>
                <li>{{ __('Activez "Mode developpeur" (haut-droite)') }}</li>
                <li>{{ __('Cliquez "Charger l\'extension non empaquetee" et selectionnez le dossier dezippe') }}</li>
                <li>{{ __('Rechargez cette page') }}</li>
            </ol>
        </details>
    </div>
</div>

<script>
(function() {
    const LATEST = @json($latestExtensionVersion);
    const gate = document.getElementById('extension-gate');
    const title = document.getElementById('extension-gate-title');
    const message = document.getElementById('extension-gate-message');
    const versions = document.getElementById('extension-gate-versions');
    const currentVersionEl = document.getElementById('ext-gate-current-version');
    const buttonLabel = document.getElementById('extension-gate-button-label');

    let answered = false;

    function showMissing() {
        title.textContent = "Extension requise";
        message.textContent = "L'extension LesRats est requise pour utiliser l'application. Telechargez-la et installez-la pour continuer.";
        versions.classList.add('hidden');
        buttonLabel.textContent = "Telecharger l'extension v" + LATEST;
        gate.classList.remove('hidden');
        lockScroll();
    }

    function showOutdated(installedVersion) {
        title.textContent = "Mise a jour de l'extension requise";
        message.textContent = "Votre extension est obsolete. Mettez-la a jour pour continuer.";
        currentVersionEl.textContent = installedVersion || '< 2.2.0';
        versions.classList.remove('hidden');
        buttonLabel.textContent = "Telecharger v" + LATEST;
        gate.classList.remove('hidden');
        lockScroll();
    }

    function clearGate() {
        gate.classList.add('hidden');
        unlockScroll();
    }

    function lockScroll() { document.documentElement.style.overflow = 'hidden'; }
    function unlockScroll() { document.documentElement.style.overflow = ''; }

    window.addEventListener('message', function(event) {
        if (event.source !== window) return;
        if (event.data?.type !== 'LESRATS_EXTENSION_PRESENT') return;

        answered = true;
        const installedVersion = event.data.version;
        if (installedVersion === LATEST) {
            clearGate();
        } else {
            showOutdated(installedVersion);
        }
    });

    // Ask the extension if it's there
    window.postMessage({ type: 'LESRATS_PING' }, '*');

    // If nothing answers within 1.5s, treat as not installed.
    setTimeout(() => {
        if (!answered) showMissing();
    }, 1500);
})();
</script>
