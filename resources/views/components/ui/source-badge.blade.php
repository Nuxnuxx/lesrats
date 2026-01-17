@props([
    'type', // aliexpress, printables, manual
    'showLabel' => true,
    'size' => 'sm', // sm, md
])

@php
    $config = match($type) {
        'aliexpress' => [
            'bg' => 'bg-red-100',
            'text' => 'text-red-700',
            'label' => 'AliExpress',
            'icon' => 'box',
        ],
        'printables' => [
            'bg' => 'bg-purple-100',
            'text' => 'text-purple-700',
            'label' => 'Printables',
            'icon' => 'document',
        ],
        default => [
            'bg' => 'bg-gray-100',
            'text' => 'text-gray-700',
            'label' => 'Manuel',
            'icon' => 'cube',
        ],
    };

    $sizeClasses = match($size) {
        'md' => 'px-2.5 py-1 text-sm',
        default => 'px-2 py-0.5 text-xs',
    };
    
    $iconSize = match($size) {
        'md' => 'w-4 h-4',
        default => 'w-3 h-3',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded font-medium {$config['bg']} {$config['text']} $sizeClasses"]) }}>
    @if($config['icon'] === 'box')
        <svg class="{{ $iconSize }} {{ $showLabel ? 'mr-1' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
        </svg>
    @elseif($config['icon'] === 'document')
        <svg class="{{ $iconSize }} {{ $showLabel ? 'mr-1' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
        </svg>
    @else
        <svg class="{{ $iconSize }} {{ $showLabel ? 'mr-1' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
    @endif
    @if($showLabel)
        {{ $config['label'] }}
    @endif
</span>
