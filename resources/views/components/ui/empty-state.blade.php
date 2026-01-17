@props([
    'title',
    'description' => null,
    'icon' => 'folder', // folder, orders, products, search, inbox
    'actionUrl' => null,
    'actionLabel' => null,
    'secondaryActionUrl' => null,
    'secondaryActionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center']) }}>
    <div class="mx-auto w-16 h-16 text-gray-300 mb-4">
        @if($icon === 'folder')
            <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
        @elseif($icon === 'orders')
            <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
        @elseif($icon === 'products')
            <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
        @elseif($icon === 'search')
            <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        @elseif($icon === 'inbox')
            <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
        @elseif($icon === 'shop')
            <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        @elseif($icon === 'check')
            <svg class="w-full h-full text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        @else
            {{ $iconSlot ?? '' }}
        @endif
    </div>

    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $title }}</h3>
    
    @if($description)
        <p class="text-gray-500 mb-4">{{ $description }}</p>
    @endif

    @if($actionUrl || $secondaryActionUrl || !$slot->isEmpty())
        <div class="flex items-center justify-center gap-3">
            @if($actionUrl)
                <a href="{{ $actionUrl }}" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition-colors">
                    {{ $actionLabel ?? 'Action' }}
                </a>
            @endif
            @if($secondaryActionUrl)
                <a href="{{ $secondaryActionUrl }}" class="text-sm text-orange-600 hover:text-orange-700 font-medium">
                    {{ $secondaryActionLabel ?? 'Autre action' }}
                </a>
            @endif
            {{ $slot }}
        </div>
    @endif
</div>
