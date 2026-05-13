@php
    /** @var string $state 'done' | 'active' | 'locked' */
    /** @var int $number */
@endphp
@switch($state)
    @case('done')
        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        @break
    @case('locked')
        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center" title="{{ __('Etape verrouillee') }}">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.105.895-2 2-2h0a2 2 0 012 2v2m-4 0h4m-7-5V7a4 4 0 118 0v4M5 13h14v8H5v-8z"/>
            </svg>
        </div>
        @break
    @default
        <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 font-semibold text-sm">{{ $number }}</div>
@endswitch
