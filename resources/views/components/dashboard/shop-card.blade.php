@props(['shop'])

<div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-all duration-200">
    <div class="p-5">
        {{-- Header with shop name and status --}}
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1 min-w-0">
                <h3 class="text-lg font-semibold text-gray-900 truncate">
                    {{ $shop->name }}
                </h3>
                @if($shop->description)
                    <p class="text-sm text-gray-500 mt-1 line-clamp-1">{{ $shop->description }}</p>
                @endif
            </div>

            {{-- Active status --}}
            <div class="flex-shrink-0 ml-3">
                @if($shop->is_active)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Active
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                        Inactive
                    </span>
                @endif
            </div>
        </div>

        {{-- Stats --}}
        <div class="mb-4">
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-2xl font-bold text-gray-900">{{ $shop->products_count ?? 0 }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">Produits</div>
            </div>
        </div>

        {{-- Action buttons --}}
        <div class="flex gap-2">
            <a href="{{ route('products.index', ['shop_id' => $shop->id]) }}"
               class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Voir produits
            </a>
            <a href="{{ route('shops.edit', $shop) }}"
               class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors"
               title="Parametres">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </a>
        </div>
    </div>
</div>
