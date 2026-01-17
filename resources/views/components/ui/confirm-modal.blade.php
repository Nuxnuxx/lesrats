@props([
    'id',
    'title' => 'Confirmer',
    'message' => 'Etes-vous sur de vouloir continuer ?',
    'confirmLabel' => 'Confirmer',
    'cancelLabel' => 'Annuler',
    'type' => 'danger', // danger, warning, info
    'formAction' => null,
    'formMethod' => 'POST',
])

@php
    $buttonColors = match($type) {
        'danger' => 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
        'warning' => 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500',
        default => 'bg-orange-600 hover:bg-orange-700 focus:ring-orange-500',
    };

    $iconColors = match($type) {
        'danger' => 'bg-red-100 text-red-600',
        'warning' => 'bg-yellow-100 text-yellow-600',
        default => 'bg-blue-100 text-blue-600',
    };
@endphp

<div x-data="{ open: false }"
     x-on:open-modal-{{ $id }}.window="open = true"
     x-on:close-modal-{{ $id }}.window="open = false"
     x-on:keydown.escape.window="open = false"
     {{ $attributes }}>
    
    {{-- Trigger slot --}}
    <div @click="open = true">
        {{ $trigger ?? '' }}
    </div>

    {{-- Modal backdrop --}}
    <template x-teleport="body">
        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-title-{{ $id }}"
             role="dialog"
             aria-modal="true">
            
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" 
                     @click="open = false"
                     aria-hidden="true"></div>

                {{-- Modal panel --}}
                <div x-show="open"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    
                    <div class="sm:flex sm:items-start">
                        {{-- Icon --}}
                        <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto rounded-full {{ $iconColors }} sm:mx-0 sm:h-10 sm:w-10">
                            @if($type === 'danger')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            @elseif($type === 'warning')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @else
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title-{{ $id }}">
                                {{ $title }}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">{{ $message }}</p>
                            </div>
                            
                            {{-- Extra content slot --}}
                            @if(isset($content))
                                <div class="mt-4">
                                    {{ $content }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
                        @if($formAction)
                            <form action="{{ $formAction }}" method="POST" class="inline">
                                @csrf
                                @if($formMethod !== 'POST')
                                    @method($formMethod)
                                @endif
                                {{ $hiddenInputs ?? '' }}
                                <button type="submit" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white border border-transparent rounded-lg shadow-sm {{ $buttonColors }} focus:outline-none focus:ring-2 focus:ring-offset-2 sm:w-auto sm:text-sm">
                                    {{ $confirmLabel }}
                                </button>
                            </form>
                        @else
                            <button type="button" 
                                    @click="$dispatch('confirm-{{ $id }}'); open = false"
                                    class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white border border-transparent rounded-lg shadow-sm {{ $buttonColors }} focus:outline-none focus:ring-2 focus:ring-offset-2 sm:w-auto sm:text-sm">
                                {{ $confirmLabel }}
                            </button>
                        @endif
                        
                        <button type="button" 
                                @click="open = false"
                                class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:w-auto sm:text-sm">
                            {{ $cancelLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
