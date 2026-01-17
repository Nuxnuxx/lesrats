<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="{{ route('orders.index', ['shop_id' => $order->shop_id]) }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Commande #{{ $order->etsy_receipt_id }}
                    </h2>
                    <p class="text-sm text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }} - {{ $order->shop->name }}</p>
                </div>
            </div>
            <x-ui.status-badge :status="$order->status" type="order" class="px-3 py-1 text-sm" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Status Workflow --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Workflow</h3>

                {{-- Progress bar --}}
                <div class="relative mb-6">
                    <div class="flex items-center justify-between">
                        @php
                            $statuses = ['new', 'ordered', 'shipped', 'delivered', 'completed'];
                            $currentIndex = array_search($order->status, $statuses);
                        @endphp
                        @foreach($statuses as $index => $status)
                            <div class="flex flex-col items-center flex-1">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium
                                    {{ $index <= $currentIndex ? 'bg-orange-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                                    @if($index < $currentIndex)
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </div>
                                <span class="mt-2 text-xs font-medium {{ $index <= $currentIndex ? 'text-orange-600' : 'text-gray-500' }}">
                                    @switch($status)
                                        @case('new') Nouvelle @break
                                        @case('ordered') Commandee @break
                                        @case('shipped') Expediee @break
                                        @case('delivered') Livree @break
                                        @case('completed') Terminee @break
                                    @endswitch
                                </span>
                            </div>
                            @if($index < count($statuses) - 1)
                                <div class="flex-1 h-1 mx-2 {{ $index < $currentIndex ? 'bg-orange-600' : 'bg-gray-200' }}"></div>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="flex flex-wrap gap-3">
                    @if($order->status === 'new')
                        {{-- Check if has dropship items --}}
                        @if($order->items->where('source_type', 'aliexpress')->count() > 0)
                            <form action="{{ route('orders.update-status', $order) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="ordered">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                    </svg>
                                    Marquer comme commandee
                                </button>
                            </form>
                        @endif
                        @if($order->is_digital_only)
                            <form action="{{ route('orders.update-status', $order) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Terminer (digital)
                                </button>
                            </form>
                        @endif
                    @endif

                    @if($order->status === 'ordered')
                        <form action="{{ route('orders.update-status', $order) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="shipped">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                                Marquer comme expediee
                            </button>
                        </form>
                    @endif

                    @if($order->status === 'shipped')
                        <form action="{{ route('orders.update-status', $order) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="delivered">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                Marquer comme livree
                            </button>
                        </form>
                    @endif

                    @if($order->status === 'delivered')
                        <form action="{{ route('orders.update-status', $order) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg text-sm font-medium hover:bg-gray-700">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Terminer la commande
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Timestamps --}}
                <div class="mt-4 pt-4 border-t border-gray-200 grid grid-cols-2 md:grid-cols-5 gap-4 text-xs text-gray-500">
                    <div>
                        <span class="block font-medium">Creee</span>
                        {{ $order->created_at->format('d/m/Y H:i') }}
                    </div>
                    <div>
                        <span class="block font-medium">Commandee</span>
                        {{ $order->ordered_at?->format('d/m/Y H:i') ?? '-' }}
                    </div>
                    <div>
                        <span class="block font-medium">Expediee</span>
                        {{ $order->shipped_at?->format('d/m/Y H:i') ?? '-' }}
                    </div>
                    <div>
                        <span class="block font-medium">Livree</span>
                        {{ $order->delivered_at?->format('d/m/Y H:i') ?? '-' }}
                    </div>
                    <div>
                        <span class="block font-medium">Terminee</span>
                        {{ $order->completed_at?->format('d/m/Y H:i') ?? '-' }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Order Items --}}
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Articles ({{ $order->items->count() }})</h3>

                    <div class="space-y-4">
                        @foreach($order->items as $item)
                            <div class="flex items-start space-x-4 p-4 bg-gray-50 rounded-lg">
                                {{-- Source icon --}}
                                <div class="flex-shrink-0">
                                    @if($item->source_type === 'aliexpress')
                                        <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                            </svg>
                                        </div>
                                    @elseif($item->source_type === 'printables')
                                        <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                {{-- Item info --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">{{ $item->title }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Qty: {{ $item->quantity }}
                                        @if($item->sku)
                                            | SKU: {{ $item->sku }}
                                        @endif
                                    </p>
                                    
                                    {{-- Source link for dropship --}}
                                    @if($item->source_type === 'aliexpress' && $item->source_url && $order->status === 'new')
                                        <a href="{{ $item->source_url }}" 
                                           target="_blank"
                                           class="mt-2 inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                            Commander sur AliExpress
                                        </a>
                                    @endif
                                </div>

                                {{-- Pricing --}}
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900">{{ number_format($item->price * $item->quantity, 2) }} {{ $order->currency }}</p>
                                    @if($item->cost > 0)
                                        <p class="text-xs text-gray-500">Cout: {{ number_format($item->cost * $item->quantity, 2) }}</p>
                                        <p class="text-xs text-green-600">+{{ number_format($item->profit * $item->quantity, 2) }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Totals --}}
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-500">Sous-total</span>
                            <span class="text-gray-900">{{ number_format($order->total_price, 2) }} {{ $order->currency }}</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-500">Cout total</span>
                            <span class="text-gray-900">{{ number_format($order->total_cost, 2) }} {{ $order->currency }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-semibold pt-2 border-t border-gray-200">
                            <span class="text-gray-900">Profit</span>
                            <span class="text-green-600">+{{ number_format($order->total_profit, 2) }} {{ $order->currency }}</span>
                        </div>
                        @if($order->total_price > 0)
                            <p class="text-right text-xs text-gray-500 mt-1">
                                Marge: {{ number_format(($order->total_profit / $order->total_price) * 100, 1) }}%
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Customer Info & Notes --}}
                <div class="space-y-6">
                    {{-- Customer --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Client</h3>
                        
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $order->customer_name }}</p>
                                @if($order->customer_email)
                                    <a href="mailto:{{ $order->customer_email }}" class="text-sm text-orange-600 hover:text-orange-700">
                                        {{ $order->customer_email }}
                                    </a>
                                @endif
                            </div>

                            @if($order->shipping_address)
                                <div class="pt-3 border-t border-gray-200">
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-2">Adresse de livraison</p>
                                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $order->formatted_address }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes</h3>
                        
                        <form action="{{ route('orders.add-note', $order) }}" method="POST">
                            @csrf
                            <textarea name="notes" 
                                      rows="4" 
                                      class="w-full border border-gray-300 rounded-lg text-sm focus:ring-orange-500 focus:border-orange-500"
                                      placeholder="Ajouter une note...">{{ $order->notes }}</textarea>
                            <button type="submit" class="mt-2 inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">
                                Enregistrer
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
