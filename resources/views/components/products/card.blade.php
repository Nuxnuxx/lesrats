@props(['product', 'selectable' => false])

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow group"
     x-data="{ selected: false }"
     :class="{ 'ring-2 ring-orange-500 border-orange-500': selected }">
    
    {{-- Image / Placeholder --}}
    <div class="relative aspect-square bg-gray-100">
        @php
            $images = is_string($product->images) ? json_decode($product->images, true) : $product->images;
            $firstImage = is_array($images) && !empty($images) ? $images[0] : null;
        @endphp
        
        @if($firstImage)
            <img src="{{ $firstImage }}" alt="{{ $product->title }}" class="w-full h-full object-cover">
        @else
            <div class="w-full h-full flex items-center justify-center">
                @if($product->source_type === 'printables')
                    <svg class="w-16 h-16 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                @elseif($product->source_type === 'aliexpress')
                    <svg class="w-16 h-16 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                @else
                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                @endif
            </div>
        @endif

        {{-- Source Badge --}}
        <div class="absolute top-2 left-2">
            @if($product->source_type === 'aliexpress')
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    Dropship
                </span>
            @elseif($product->source_type === 'printables')
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    STL
                </span>
            @endif
        </div>

        {{-- Sync Status Badge --}}
        <div class="absolute top-2 right-2">
            @if($product->etsy_sync_status === 'synced')
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Etsy
                </span>
            @elseif($product->etsy_sync_status === 'pending')
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    En attente
                </span>
            @elseif($product->etsy_sync_status === 'error')
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700" title="{{ $product->etsy_sync_error }}">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Erreur
                </span>
            @endif
        </div>

        {{-- Selection Checkbox --}}
        @if($selectable)
            <div class="absolute bottom-2 left-2">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" 
                           class="product-checkbox w-5 h-5 rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                           value="{{ $product->id }}"
                           x-model="selected"
                           @change="$dispatch('product-selected', { id: {{ $product->id }}, selected: selected })">
                </label>
            </div>
        @endif

        {{-- Quick Actions (on hover) --}}
        <div class="absolute bottom-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex space-x-1">
            <a href="{{ route('products.edit', $product) }}" 
               class="p-2 bg-white rounded-full shadow-md hover:bg-gray-100 transition-colors"
               title="Modifier">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </a>
            @if($product->source_url)
                <a href="{{ $product->source_url }}" 
                   target="_blank"
                   class="p-2 bg-white rounded-full shadow-md hover:bg-gray-100 transition-colors"
                   title="Voir la source">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            @endif
        </div>
    </div>

    {{-- Content --}}
    <div class="p-4">
        <a href="{{ route('products.edit', $product) }}" class="block">
            <h3 class="text-sm font-medium text-gray-900 line-clamp-2 hover:text-orange-600 transition-colors">
                {{ $product->title }}
            </h3>
        </a>

        @if($product->sku)
            <p class="text-xs text-gray-400 mt-1">SKU: {{ $product->sku }}</p>
        @endif

        <div class="mt-3 flex items-center justify-between">
            <div>
                <p class="text-lg font-bold text-gray-900">{{ number_format($product->price, 2) }} {{ $product->shop->currency }}</p>
                @if($product->cost_price > 0)
                    <p class="text-xs text-green-600">
                        +{{ number_format($product->price - $product->cost_price, 2) }} profit
                    </p>
                @endif
            </div>

            @if(!$product->is_active)
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600">
                    Inactif
                </span>
            @endif
        </div>
    </div>
</div>
