<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ $shop->name }}
                    </h2>
                    <div class="flex items-center mt-1 space-x-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $shop->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $shop->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <span class="text-sm text-gray-500">{{ $shop->currency }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('products.create', ['shop_id' => $shop->id]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nouveau produit
                </a>
                <a href="{{ route('shops.edit', $shop) }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Parametres
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                {{-- Total Products --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Produits</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total_products'] }}</p>
                        </div>
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                    </div>
                    @if($stats['active_products'] < $stats['total_products'])
                        <div class="mt-2 text-xs text-gray-500">
                            {{ $stats['active_products'] }} actif(s)
                        </div>
                    @endif
                </div>

                {{-- Total Orders --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Commandes</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total_orders'] }}</p>
                        </div>
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">{{ $stats['today_orders'] }} aujourd'hui</p>
                </div>

                {{-- Total Revenue --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Revenus</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_revenue'], 0, ',', ' ') }}</p>
                        </div>
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-green-600">+{{ number_format($stats['today_revenue'], 2, ',', ' ') }} {{ $shop->currency }} aujourd'hui</p>
                </div>

                {{-- Total Profit --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Profit</p>
                            <p class="text-2xl font-bold text-{{ $stats['total_profit'] >= 0 ? 'green' : 'red' }}-600 mt-1">
                                {{ number_format($stats['total_profit'], 0, ',', ' ') }}
                            </p>
                        </div>
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                    @if($stats['total_revenue'] > 0)
                        <p class="mt-2 text-xs text-gray-500">
                            Marge: {{ number_format(($stats['total_profit'] / $stats['total_revenue']) * 100, 1) }}%
                        </p>
                    @endif
                </div>

                {{-- This Month Orders --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Ce mois</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['this_month_orders'] }}</p>
                        </div>
                        <div class="p-2 bg-indigo-100 rounded-lg">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">{{ number_format($stats['this_month_revenue'], 0, ',', ' ') }} {{ $shop->currency }}</p>
                </div>

                {{-- Orders by Status --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase mb-2">Statuts</p>
                    <div class="space-y-1">
                        <div class="flex justify-between text-xs">
                            <span class="text-yellow-600">Nouvelles</span>
                            <span class="font-medium">{{ $ordersByStatus['new'] }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-blue-600">Commandees</span>
                            <span class="font-medium">{{ $ordersByStatus['ordered'] }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-indigo-600">Expediees</span>
                            <span class="font-medium">{{ $ordersByStatus['shipped'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Revenue Chart --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Revenus des 30 derniers jours</h3>
                    <span class="text-sm text-gray-500">{{ $shop->currency }}</span>
                </div>
                <div id="revenue-chart" class="h-72"></div>
            </div>

            {{-- Two Column Layout --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Recent Products --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">Derniers produits</h3>
                        <a href="{{ route('products.index', ['shop_id' => $shop->id]) }}" class="text-sm text-orange-600 hover:text-orange-700">
                            Voir tout
                        </a>
                    </div>
                    @if($recentProducts->isEmpty())
                        <div class="p-6 text-center">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <p class="text-gray-500 text-sm">Aucun produit</p>
                            <a href="{{ route('products.create', ['shop_id' => $shop->id]) }}" class="mt-2 inline-block text-sm text-orange-600 hover:text-orange-700">
                                Ajouter un produit
                            </a>
                        </div>
                    @else
                        <ul class="divide-y divide-gray-200">
                            @foreach($recentProducts as $product)
                                <li>
                                    <a href="{{ route('products.edit', $product) }}" class="block p-4 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center space-x-2">
                                                    {{-- Source icon --}}
                                                    @if($product->source_type === 'aliexpress')
                                                        <span class="flex-shrink-0 w-5 h-5 rounded bg-red-100 flex items-center justify-center" title="AliExpress">
                                                            <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                                            </svg>
                                                        </span>
                                                    @elseif($product->source_type === 'printables')
                                                        <span class="flex-shrink-0 w-5 h-5 rounded bg-purple-100 flex items-center justify-center" title="Printables">
                                                            <svg class="w-3 h-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                            </svg>
                                                        </span>
                                                    @else
                                                        <span class="flex-shrink-0 w-5 h-5 rounded bg-gray-100 flex items-center justify-center" title="Manuel">
                                                            <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                                            </svg>
                                                        </span>
                                                    @endif
                                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $product->title }}</p>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    {{ number_format($product->price, 2) }} {{ $shop->currency }}
                                                    @if($product->cost_price > 0)
                                                        <span class="text-green-600 ml-2">
                                                            +{{ number_format($product->price - $product->cost_price, 2) }} profit
                                                        </span>
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="ml-4">
                                                @if($product->is_active)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                                        Actif
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                                        Inactif
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Recent Orders --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">Dernieres commandes</h3>
                        {{-- TODO: Add orders index route with shop filter --}}
                    </div>
                    @if($recentOrders->isEmpty())
                        <div class="p-6 text-center">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <p class="text-gray-500 text-sm">Aucune commande</p>
                        </div>
                    @else
                        <ul class="divide-y divide-gray-200">
                            @foreach($recentOrders as $order)
                                <li class="p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-2">
                                                <p class="text-sm font-medium text-gray-900">{{ $order->customer_name }}</p>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    {{ $order->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $order->status_color === 'blue' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $order->status_color === 'indigo' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                                    {{ $order->status_color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $order->status_color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                                                    {{ $order->status_label }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ $order->created_at->format('d/m/Y H:i') }} &bull; {{ $order->items->count() }} article(s)
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

            {{-- Shop Information --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Informations</h3>
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Statut</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $shop->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $shop->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Devise</dt>
                        <dd class="font-medium text-gray-900 mt-1">{{ $shop->currency }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Cree le</dt>
                        <dd class="font-medium text-gray-900 mt-1">{{ $shop->created_at->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Membres</dt>
                        <dd class="font-medium text-gray-900 mt-1">{{ $shop->members->count() }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Members Section --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Membres ({{ $shop->members->count() }})</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($shop->members as $member)
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                            <div class="flex-shrink-0 w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-gray-600">
                                    {{ strtoupper(substr($member->user->name, 0, 2)) }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ $member->user->name }}
                                    @if($member->user->id === auth()->id())
                                        <span class="text-gray-400">(vous)</span>
                                    @endif
                                </p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize
                                    @if($member->role === 'owner') bg-purple-100 text-purple-800
                                    @elseif($member->role === 'admin') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $member->role }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    {{-- ApexCharts Script --}}
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var options = {
                series: [{
                    name: 'Revenus',
                    data: @json($chartData['data'])
                }],
                chart: {
                    type: 'area',
                    height: 288,
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                colors: ['#f97316'],
                xaxis: {
                    categories: @json($chartData['dates']),
                    labels: {
                        style: {
                            colors: '#9ca3af',
                            fontSize: '12px'
                        },
                        rotate: -45,
                        rotateAlways: false
                    },
                    tickAmount: 10,
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#9ca3af',
                            fontSize: '12px'
                        },
                        formatter: function(val) {
                            return val.toFixed(0) + ' {{ $shop->currency }}';
                        }
                    }
                },
                grid: {
                    borderColor: '#f3f4f6',
                    strokeDashArray: 4
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val.toFixed(2) + ' {{ $shop->currency }}';
                        }
                    }
                }
            };

            var chart = new ApexCharts(document.querySelector("#revenue-chart"), options);
            chart.render();
        });
    </script>
    @endpush
</x-app-layout>
