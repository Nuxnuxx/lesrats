<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>LesRats — Etsy en moins de manuel</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/logo.png">
    <link rel="apple-touch-icon" href="/logo-192.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif; }
        .grain {
            background-image:
                radial-gradient(circle at 20% 30%, rgba(249, 115, 22, 0.08) 0%, transparent 35%),
                radial-gradient(circle at 80% 70%, rgba(249, 115, 22, 0.06) 0%, transparent 40%);
        }
    </style>
</head>
<body class="bg-[#FDFDFC] text-[#1b1b18] min-h-screen flex flex-col antialiased">

    {{-- Top nav --}}
    <header class="w-full">
        <div class="mx-auto max-w-5xl px-6 py-5 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 text-lg font-semibold">
                <span class="text-2xl leading-none">🐀</span>
                <span>LesRats</span>
                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-orange-100 text-orange-700">beta</span>
            </a>

            <nav class="flex items-center gap-2 sm:gap-3 text-sm">
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="inline-flex items-center px-4 py-2 rounded-md bg-[#1b1b18] text-white font-medium hover:bg-black transition">
                        Dashboard →
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center px-4 py-2 rounded-md text-[#1b1b18] hover:bg-gray-100 transition">
                        Se connecter
                    </a>
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center px-4 py-2 rounded-md bg-[#1b1b18] text-white font-medium hover:bg-black transition">
                        J'ai un code
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    {{-- Hero --}}
    <main class="flex-1 flex items-center justify-center grain">
        <div class="mx-auto max-w-3xl px-6 py-16 text-center">

            {{-- Beta badge --}}
            <div class="inline-flex items-center gap-2 px-3 py-1 mb-6 rounded-full bg-white border border-gray-200 text-xs text-gray-600 shadow-sm">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-orange-500"></span>
                </span>
                Beta privée · sur invitation
            </div>

            {{-- Mascot --}}
            <div class="text-7xl sm:text-8xl mb-4 select-none" aria-hidden="true">🐀</div>

            {{-- Headline --}}
            <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight leading-[1.05]">
                Etsy, mais <span class="text-orange-500">sans la corvée</span>.
            </h1>

            <p class="mt-5 text-base sm:text-lg text-gray-600 max-w-xl mx-auto leading-relaxed">
                Scrape, optimise et liste tes produits en quelques secondes.
                Pas de Photoshop, pas de SEO à la main, pas de remplir 47 champs à 2h du mat.
            </p>

            {{-- CTAs --}}
            <div class="mt-9 flex flex-col sm:flex-row items-center justify-center gap-3">
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-md bg-[#1b1b18] text-white font-semibold shadow-sm hover:bg-black hover:shadow-md transition">
                        Aller au dashboard
                        <span class="ml-2">→</span>
                    </a>
                @else
                    <a href="{{ route('register') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-md bg-[#1b1b18] text-white font-semibold shadow-sm hover:bg-black hover:shadow-md transition">
                        J'ai un code d'invitation
                        <span class="ml-2">→</span>
                    </a>
                    <a href="{{ route('login') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-md border border-gray-300 text-[#1b1b18] hover:border-[#1b1b18] hover:bg-white transition font-medium">
                        Se connecter
                    </a>
                @endauth
            </div>

            <p class="mt-5 text-xs text-gray-500">
                Pas de code ? Demande-le sur
                <a href="https://les-rats-landing-f9z4.vercel.app/#cta" class="underline hover:text-orange-600" target="_blank" rel="noopener">la page d'accueil</a>.
            </p>

            {{-- Stat strip --}}
            <div class="mt-14 grid grid-cols-3 gap-4 sm:gap-8 max-w-xl mx-auto">
                <div class="text-center">
                    <div class="text-2xl sm:text-3xl font-bold text-[#1b1b18]">75</div>
                    <div class="text-[11px] sm:text-xs text-gray-500 uppercase tracking-wider mt-1">photos IA</div>
                </div>
                <div class="text-center border-x border-gray-200">
                    <div class="text-2xl sm:text-3xl font-bold text-[#1b1b18]">0€</div>
                    <div class="text-[11px] sm:text-xs text-gray-500 uppercase tracking-wider mt-1">pendant la beta</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl sm:text-3xl font-bold text-[#1b1b18]">~30s</div>
                    <div class="text-[11px] sm:text-xs text-gray-500 uppercase tracking-wider mt-1">par produit</div>
                </div>
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="border-t border-gray-100">
        <div class="mx-auto max-w-5xl px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-500">
            <div>© {{ date('Y') }} LesRats · fait à la main 🐀</div>
            <div class="flex items-center gap-4">
                <a href="mailto:lesratsss@protonmail.com" class="hover:text-[#1b1b18] transition">Contact</a>
                <span>built in 🥖 France</span>
            </div>
        </div>
    </footer>

</body>
</html>
