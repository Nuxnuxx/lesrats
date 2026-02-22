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
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
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

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Logo de la boutique</label>
                        <input type="file" name="logo" id="logo" accept="image/*"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                        <p class="mt-1 text-xs text-gray-500">PNG ou JPG recommande. Sera utilise pour la generation d'images IA (optionnel).</p>
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
