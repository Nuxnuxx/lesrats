<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Bienvenue ! Configurons votre compte') }}
            </h2>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 underline">
                    {{ __('Se deconnecter') }}
                </button>
            </form>
        </div>
    </x-slot>

    @php
        // Etat de chaque etape : 'done' | 'active' | 'locked'.
        // Une etape devient 'active' uniquement quand la precedente est 'done'.
        $step1State = $hasShop ? 'done' : 'active';
        $step2State = $hasExtension ? 'done' : ($hasShop ? 'active' : 'locked');
        $step3State = $hasProduct ? 'done' : ($hasExtension ? 'active' : 'locked');

        $doneSteps = ($hasShop ? 1 : 0) + ($hasExtension ? 1 : 0) + ($hasProduct ? 1 : 0);
        $totalSteps = 3;
        $progressPct = (int) round($doneSteps / $totalSteps * 100);
    @endphp

    <div class="py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow rounded-lg p-6">
                <p class="text-sm text-gray-600">
                    {{ __('Creez votre boutique, connectez l\'extension, puis importez un produit. Vous pouvez aussi passer et connecter l\'extension plus tard.') }}
                </p>
                <div class="mt-4 flex items-center gap-2 text-sm text-gray-500">
                    <span>{{ $doneSteps }} / {{ $totalSteps }} terminees</span>
                    <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-orange-500 transition-all" style="width: {{ $progressPct }}%"></div>
                    </div>
                </div>
            </div>

            {{-- ===== Step 1 — Create shop ===== --}}
            @php $state = $step1State; @endphp
            <div class="bg-white shadow rounded-lg p-6
                {{ $state === 'done' ? 'border-l-4 border-green-500' : '' }}
                {{ $state === 'locked' ? 'opacity-60' : '' }}">
                <div class="flex items-start gap-4">
                    <div class="shrink-0 mt-1">
                        @include('onboarding.partials.step-marker', ['state' => $state, 'number' => 1])
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold {{ $state === 'locked' ? 'text-gray-500' : 'text-gray-900' }}">
                            {{ __('Creez votre premiere boutique') }}
                        </h3>
                        @if($state === 'done')
                            <p class="text-sm text-gray-600 mt-1">
                                {{ __('Boutique active :') }} <span class="font-medium text-gray-900">{{ $ownedShop->name }}</span>
                            </p>
                            <a href="{{ route('shops.edit', $ownedShop) }}" class="inline-block mt-3 text-sm text-orange-600 hover:text-orange-700 font-medium">
                                {{ __('Modifier la boutique') }} &rarr;
                            </a>
                        @else
                            <p class="text-sm text-gray-600 mt-1">
                                {{ __('Donnez un nom a votre boutique, choisissez la devise et le type de produits.') }}
                            </p>
                            <a href="{{ route('shops.create') }}"
                               class="inline-flex items-center mt-4 px-4 py-2 bg-orange-600 text-white text-sm font-semibold rounded-lg hover:bg-orange-700 transition">
                                {{ __('Creer ma boutique') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ===== Step 2 — Install + connect extension ===== --}}
            @php $state = $step2State; @endphp
            <div class="bg-white shadow rounded-lg p-6
                {{ $state === 'done' ? 'border-l-4 border-green-500' : '' }}
                {{ $state === 'locked' ? 'opacity-60' : '' }}">
                <div class="flex items-start gap-4">
                    <div class="shrink-0 mt-1">
                        @include('onboarding.partials.step-marker', ['state' => $state, 'number' => 2])
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold {{ $state === 'locked' ? 'text-gray-500' : 'text-gray-900' }}">
                            {{ __('Connectez l\'extension navigateur') }}
                        </h3>

                        @if($state === 'locked')
                            <p class="text-sm text-gray-500 mt-1">
                                {{ __('Terminez l\'etape 1 pour debloquer l\'installation de l\'extension.') }}
                            </p>
                        @elseif($state === 'done')
                            <p class="text-sm text-gray-600 mt-1">
                                {{ __('Extension connectee a votre compte.') }}
                            </p>
                        @else
                            <p class="text-sm text-gray-600 mt-1">
                                {{ __('L\'extension LesRats importe les produits depuis AliExpress / Etsy.') }}
                            </p>

                            <div class="mt-4 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <div class="shrink-0 w-6 h-6 rounded-full bg-orange-600 text-white text-xs font-bold flex items-center justify-center">1</div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-orange-900">{{ __('Telechargez et installez l\'extension') }}</p>
                                        <a href="{{ route('onboarding.download-extension') }}"
                                           class="inline-flex items-center mt-2 px-4 py-2 bg-orange-600 text-white text-sm font-semibold rounded-lg hover:bg-orange-700 transition shadow-sm">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/>
                                            </svg>
                                            {{ __('Telecharger l\'extension (.zip)') }}
                                        </a>
                                        <p class="text-xs text-orange-900 mt-2 leading-relaxed">
                                            {{ __('Decompressez le ZIP, ouvrez') }}
                                            <code class="px-1 py-0.5 bg-white border border-orange-200 rounded text-xs font-mono">chrome://extensions</code>,
                                            {{ __('activez le mode developpeur, puis cliquez sur "Charger l\'extension non empaquetee" et selectionnez le dossier.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <div class="shrink-0 w-6 h-6 rounded-full bg-gray-600 text-white text-xs font-bold flex items-center justify-center">2</div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-gray-900 mb-3">{{ __('Connectez l\'extension a votre compte') }}</p>
                                        @include('profile.partials.extension-tokens-form')
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ===== Step 3 — Import first product ===== --}}
            @php $state = $step3State; @endphp
            <div class="bg-white shadow rounded-lg p-6
                {{ $state === 'done' ? 'border-l-4 border-green-500' : '' }}
                {{ $state === 'locked' ? 'opacity-60' : '' }}">
                <div class="flex items-start gap-4">
                    <div class="shrink-0 mt-1">
                        @include('onboarding.partials.step-marker', ['state' => $state, 'number' => 3])
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-base font-semibold {{ $state === 'locked' ? 'text-gray-500' : 'text-gray-900' }}">
                                {{ __('Importez votre premier produit') }}
                            </h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                {{ __('Optionnel') }}
                            </span>
                        </div>

                        @if($state === 'locked')
                            <p class="text-sm text-gray-500 mt-1">
                                {{ __('Terminez l\'etape 2 pour debloquer l\'import de produits.') }}
                            </p>
                        @elseif($state === 'done')
                            <p class="text-sm text-gray-600 mt-1">
                                {{ __('Premier produit importe. Vous etes pret a publier sur Etsy.') }}
                            </p>
                        @else
                            <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                                {{ __('Ouvrez') }}
                                <a href="https://www.aliexpress.com/" target="_blank" rel="noopener noreferrer"
                                   class="font-semibold text-orange-600 underline hover:text-orange-700">AliExpress &rarr;</a>,
                                {{ __('cherchez un article. En bas a droite, la fenetre LesRats affiche') }}
                                <span class="font-semibold">&laquo;&nbsp;{{ __('Cherche un article') }}&nbsp;&raquo;</span>
                                — {{ __('ouvrez une fiche produit, cliquez sur le rat, puis sur') }}
                                <span class="font-semibold">{{ __('Importer vers LesRats') }}</span>.
                            </p>
                            <div class="mt-3 flex items-center gap-3">
                                <a href="https://www.aliexpress.com/" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-sm font-semibold rounded-lg hover:bg-orange-700 transition">
                                    {{ __('Aller sur AliExpress') }}
                                </a>
                                <button type="button" onclick="window.location.reload()"
                                        class="text-sm text-gray-500 hover:text-gray-700 underline">
                                    {{ __('J\'ai importe un produit, verifier') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Complete button — debloque des que les 2 etapes principales sont faites --}}
            <div class="bg-white shadow rounded-lg p-6">
                <form method="POST" action="{{ route('onboarding.complete') }}">
                    @csrf
                    <button type="submit"
                            @disabled(! ($hasShop && $hasExtension))
                            class="w-full px-4 py-3 bg-orange-600 text-white text-sm font-semibold rounded-lg hover:bg-orange-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        @if($hasShop && $hasExtension)
                            {{ __('Terminer et acceder au tableau de bord') }}
                        @else
                            {{ __('Terminez les etapes 1 et 2 pour continuer') }}
                        @endif
                    </button>
                    @if($hasShop && $hasExtension && ! $hasProduct)
                        <p class="text-xs text-gray-500 text-center mt-3">
                            {{ __('Astuce : importez un produit maintenant pour aller directement sur sa fiche.') }}
                        </p>
                    @endif
                </form>

                {{-- Echappatoire : passer sans connecter l'extension (connectable plus tard via le profil) --}}
                <form method="POST" action="{{ route('onboarding.skip') }}" class="mt-3">
                    @csrf
                    <button type="submit"
                            class="w-full text-center text-sm text-gray-500 hover:text-gray-700 underline">
                        {{ __('Passer pour l\'instant — je connecterai l\'extension plus tard') }}
                    </button>
                </form>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        // Reload after the extension confirms it received the token, so Step 2 flips to "done".
        window.addEventListener('message', function(event) {
            if (event.source !== window) return;
            if (event.data?.type === 'LESRATS_CONNECT_SAVED') {
                setTimeout(() => window.location.reload(), 800);
            }
        });

        @if(! $hasProduct && $hasExtension)
        // L'utilisateur importe son premier produit depuis un autre onglet (AliExpress).
        // Des qu'il revient sur cet onglet, on recharge pour faire passer l'etape 3.
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                window.location.reload();
            }
        });
        @endif
    </script>
    @endpush
</x-app-layout>
