@props([
    'label',
    'value',
    'icon' => null,
    'color' => 'gray', // gray, orange, blue, green, purple, red, yellow
    'trend' => null, // positive, negative, or null
    'trendValue' => null,
    'href' => null,
])

@php
    $iconBgColors = [
        'orange' => 'bg-orange-100',
        'blue' => 'bg-blue-100',
        'green' => 'bg-green-100',
        'purple' => 'bg-purple-100',
        'red' => 'bg-red-100',
        'yellow' => 'bg-yellow-100',
        'gray' => 'bg-gray-100',
    ];

    $iconTextColors = [
        'orange' => 'text-orange-600',
        'blue' => 'text-blue-600',
        'green' => 'text-green-600',
        'purple' => 'text-purple-600',
        'red' => 'text-red-600',
        'yellow' => 'text-yellow-600',
        'gray' => 'text-gray-600',
    ];

    $valueColors = [
        'orange' => 'text-orange-600',
        'blue' => 'text-blue-600',
        'green' => 'text-green-600',
        'purple' => 'text-purple-600',
        'red' => 'text-red-600',
        'yellow' => 'text-yellow-600',
        'gray' => 'text-gray-900',
    ];

    $iconBg = $iconBgColors[$color] ?? $iconBgColors['gray'];
    $iconText = $iconTextColors[$color] ?? $iconTextColors['gray'];
    $valueColor = $valueColors[$color] ?? $valueColors['gray'];
@endphp

@if($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'block bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow']) }}>
@else
<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow-sm border border-gray-200 p-4']) }}>
@endif

    <div class="flex items-center">
        @if($icon)
            <div class="flex-shrink-0 p-3 {{ $iconBg }} rounded-lg">
                @if($icon === 'products')
                    <svg class="w-6 h-6 {{ $iconText }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                @elseif($icon === 'orders')
                    <svg class="w-6 h-6 {{ $iconText }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                @elseif($icon === 'revenue')
                    <svg class="w-6 h-6 {{ $iconText }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @elseif($icon === 'profit')
                    <svg class="w-6 h-6 {{ $iconText }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                @elseif($icon === 'shop')
                    <svg class="w-6 h-6 {{ $iconText }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                @elseif($icon === 'sync')
                    <svg class="w-6 h-6 {{ $iconText }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                @elseif($icon === 'warning')
                    <svg class="w-6 h-6 {{ $iconText }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                @else
                    {{ $icon }}
                @endif
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
                <p class="text-2xl font-bold {{ $valueColor }}">{{ $value }}</p>
                @if($trendValue)
                    <p class="text-xs {{ $trend === 'positive' ? 'text-green-600' : ($trend === 'negative' ? 'text-red-600' : 'text-gray-500') }}">
                        @if($trend === 'positive')+@endif{{ $trendValue }}
                    </p>
                @endif
            </div>
        @else
            <div>
                <p class="text-sm font-medium {{ $color !== 'gray' ? $iconText : 'text-gray-500' }}">{{ $label }}</p>
                <p class="text-2xl font-bold {{ $valueColor }}">{{ $value }}</p>
                @if($trendValue)
                    <p class="text-xs {{ $trend === 'positive' ? 'text-green-600' : ($trend === 'negative' ? 'text-red-600' : 'text-gray-500') }}">
                        @if($trend === 'positive')+@endif{{ $trendValue }}
                    </p>
                @endif
            </div>
        @endif
    </div>

@if($href)
</a>
@else
</div>
@endif
