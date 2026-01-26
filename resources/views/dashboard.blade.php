<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tableau de bord') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            {{-- Global Stats Summary --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-ui.stat-card 
                    label="Produits" 
                    :value="$totalStats['total_products']" 
                    icon="products" 
                    color="orange" 
                />
                <x-ui.stat-card 
                    label="Commandes" 
                    :value="$totalStats['total_orders']" 
                    icon="orders" 
                    color="blue" 
                />
                <x-ui.stat-card 
                    label="Revenus totaux" 
                    :value="number_format($totalStats['total_revenue'], 0, ',', ' ') . ' EUR'" 
                    icon="revenue" 
                    color="green" 
                />
                <x-ui.stat-card 
                    label="Profit total" 
                    :value="($totalStats['total_profit'] >= 0 ? '+' : '') . number_format($totalStats['total_profit'], 0, ',', ' ') . ' EUR'" 
                    icon="profit" 
                    :color="$totalStats['total_profit'] >= 0 ? 'green' : 'red'" 
                />
            </div>

            {{-- Shops Grid --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Mes boutiques</h3>
                    <a href="{{ route('shops.create') }}" 
                       class="text-sm text-orange-600 hover:text-orange-700 font-medium">
                        + Ajouter une boutique
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

            {{-- Two Column Layout for Orders and Attention Items --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                {{-- Today's Orders --}}
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Commandes du jour
                            @if($totalStats['today_orders'] > 0)
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $totalStats['today_orders'] }}
                                </span>
                            @endif
                        </h3>
                        @if($totalStats['today_revenue'] > 0)
                            <span class="text-sm font-medium text-green-600">
                                +{{ number_format($totalStats['today_revenue'], 2, ',', ' ') }} EUR
                            </span>
                        @endif
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        @if($todaysOrders->isEmpty())
                            <div class="p-6 text-center">
                                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="text-gray-500 text-sm">Aucune commande aujourd'hui</p>
                            </div>
                        @else
                            <ul class="divide-y divide-gray-200">
                                @foreach($todaysOrders as $order)
                                    <li class="p-4 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-sm font-medium text-gray-900 truncate">
                                                        {{ $order->customer_name }}
                                                    </span>
                                                    <x-ui.status-badge :status="$order->status" type="order" />
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    {{ $order->shop->name }} &bull; {{ $order->items->count() }} article(s)
                                                </p>
                                            </div>
                                            <div class="text-right ml-4">
                                                <p class="text-sm font-semibold text-gray-900">{{ $order->formatted_total }}</p>
                                                <p class="text-xs text-green-600">{{ $order->formatted_profit }}</p>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                {{-- Products Needing Attention --}}
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Produits a traiter
                            @if($productsNeedingAttention->isNotEmpty())
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                    {{ $productsNeedingAttention->count() }}
                                </span>
                            @endif
                        </h3>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        @if($productsNeedingAttention->isEmpty())
                            <div class="p-6 text-center">
                                <svg class="w-10 h-10 text-green-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-gray-500 text-sm">Tous vos produits sont en ordre</p>
                            </div>
                        @else
                            <ul class="divide-y divide-gray-200">
                                @foreach($productsNeedingAttention as $product)
                                    <li class="p-4 hover:bg-gray-50 transition-colors">
                                        <a href="{{ route('products.edit', $product) }}" class="flex items-center justify-between">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    {{ $product->title }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    {{ $product->shop->name }}
                                                </p>
                                            </div>
                                            <div class="ml-4 flex-shrink-0">
                                                <x-ui.source-badge :type="$product->source_type ?? 'manual'" />
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Orders Needing Supplier Action --}}
            @if($ordersNeedingAction->isNotEmpty())
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <span class="inline-flex items-center">
                                <svg class="w-5 h-5 text-amber-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                Commandes a passer chez le fournisseur
                            </span>
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                {{ $ordersNeedingAction->count() }}
                            </span>
                        </h3>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-amber-200 overflow-hidden">
                        <ul class="divide-y divide-gray-200">
                            @foreach($ordersNeedingAction as $order)
                                <li class="p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $order->customer_name }}</span>
                                            <span class="text-xs text-gray-500 ml-2">{{ $order->shop->name }}</span>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900">{{ $order->formatted_total }}</span>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        @foreach($order->items->where('source_type', 'aliexpress') as $item)
                                            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm text-gray-900 truncate">{{ $item->title }}</p>
                                                    <p class="text-xs text-gray-500">Qty: {{ $item->quantity }} &bull; Cout: {{ number_format($item->cost, 2) }} EUR</p>
                                                </div>
                                                @if($item->source_url)
                                                    <a href="{{ $item->source_url }}" 
                                                       target="_blank"
                                                       class="ml-4 inline-flex items-center px-3 py-1.5 bg-orange-600 text-white text-xs font-medium rounded-lg hover:bg-orange-700 transition-colors">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                        </svg>
                                                        Commander
                                                    </a>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
