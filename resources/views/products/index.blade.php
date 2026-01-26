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
            <div class="flex items-center space-x-3">
                @if($shop->etsy_shop_id)
                    <form action="{{ route('products.import-etsy') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-orange-500 text-orange-600 rounded-lg text-sm font-medium hover:bg-orange-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Importer Etsy
                        </button>
                    </form>
                @endif
                <a href="{{ route('products.create') }}" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nouveau produit
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="productList()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Stats Bar --}}
            @if($shop->mode === 'connected')
                <!-- Stats pour mode connecté -->
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <x-ui.stat-card label="Total" :value="$stats['total']" />
                    <x-ui.stat-card label="Synchronises" :value="$stats['synced']" color="green" />
                    <x-ui.stat-card label="En attente" :value="$stats['pending']" color="blue" />
                    <x-ui.stat-card label="Erreurs" :value="$stats['errors']" color="red" />
                </div>
            @else
                <!-- Stats pour mode manuel -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <x-ui.stat-card label="Total produits" :value="$stats['total']" />
                    <x-ui.stat-card label="Prets a copier" :value="$stats['total']" color="green" />
                    <div class="bg-blue-50 p-4 rounded-lg shadow-sm border border-blue-200">
                        <p class="text-blue-600 text-sm font-medium">Mode manuel</p>
                        <p class="text-xs text-blue-800 mt-1">
                            <a href="{{ route('shops.edit', $shop) }}" class="underline hover:text-blue-900">
                                Connecter Etsy pour sync auto
                            </a>
                        </p>
                    </div>
                </div>
            @endif

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
                    <select name="source_type" class="border border-gray-300 rounded-lg text-sm py-2 px-3 focus:ring-orange-500 focus:border-orange-500">
                        <option value="">Toutes sources</option>
                        <option value="aliexpress" {{ request('source_type') === 'aliexpress' ? 'selected' : '' }}>AliExpress</option>
                        <option value="printables" {{ request('source_type') === 'printables' ? 'selected' : '' }}>Printables</option>
                        <option value="manual" {{ request('source_type') === 'manual' ? 'selected' : '' }}>Manuel</option>
                    </select>

                    {{-- Sync Status Filter --}}
                    <select name="sync_status" class="border border-gray-300 rounded-lg text-sm py-2 px-3 focus:ring-orange-500 focus:border-orange-500">
                        <option value="">Tous statuts</option>
                        <option value="synced" {{ request('sync_status') === 'synced' ? 'selected' : '' }}>Synchronise</option>
                        <option value="pending" {{ request('sync_status') === 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="error" {{ request('sync_status') === 'error' ? 'selected' : '' }}>Erreur</option>
                    </select>

                    {{-- Filter Button --}}
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filtrer
                    </button>

                    @if(request('search') || request('source_type') || request('sync_status'))
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
                        <button @click="bulkSync()" 
                                :disabled="syncing"
                                class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 disabled:opacity-50">
                            <svg class="w-4 h-4 mr-2" :class="{ 'animate-spin': syncing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span x-text="syncing ? 'Synchronisation...' : 'Synchroniser Etsy'"></span>
                        </button>
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
                    :description="request('search') || request('source_type') || request('sync_status') 
                        ? 'Aucun produit ne correspond a vos filtres.' 
                        : 'Commencez par ajouter votre premier produit.'"
                    :actionUrl="!(request('search') || request('source_type') || request('sync_status')) ? route('products.create') : null"
                    :actionLabel="!(request('search') || request('source_type') || request('sync_status')) ? 'Nouveau produit' : null"
                    :secondaryActionUrl="request('search') || request('source_type') || request('sync_status') ? route('products.index', ['shop_id' => $shop->id]) : null"
                    :secondaryActionLabel="request('search') || request('source_type') || request('sync_status') ? 'Effacer les filtres' : null"
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
                syncing: false,
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

                async bulkSync() {
                    if (this.selectedProducts.length === 0) return;
                    if (!confirm('Synchroniser ' + this.selectedProducts.length + ' produit(s) avec Etsy ?')) return;

                    this.syncing = true;
                    try {
                        const response = await fetch('{{ route('products.bulk-sync') }}', {
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
                        alert('Erreur lors de la synchronisation');
                    } finally {
                        this.syncing = false;
                    }
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
