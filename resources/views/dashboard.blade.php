<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tableau de bord') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- Global Stats --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.stat-card
                    label="Produits"
                    :value="$totalStats['total_products']"
                    icon="products"
                    color="orange"
                />
                <x-ui.stat-card
                    label="Boutiques"
                    :value="$shops->count()"
                    icon="shop"
                    color="blue"
                />
            </div>

            {{-- Shops Grid --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Mes boutiques</h3>
                    <a href="{{ route('shops.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Ajouter une boutique
                    </a>
                </div>

                @if($shops->isEmpty())
                    <x-ui.empty-state
                        icon="shop"
                        title="Aucune boutique"
                        description="Creez votre premiere boutique pour commencer."
                        :actionUrl="route('shops.create')"
                        actionLabel="Creer une boutique"
                    />
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($shops as $shop)
                            <x-dashboard.shop-card :shop="$shop" />
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
