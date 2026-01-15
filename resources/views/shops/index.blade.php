<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Mes Boutiques') }}
            </h2>
            <a href="{{ route('shops.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Nouvelle Boutique
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if ($shops->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                    <p class="text-gray-600 mb-4">Vous n'avez pas encore de boutique.</p>
                    <a href="{{ route('shops.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        Créer ma première boutique
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($shops as $shop)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $shop->name }}</h3>
                                        <p class="text-sm text-gray-500">{{ $shop->currency }}</p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-semibold rounded {{ $shop->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $shop->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>

                                <div class="mb-4">
                                    <p class="text-sm text-gray-600">
                                        <strong>Membres:</strong> {{ $shop->members->count() }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <strong>Votre rôle:</strong>
                                        <span class="capitalize">{{ $shop->pivot->role }}</span>
                                    </p>
                                </div>

                                <div class="flex gap-2">
                                    <a href="{{ route('shops.show', $shop) }}" class="flex-1 text-center px-3 py-2 bg-blue-500 text-white text-xs font-semibold rounded hover:bg-blue-600">
                                        Voir
                                    </a>

                                    @if(session('active_shop_id') != $shop->id)
                                        <form action="{{ route('shops.switch', $shop) }}" method="POST" class="flex-1">
                                            @csrf
                                            <button type="submit" class="w-full px-3 py-2 bg-gray-500 text-white text-xs font-semibold rounded hover:bg-gray-600">
                                                Activer
                                            </button>
                                        </form>
                                    @else
                                        <span class="flex-1 text-center px-3 py-2 bg-green-500 text-white text-xs font-semibold rounded">
                                            Active
                                        </span>
                                    @endif

                                    @can('update', $shop)
                                        <a href="{{ route('shops.edit', $shop) }}" class="px-3 py-2 bg-yellow-500 text-white text-xs font-semibold rounded hover:bg-yellow-600">
                                            Éditer
                                        </a>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
