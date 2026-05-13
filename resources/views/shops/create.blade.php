<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('shops.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Nouvelle boutique
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-orange-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-orange-900">
                        <p class="font-medium">Vous avez deja une boutique Etsy ?</p>
                        <p class="mt-1">
                            Ouvrez
                            <a href="https://www.etsy.com/your/shops/me/settings/your-shop/shop-basics?ref=seller-platform-mcnav"
                               target="_blank" rel="noopener noreferrer"
                               class="font-semibold underline hover:text-orange-700">
                                vos parametres Etsy &rarr;
                            </a>
                            puis copiez-collez le nom, la devise et la description ci-dessous.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <form method="POST" action="{{ route('shops.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom de la boutique *</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                            class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm"
                            placeholder="Ma Boutique">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description / Niche</label>
                        <textarea id="description" name="description" rows="3"
                            class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm text-sm"
                            placeholder="Ex: outdoor equipment and garden tools, 3D printed figurines, handmade jewelry...">{{ old('description') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Decrivez votre niche/specialite. Sera utilise dans les prompts IA pour personnaliser les contenus generes.</p>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4"
                         x-data="{
                            dragging: false,
                            preview: null,
                            filename: null,
                            assign(file) {
                                if (!file || !file.type.startsWith('image/')) return;
                                this.filename = file.name;
                                const r = new FileReader();
                                r.onload = (e) => this.preview = e.target.result;
                                r.readAsDataURL(file);
                            },
                            drop(e) {
                                this.dragging = false;
                                const file = e.dataTransfer.files[0];
                                if (!file) return;
                                this.$refs.input.files = e.dataTransfer.files;
                                this.assign(file);
                            },
                            clear() {
                                this.$refs.input.value = '';
                                this.preview = null;
                                this.filename = null;
                            }
                         }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Logo de la boutique</label>

                        <input type="file" name="logo" id="logo" accept="image/*" class="hidden"
                               x-ref="input"
                               @change="assign($event.target.files[0])">

                        <label for="logo"
                               @dragover.prevent="dragging = true"
                               @dragenter.prevent="dragging = true"
                               @dragleave.prevent="dragging = false"
                               @drop.prevent="drop($event)"
                               class="flex flex-col items-center justify-center w-full min-h-[160px] border-2 border-dashed rounded-lg p-6 cursor-pointer transition"
                               :class="dragging ? 'border-orange-500 bg-orange-50' : 'border-gray-300 hover:border-orange-400 hover:bg-gray-50'">

                            <template x-if="!preview">
                                <div class="text-center">
                                    <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <p class="mt-2 text-sm font-medium text-gray-700">{{ __('Glissez votre logo ici') }}</p>
                                    <p class="text-xs text-gray-500">{{ __('ou cliquez pour parcourir') }}</p>
                                    <p class="mt-2 text-xs text-gray-400">PNG / JPG / WEBP &middot; 2 MB max</p>
                                </div>
                            </template>

                            <template x-if="preview">
                                <div class="flex flex-col items-center gap-2">
                                    <img :src="preview" alt="" class="h-24 w-24 object-contain rounded bg-white border border-gray-200">
                                    <p x-text="filename" class="text-sm font-medium text-gray-700 truncate max-w-xs"></p>
                                    <button type="button" @click.prevent.stop="clear()"
                                            class="text-xs text-red-600 hover:text-red-700 underline">
                                        {{ __('Retirer') }}
                                    </button>
                                </div>
                            </template>
                        </label>

                        <p class="mt-1 text-xs text-gray-500">Sera utilise pour la generation d'images IA (optionnel).</p>
                        @error('logo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="currency" class="block text-sm font-medium text-gray-700">Devise *</label>
                        <select id="currency" name="currency" required
                            class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm">
                            <option value="EUR" {{ old('currency', 'EUR') == 'EUR' ? 'selected' : '' }}>EUR (Euro)</option>
                            <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD (Dollar US)</option>
                            <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP (Livre Sterling)</option>
                            <option value="CAD" {{ old('currency') == 'CAD' ? 'selected' : '' }}>CAD (Dollar Canadien)</option>
                        </select>
                        @error('currency')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4" x-data="{ type: '{{ old('product_type', 'physical') }}' }">
                        <label for="product_type" class="block text-sm font-medium text-gray-700">Type de produits *</label>
                        <select id="product_type" name="product_type" required x-model="type"
                            class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-sm">
                            <option value="physical">Produits physiques (dropshipping)</option>
                            <option value="virtual">Produits virtuels (fichiers numeriques)</option>
                        </select>
                        @error('product_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <div x-show="type === 'virtual'" x-cloak class="mt-3">
                            <label for="default_price" class="block text-sm font-medium text-gray-700">Prix par defaut</label>
                            <div class="mt-1 relative">
                                <input type="number" name="default_price" id="default_price" step="0.01" min="0"
                                       value="{{ old('default_price') }}"
                                       placeholder="5.99"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 pr-12">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-sm">EUR</span>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Prix applique automatiquement aux nouveaux produits. Modifiable par produit.</p>
                            @error('default_price')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Etsy categories — utilisees par l'IA pour ranger les produits importes --}}
                    <div class="mb-4"
                         x-data="{
                            categories: @js(old('etsy_categories_array', [])),
                            input: '',
                            add() {
                                const v = (this.input || '').trim();
                                if (!v) return;
                                if (this.categories.includes(v)) { this.input = ''; return; }
                                this.categories.push(v);
                                this.input = '';
                            },
                            remove(i) { this.categories.splice(i, 1); }
                         }">
                        <label class="block text-sm font-medium text-gray-700">Categories Etsy</label>
                        <p class="mt-1 mb-2 text-xs text-gray-500">
                            Listez les categories que vous vendez sur Etsy (ex : "Bijoux", "Decoration murale"). L'IA y rangera automatiquement chaque produit importe par l'extension.
                        </p>

                        <div class="flex flex-wrap gap-1.5 mb-2 min-h-[36px] p-2 border border-gray-200 rounded-lg bg-gray-50">
                            <template x-if="categories.length === 0">
                                <span class="text-xs text-gray-400 italic">Aucune categorie</span>
                            </template>
                            <template x-for="(cat, i) in categories" :key="i">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    <span x-text="cat"></span>
                                    <button type="button" @click="remove(i)" class="ml-1.5 text-orange-600 hover:text-orange-900">&times;</button>
                                </span>
                            </template>
                        </div>

                        <div class="flex gap-2">
                            <input type="text" x-model="input" @keydown.enter.prevent="add()"
                                placeholder="Ex : Bijoux, Decoration, Vetements..."
                                class="flex-1 text-sm border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg">
                            <button type="button" @click="add()"
                                class="px-3 py-2 bg-orange-100 text-orange-700 text-sm font-medium rounded-lg hover:bg-orange-200 transition">
                                Ajouter
                            </button>
                        </div>

                        <input type="hidden" name="etsy_categories" :value="JSON.stringify(categories)">
                        @error('etsy_categories')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('shops.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">
                            Annuler
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Creer la boutique
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
