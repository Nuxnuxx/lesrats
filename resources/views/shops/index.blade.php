<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Mes Boutiques
            </h2>
            <a href="{{ route('shops.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouvelle boutique
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            @if ($shops->isEmpty())
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune boutique</h3>
                    <p class="text-gray-500 mb-6">Creez votre premiere boutique pour commencer.</p>
                    <a href="{{ route('shops.create') }}" 
                       class="inline-flex items-center px-6 py-3 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Creer une boutique
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($shops as $shop)
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                            <div class="p-6">
                                {{-- Header --}}
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-gray-900 truncate">
                                            {{ $shop->name }}
                                        </h3>
                                        <div class="flex items-center mt-1 space-x-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $shop->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ $shop->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                            <span class="text-xs text-gray-500">{{ $shop->currency }}</span>
                                        </div>
                                    </div>
                                    
                                    {{-- Shop icon --}}
                                    <div class="flex-shrink-0 ml-3">
                                        <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                {{-- Stats grid --}}
                                <div class="grid grid-cols-2 gap-3 mb-4">
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <div class="text-2xl font-bold text-gray-900">{{ $shop->products_count }}</div>
                                        <div class="text-xs text-gray-500 uppercase tracking-wide">Produits</div>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <div class="text-2xl font-bold text-gray-900">{{ $shop->orders_count }}</div>
                                        <div class="text-xs text-gray-500 uppercase tracking-wide">Commandes</div>
                                    </div>
                                    <div class="bg-blue-50 rounded-lg p-3">
                                        <div class="text-2xl font-bold text-blue-600">{{ $shop->todays_orders_count }}</div>
                                        <div class="text-xs text-blue-600 uppercase tracking-wide">Aujourd'hui</div>
                                    </div>
                                    <div class="bg-green-50 rounded-lg p-3">
                                        <div class="text-lg font-bold text-green-600">{{ number_format($shop->total_revenue ?? 0, 0, ',', ' ') }}</div>
                                        <div class="text-xs text-green-600 uppercase tracking-wide">{{ $shop->currency }}</div>
                                    </div>
                                </div>

                                {{-- Role badge --}}
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-xs text-gray-500">
                                        Votre role: 
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize
                                            @if($shop->pivot->role === 'owner') bg-purple-100 text-purple-800
                                            @elseif($shop->pivot->role === 'admin') bg-blue-100 text-blue-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $shop->pivot->role }}
                                        </span>
                                    </span>
                                    <span class="text-xs text-gray-400">{{ $shop->members->count() }} membre(s)</span>
                                </div>

                                {{-- Actions --}}
                                <div class="flex gap-2">
                                    <a href="{{ route('shops.edit', $shop) }}" 
                                       class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        Gerer
                                    </a>

                                    @if(session('active_shop_id') != $shop->id)
                                        <form action="{{ route('shops.switch', $shop) }}" method="POST">
                                            @csrf
                                            <button type="submit" 
                                                    class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                                </svg>
                                                Activer
                                            </button>
                                        </form>
                                    @else
                                        <span class="inline-flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-lg text-sm font-medium">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Active
                                        </span>
                                    @endif


                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
