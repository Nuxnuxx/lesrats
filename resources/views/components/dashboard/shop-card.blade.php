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


        </div>

        {{-- Stats --}}
        <div class="mb-4">
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-2xl font-bold text-gray-900">{{ $shop->products_count ?? 0 }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">Produits</div>
            </div>
        </div>

        {{-- Rappel promotions --}}
        <div class="mb-4" x-data="{
            date: '{{ $shop->promotion_reminder_date?->format('Y-m-d') ?? '' }}',
            get status() {
                if (!this.date) return 'none';
                const today = new Date(); today.setHours(0,0,0,0);
                const d = new Date(this.date + 'T00:00:00');
                if (d < today) return 'past';
                if (d.getTime() === today.getTime()) return 'today';
                return 'future';
            },
            save() {
                fetch('/shops/{{ $shop->id }}/autosave', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ promotion_reminder_date: this.date || null })
                });
            }
        }">
            <label class="text-xs font-medium text-gray-600">Rappel promotions</label>
            <div class="mt-1 flex items-center gap-2">
                <input type="date" x-model="date" @change="save()"
                       class="block w-full text-sm rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500">
                <span x-show="status === 'past'" x-cloak class="text-xs font-medium text-red-600 whitespace-nowrap">Rappel dû !</span>
                <span x-show="status === 'today'" x-cloak class="text-xs font-medium text-orange-500 whitespace-nowrap">Aujourd'hui</span>
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
