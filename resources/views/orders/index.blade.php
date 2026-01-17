<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Commandes
                </h2>
                {{-- Shop Selector --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" 
                            class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <span>{{ $shop->name }}</span>
                        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" 
                         @click.away="open = false"
                         x-transition
                         class="absolute left-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                        @foreach($shops as $s)
                            <a href="{{ route('orders.index', ['shop_id' => $s->id]) }}" 
                               class="block px-4 py-2 text-sm {{ $s->id === $shop->id ? 'bg-orange-50 text-orange-600' : 'text-gray-700 hover:bg-gray-50' }}">
                                {{ $s->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                @if($shop->etsy_shop_id)
                    <form action="{{ route('orders.import-etsy') }}" method="POST">
                        @csrf
                        <input type="hidden" name="shop_id" value="{{ $shop->id }}">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Importer depuis Etsy
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Stats Bar --}}
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
                <x-ui.stat-card label="Total" :value="$stats['total']" />
                <x-ui.stat-card label="Nouvelles" :value="$stats['new']" color="yellow" />
                <x-ui.stat-card label="En cours" :value="$stats['in_progress']" color="blue" />
                <x-ui.stat-card label="Terminees" :value="$stats['completed']" color="green" />
                <x-ui.stat-card label="CA Aujourd'hui" :value="number_format($stats['today_revenue'], 0, ',', ' ')" />
                <x-ui.stat-card label="Profit Aujourd'hui" :value="'+' . number_format($stats['today_profit'], 0, ',', ' ')" color="green" />
            </div>

            {{-- Filters --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                <form method="GET" action="{{ route('orders.index') }}" class="flex flex-wrap items-center gap-4">
                    <input type="hidden" name="shop_id" value="{{ $shop->id }}">
                    
                    {{-- Search --}}
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <input type="text" 
                                   name="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Rechercher (client, email, ID)..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-orange-500 focus:border-orange-500">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Status Filter --}}
                    <select name="status" class="border border-gray-300 rounded-lg text-sm py-2 px-3 focus:ring-orange-500 focus:border-orange-500">
                        <option value="">Tous statuts</option>
                        <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>Nouvelles</option>
                        <option value="ordered" {{ request('status') === 'ordered' ? 'selected' : '' }}>Commandees</option>
                        <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>Expediees</option>
                        <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Livrees</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Terminees</option>
                    </select>

                    {{-- Date Filter --}}
                    <select name="date" class="border border-gray-300 rounded-lg text-sm py-2 px-3 focus:ring-orange-500 focus:border-orange-500">
                        <option value="">Toutes dates</option>
                        <option value="today" {{ request('date') === 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                        <option value="week" {{ request('date') === 'week' ? 'selected' : '' }}>Cette semaine</option>
                        <option value="month" {{ request('date') === 'month' ? 'selected' : '' }}>Ce mois</option>
                    </select>

                    {{-- Filter Button --}}
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filtrer
                    </button>

                    @if(request('search') || request('status') || request('date'))
                        <a href="{{ route('orders.index', ['shop_id' => $shop->id]) }}" class="text-sm text-gray-500 hover:text-gray-700">
                            Effacer
                        </a>
                    @endif
                </form>
            </div>

            {{-- Orders List --}}
            @if ($orders->isEmpty())
                <x-ui.empty-state 
                    icon="orders"
                    title="Aucune commande"
                    :description="request('search') || request('status') || request('date') 
                        ? 'Aucune commande ne correspond a vos filtres.' 
                        : 'Importez vos commandes depuis Etsy pour commencer.'"
                    :secondaryActionUrl="request('search') || request('status') || request('date') ? route('orders.index', ['shop_id' => $shop->id]) : null"
                    :secondaryActionLabel="request('search') || request('status') || request('date') ? 'Effacer les filtres' : null"
                >
                    @if(!request('search') && !request('status') && !request('date') && $shop->etsy_shop_id)
                        <form action="{{ route('orders.import-etsy') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="shop_id" value="{{ $shop->id }}">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Importer depuis Etsy
                            </button>
                        </form>
                    @endif
                </x-ui.empty-state>
            @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commande</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Articles</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($orders as $order)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            #{{ $order->etsy_receipt_id }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $order->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $order->customer_name }}</div>
                                        @if($order->customer_email)
                                            <div class="text-xs text-gray-500">{{ $order->customer_email }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="text-sm text-gray-900">{{ $order->items->count() }} article(s)</span>
                                            @if($order->items->where('source_type', 'aliexpress')->count() > 0)
                                                <x-ui.source-badge type="aliexpress" :showLabel="false" class="ml-2" title="Dropship" />
                                            @endif
                                            @if($order->items->where('is_digital', true)->count() > 0)
                                                <x-ui.source-badge type="printables" :showLabel="false" class="ml-1" title="Digital" />
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900">{{ $order->formatted_total }}</div>
                                        <div class="text-xs text-green-600">{{ $order->formatted_profit }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <x-ui.status-badge :status="$order->status" type="order" />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <a href="{{ route('orders.show', $order) }}" class="text-orange-600 hover:text-orange-700 font-medium">
                                            Voir
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
