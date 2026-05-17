<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Produits
                </h2>
                {{-- Shop Selector --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" 
                            class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <span>{{ $shop->name }}</span>
                        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" 
                         @click.away="open = false"
                         x-transition
                         class="absolute left-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                        @foreach($shops as $s)
                            <a href="{{ route('products.index', ['shop_id' => $s->id]) }}" 
                               class="block px-4 py-2 text-sm {{ $s->id === $shop->id ? 'bg-orange-50 text-orange-600' : 'text-gray-700 hover:bg-gray-50' }}">
                                {{ $s->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="productList()">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Stats Bar --}}
            <div class="mb-6">
                <x-ui.stat-card label="Total produits" :value="$stats['total']" />
            </div>

            {{-- Filters & Bulk Actions Bar --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                <form method="GET" action="{{ route('products.index') }}" class="flex flex-wrap items-center gap-4">
                    <input type="hidden" name="shop_id" value="{{ $shop->id }}">
                    
                    {{-- Search --}}
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <input type="text" 
                                   name="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Rechercher un produit..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-orange-500 focus:border-orange-500">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Source Type Filter --}}
                    <select name="source_type"
                            onchange="this.form.submit()"
                            class="border border-gray-300 rounded-lg text-sm py-2 px-3 focus:ring-orange-500 focus:border-orange-500">
                        <option value="">Toutes sources</option>
                        <option value="aliexpress" {{ request('source_type') === 'aliexpress' ? 'selected' : '' }}>AliExpress</option>
                        <option value="printables" {{ request('source_type') === 'printables' ? 'selected' : '' }}>Printables</option>
                        <option value="manual" {{ request('source_type') === 'manual' ? 'selected' : '' }}>Manuel</option>
                    </select>

                    {{-- Tri --}}
                    <select name="sort"
                            onchange="this.form.submit()"
                            class="border border-gray-300 rounded-lg text-sm py-2 px-3 focus:ring-orange-500 focus:border-orange-500">
                        <option value="latest"     {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Plus recents</option>
                        <option value="oldest"     {{ request('sort') === 'oldest' ? 'selected' : '' }}>Plus anciens</option>
                        <option value="title_asc"  {{ request('sort') === 'title_asc' ? 'selected' : '' }}>Titre A → Z</option>
                        <option value="title_desc" {{ request('sort') === 'title_desc' ? 'selected' : '' }}>Titre Z → A</option>
                    </select>

                    @if(request('search') || request('source_type') || (request('sort') && request('sort') !== 'latest'))
                        <a href="{{ route('products.index', ['shop_id' => $shop->id]) }}" class="text-sm text-gray-500 hover:text-gray-700">
                            Effacer
                        </a>
                    @endif
                </form>

                {{-- Bulk Actions --}}
                <div x-show="selectedProducts.length > 0" 
                     x-transition
                     class="mt-4 pt-4 border-t border-gray-200 flex items-center justify-between">
                    <span class="text-sm text-gray-600">
                        <span x-text="selectedProducts.length"></span> produit(s) selectionne(s)
                    </span>
                    <div class="flex items-center space-x-3">
                        <button @click="bulkDelete()" 
                                :disabled="deleting"
                                class="inline-flex items-center px-4 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-medium hover:bg-red-200 disabled:opacity-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Supprimer
                        </button>
                        <button @click="selectedProducts = []" class="text-sm text-gray-500 hover:text-gray-700">
                            Annuler
                        </button>
                    </div>
                </div>
            </div>

            {{-- Products Grid --}}
            @if ($products->isEmpty())
                <x-ui.empty-state
                    icon="products"
                    title="Aucun produit"
                    :description="request('search') || request('source_type')
                        ? 'Aucun produit ne correspond a vos filtres.'
                        : 'Importez vos premiers produits depuis AliExpress ou Printables via l\'extension navigateur LesRats.'"
                    :secondaryActionUrl="request('search') || request('source_type') ? route('products.index', ['shop_id' => $shop->id]) : null"
                    :secondaryActionLabel="request('search') || request('source_type') ? 'Effacer les filtres' : null"
                />
            @else
                {{-- Select All --}}
                <div class="flex items-center justify-between mb-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" 
                               class="w-4 h-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                               @change="toggleSelectAll($event)">
                        <span class="ml-2 text-sm text-gray-600">Tout selectionner</span>
                    </label>
                    <p class="text-sm text-gray-500">
                        {{ $products->total() }} produit(s)
                    </p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                    @foreach($products as $product)
                        <x-products.card :product="$product" :selectable="true" />
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        function productList() {
            return {
                selectedProducts: [],
                deleting: false,

                init() {
                    // Listen for product selection events
                    this.$el.addEventListener('product-selected', (event) => {
                        const { id, selected } = event.detail;
                        if (selected) {
                            if (!this.selectedProducts.includes(id)) {
                                this.selectedProducts.push(id);
                            }
                        } else {
                            this.selectedProducts = this.selectedProducts.filter(p => p !== id);
                        }
                    });
                },

                toggleSelectAll(event) {
                    const checkboxes = document.querySelectorAll('.product-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = event.target.checked;
                        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                },

                async bulkDelete() {
                    if (this.selectedProducts.length === 0) return;
                    if (!confirm('Supprimer ' + this.selectedProducts.length + ' produit(s) ? Cette action est irreversible.')) return;

                    this.deleting = true;
                    try {
                        const response = await fetch('{{ route('products.bulk-delete') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ product_ids: this.selectedProducts })
                        });

                        const result = await response.json();
                        alert(result.message);
                        
                        if (result.success) {
                            window.location.reload();
                        }
                    } catch (error) {
                        alert('Erreur lors de la suppression');
                    } finally {
                        this.deleting = false;
                    }
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
