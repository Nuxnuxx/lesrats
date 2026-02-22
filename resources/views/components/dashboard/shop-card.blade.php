@props(['shop'])

<a href="{{ route('shops.edit', $shop) }}" 
   class="block bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-orange-200 transition-all duration-200">
    <div class="p-5">
        {{-- Header with shop name and status --}}
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1 min-w-0">
                <h3 class="text-lg font-semibold text-gray-900 truncate">
                    {{ $shop->name }}
                </h3>
                <div class="flex items-center mt-1 space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                        {{ $shop->connection_status_color === 'green' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $shop->connection_status_color === 'red' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $shop->connection_status_color === 'gray' ? 'bg-gray-100 text-gray-800' : '' }}">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            @if($shop->connection_status === 'connected')
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            @elseif($shop->connection_status === 'expired')
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            @else
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            @endif
                        </svg>
                        {{ $shop->connection_status_label }}
                    </span>
                </div>
            </div>
            
            {{-- Etsy icon --}}
            <div class="flex-shrink-0 ml-3">
                <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8.559 3.89c0-.458.442-.706.906-.706h5.07c.464 0 .906.248.906.706 0 .459-.442.707-.906.707h-5.07c-.464 0-.906-.248-.906-.707zm7.559 5.657c0 1.888-1.529 3.418-3.418 3.418H9.282c-.464 0-.906-.248-.906-.707s.442-.706.906-.706H12.7c1.107 0 2.006-.899 2.006-2.005 0-1.107-.899-2.006-2.006-2.006H9.282c-.464 0-.906-.247-.906-.706s.442-.707.906-.707H12.7c1.889 0 3.418 1.53 3.418 3.419zm-7.559 8.563c0-.458.442-.706.906-.706h5.07c.464 0 .906.248.906.706 0 .459-.442.707-.906.707h-5.07c-.464 0-.906-.248-.906-.707z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Stats grid --}}
        <div class="grid grid-cols-2 gap-4">
            {{-- Products --}}
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-2xl font-bold text-gray-900">{{ $shop->products_count ?? 0 }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">Produits</div>
            </div>
            
            {{-- Orders --}}
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-2xl font-bold text-gray-900">{{ $shop->orders_count ?? 0 }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">Commandes</div>
            </div>
            
            {{-- Today's orders --}}
            <div class="bg-blue-50 rounded-lg p-3">
                <div class="text-2xl font-bold text-blue-600">{{ $shop->todays_orders_count }}</div>
                <div class="text-xs text-blue-600 uppercase tracking-wide">Aujourd'hui</div>
            </div>
            
            {{-- Revenue --}}
            <div class="bg-green-50 rounded-lg p-3">
                <div class="text-lg font-bold text-green-600">{{ number_format($shop->total_revenue ?? 0, 0, ',', ' ') }} {{ $shop->currency }}</div>
                <div class="text-xs text-green-600 uppercase tracking-wide">Revenus</div>
            </div>
        </div>
    </div>
</a>
