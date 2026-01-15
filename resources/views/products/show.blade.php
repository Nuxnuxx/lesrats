<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $product->title }}
            </h2>
            <a href="{{ route('products.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                Retour
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations du produit</h3>

                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Titre</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $product->title }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">SKU</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $product->sku ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Prix</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ number_format($product->price, 2) }} {{ $product->shop->currency }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Quantité</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $product->quantity }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Statut</dt>
                            <dd class="mt-1">
                                <span class="px-2 py-1 text-xs font-semibold rounded {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $product->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Auto-sync Etsy</dt>
                            <dd class="mt-1">
                                <span class="px-2 py-1 text-xs font-semibold rounded {{ $product->auto_sync ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $product->auto_sync ? 'Activé' : 'Désactivé' }}
                                </span>
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $product->description ?? 'Aucune description' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Intégration Etsy</h3>

                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">ID Listing Etsy</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $product->etsy_listing_id ?? 'Non synchronisé' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">État Etsy</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $product->etsy_state ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Dernière sync</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $product->etsy_synced_at ? $product->etsy_synced_at->format('d/m/Y H:i') : 'Jamais' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Statut de synchronisation</dt>
                            <dd class="mt-1">
                                @if($product->isSyncedWithEtsy())
                                    @if($product->needsSync())
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800">
                                            Mise à jour requise
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">
                                            À jour
                                        </span>
                                    @endif
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800">
                                        Non synchronisé
                                    </span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if($product->shop->etsy_shop_id)
                        <div class="mt-4">
                            <form action="{{ route('products.sync-etsy', $product) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-orange-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-600">
                                    {{ $product->isSyncedWithEtsy() ? 'Resynchroniser avec Etsy' : 'Publier sur Etsy' }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                            <p class="text-sm text-yellow-800">
                                Connectez votre boutique à Etsy pour synchroniser ce produit.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            @if($product->aliexpress_url)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">AliExpress</h3>
                        <dl class="grid grid-cols-1 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">URL du produit</dt>
                                <dd class="mt-1 text-sm">
                                    <a href="{{ $product->aliexpress_url }}" target="_blank" class="text-blue-600 hover:text-blue-900">
                                        {{ $product->aliexpress_url }}
                                    </a>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @endif

            @can('update', $product->shop)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                        <div class="flex gap-3">
                            <a href="{{ route('products.edit', $product) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Modifier le produit
                            </a>

                            <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
