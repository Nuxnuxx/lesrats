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
            <a href="{{ route('products.edit', $product) }}">
                <img src="{{ $firstImage }}" alt="{{ $product->title }}" class="w-full h-full object-cover cursor-pointer">
            </a>
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
                <x-ui.source-badge type="aliexpress" class="rounded-full">Dropship</x-ui.source-badge>
            @elseif($product->source_type === 'printables')
                <x-ui.source-badge type="printables" class="rounded-full">STL</x-ui.source-badge>
            @endif
        </div>

        {{-- Active Status Badge --}}
        <div class="absolute top-2 right-2">
            @if(!$product->is_active)
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600">
                    Inactif
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


    </div>

    {{-- Content --}}
    <div class="p-4">
        <a href="{{ route('products.edit', $product) }}" class="block">
            <h3 class="text-sm font-medium text-gray-900 line-clamp-2 hover:text-orange-600 transition-colors">
                {{ $product->title }}
            </h3>
        </a>

        <div class="mt-3 flex items-center justify-between">
            <div>
                <p class="text-lg font-bold text-gray-900">
                    {{ number_format($product->price, 2) }} {{ $product->shop->currency }}
                    @php $disc = (float)($product->shop->discount_percentage ?? 0); @endphp
                    @if($disc > 0)
                        <span class="text-sm font-normal text-gray-400 line-through ml-1">{{ number_format($product->price / (1 - $disc / 100), 2) }}</span>
                    @endif
                </p>
                @if($product->cost_price > 0)
                    <p class="text-xs text-green-600">
                        +{{ number_format($product->price - $product->cost_price, 2) }} profit
                    </p>
                @endif
            </div>

            <div class="flex flex-col items-end gap-1">
                @if(!$product->is_active)
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600">
                        Inactif
                    </span>
                @endif

                {{-- Stock Badge --}}
                @php
                    $qty = $product->quantity ?? 999;
                    $threshold = $product->low_stock_threshold ?? 5;
                    $isDigital = $product->is_digital ?? false;
                @endphp
                @if($isDigital || $qty >= 999)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                        Illimite
                    </span>
                @elseif($qty <= 0)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                        Rupture
                    </span>
                @elseif($qty <= $threshold)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">
                        Stock: {{ $qty }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                        Stock: {{ $qty }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
