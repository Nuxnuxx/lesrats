@props([
    'status',
    'type' => 'order', // order, sync
])

@php
    // Order status colors and labels
    $orderConfig = [
        'new' => ['color' => 'yellow', 'label' => 'Nouvelle'],
        'ordered' => ['color' => 'blue', 'label' => 'Commandee'],
        'shipped' => ['color' => 'indigo', 'label' => 'Expediee'],
        'delivered' => ['color' => 'green', 'label' => 'Livree'],
        'completed' => ['color' => 'gray', 'label' => 'Terminee'],
    ];

    // Sync status colors and labels
    $syncConfig = [
        'synced' => ['color' => 'green', 'label' => 'Synchronise', 'icon' => 'check'],
        'pending' => ['color' => 'yellow', 'label' => 'En attente', 'icon' => 'clock'],
        'error' => ['color' => 'red', 'label' => 'Erreur', 'icon' => 'exclamation'],
        'not_synced' => ['color' => 'gray', 'label' => 'Non synchronise', 'icon' => 'minus'],
    ];

    $config = $type === 'sync' ? $syncConfig : $orderConfig;
    $item = $config[$status] ?? ['color' => 'gray', 'label' => ucfirst($status), 'icon' => null];
    $color = $item['color'];
    $label = $item['label'];
    $icon = $item['icon'] ?? null;

    $colorClasses = match($color) {
        'yellow' => 'bg-yellow-100 text-yellow-800',
        'blue' => 'bg-blue-100 text-blue-800',
        'indigo' => 'bg-indigo-100 text-indigo-800',
        'green' => 'bg-green-100 text-green-800',
        'red' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-800',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $colorClasses"]) }}>
    @if($type === 'sync' && $icon)
        @if($icon === 'check')
            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        @elseif($icon === 'clock')
            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
            </svg>
        @elseif($icon === 'exclamation')
            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
        @elseif($icon === 'minus')
            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
            </svg>
        @endif
    @endif
    {{ $slot->isEmpty() ? $label : $slot }}
</span>
