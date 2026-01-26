<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('products.index', ['shop_id' => $shop->id]) }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Nouveau produit
                </h2>
                <p class="text-sm text-gray-500">{{ $shop->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="productWizard()">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            {{-- Progress Steps --}}
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <template x-for="(stepInfo, index) in steps" :key="index">
                        <div class="flex items-center" :class="index < steps.length - 1 ? 'flex-1' : ''">
                            <div class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold transition-colors"
                                     :class="step > index ? 'bg-orange-600 text-white' : (step === index ? 'bg-orange-600 text-white ring-4 ring-orange-200' : 'bg-gray-200 text-gray-500')">
                                    <template x-if="step > index">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </template>
                                    <template x-if="step <= index">
                                        <span x-text="index + 1"></span>
                                    </template>
                                </div>
                                <span class="mt-2 text-xs font-medium" 
                                      :class="step >= index ? 'text-orange-600' : 'text-gray-500'"
                                      x-text="stepInfo.label"></span>
                            </div>
                            <template x-if="index < steps.length - 1">
                                <div class="flex-1 h-1 mx-4" :class="step > index ? 'bg-orange-600' : 'bg-gray-200'"></div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Step 1: Choose Source --}}
            <div x-show="step === 0" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">D'ou vient votre produit ?</h3>
                    <p class="text-gray-500 mb-6">Choisissez la source pour importer automatiquement les informations.</p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- AliExpress --}}
                        <label class="cursor-pointer group">
                            <input type="radio" name="source_type" value="aliexpress" x-model="sourceType" class="sr-only peer">
                            <div class="relative p-6 rounded-xl border-2 transition-all peer-checked:border-orange-500 peer-checked:bg-orange-50 border-gray-200 hover:border-gray-300 group-hover:shadow-md">
                                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center mb-4">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-gray-900 mb-1">AliExpress</h4>
                                <p class="text-sm text-gray-500">Dropshipping de produits physiques</p>
                                <div class="absolute top-4 right-4 w-5 h-5 rounded-full border-2 peer-checked:border-orange-500 peer-checked:bg-orange-500 border-gray-300 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </label>

                        {{-- Printables --}}
                        <label class="cursor-pointer group">
                            <input type="radio" name="source_type" value="printables" x-model="sourceType" class="sr-only peer">
                            <div class="relative p-6 rounded-xl border-2 transition-all peer-checked:border-orange-500 peer-checked:bg-orange-50 border-gray-200 hover:border-gray-300 group-hover:shadow-md">
                                <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center mb-4">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-gray-900 mb-1">Printables</h4>
                                <p class="text-sm text-gray-500">Fichiers STL en telechargement</p>
                                <div class="absolute top-4 right-4 w-5 h-5 rounded-full border-2 peer-checked:border-orange-500 peer-checked:bg-orange-500 border-gray-300 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </label>

                        {{-- Manual --}}
                        <label class="cursor-pointer group">
                            <input type="radio" name="source_type" value="manual" x-model="sourceType" class="sr-only peer">
                            <div class="relative p-6 rounded-xl border-2 transition-all peer-checked:border-orange-500 peer-checked:bg-orange-50 border-gray-200 hover:border-gray-300 group-hover:shadow-md">
                                <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center mb-4">
                                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-gray-900 mb-1">Manuel</h4>
                                <p class="text-sm text-gray-500">Creer un produit de zero</p>
                                <div class="absolute top-4 right-4 w-5 h-5 rounded-full border-2 peer-checked:border-orange-500 peer-checked:bg-orange-500 border-gray-300 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </label>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button type="button" 
                                @click="nextStep()" 
                                :disabled="!sourceType"
                                class="inline-flex items-center px-6 py-3 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Continuer
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Step 2: Import URL (for AliExpress/Printables) or skip to form (Manual) --}}
            <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                    {{-- AliExpress Import --}}
                    <template x-if="sourceType === 'aliexpress'">
                        <div>
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">Importer depuis AliExpress</h3>
                                    <p class="text-sm text-gray-500">Collez le lien du produit pour l'importer automatiquement</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">URL du produit AliExpress</label>
                                    <div class="flex gap-3">
                                        <input type="url" 
                                               x-model="sourceUrl"
                                               placeholder="https://fr.aliexpress.com/item/123456789.html"
                                               class="flex-1 rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500">
                                        <button type="button" 
                                                @click="analyzeUrl()"
                                                :disabled="!sourceUrl || analyzing"
                                                class="inline-flex items-center px-5 py-2.5 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 disabled:opacity-50 transition-colors">
                                            <svg x-show="!analyzing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                            <svg x-show="analyzing" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span x-text="analyzing ? 'Analyse...' : 'Analyser'"></span>
                                        </button>
                                    </div>
                                </div>

                                <p class="text-sm text-gray-500 mt-4">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Le prix sera configure a l'etape suivante apres l'analyse.
                                </p>
                            </div>
                        </div>
                    </template>

                    {{-- Printables Import --}}
                    <template x-if="sourceType === 'printables'">
                        <div>
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">Importer depuis Printables</h3>
                                    <p class="text-sm text-gray-500">Collez le lien du modele 3D pour l'importer</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">URL du modele Printables</label>
                                    <div class="flex gap-3">
                                        <input type="url" 
                                               x-model="sourceUrl"
                                               placeholder="https://www.printables.com/model/123456-nom-du-modele"
                                               class="flex-1 rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500">
                                        <button type="button" 
                                                @click="analyzeUrl()"
                                                :disabled="!sourceUrl || analyzing"
                                                class="inline-flex items-center px-5 py-2.5 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 disabled:opacity-50 transition-colors">
                                            <svg x-show="!analyzing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                            <svg x-show="analyzing" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span x-text="analyzing ? 'Analyse...' : 'Analyser'"></span>
                                        </button>
                                    </div>
                                </div>

                                <p class="text-sm text-gray-500 mt-4">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Le prix sera configure a l'etape suivante. Cout = 0 EUR pour les fichiers digitaux.
                                </p>
                            </div>
                        </div>
                    </template>

                    {{-- Manual entry info --}}
                    <template x-if="sourceType === 'manual'">
                        <div>
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">Creation manuelle</h3>
                                    <p class="text-sm text-gray-500">Remplissez les informations vous-meme</p>
                                </div>
                            </div>

                            <div class="p-6 bg-gray-50 rounded-lg text-center">
                                <p class="text-gray-600 mb-4">Vous allez creer un produit manuellement sans import automatique.</p>
                                <p class="text-sm text-gray-500">Cliquez sur Continuer pour remplir les details du produit.</p>
                            </div>
                        </div>
                    </template>

                    {{-- Status message --}}
                    <div x-show="statusMessage" x-transition class="mt-4">
                        <div :class="{
                            'bg-green-50 border-green-200 text-green-800': statusType === 'success',
                            'bg-red-50 border-red-200 text-red-800': statusType === 'error',
                            'bg-blue-50 border-blue-200 text-blue-800': statusType === 'info',
                            'bg-yellow-50 border-yellow-200 text-yellow-800': statusType === 'warning'
                        }" class="p-4 rounded-lg border">
                            <p x-html="statusMessage"></p>
                        </div>
                    </div>

                    {{-- License warning for Printables --}}
                    <div x-show="licenseWarning" x-transition class="mt-4">
                        <div class="p-4 rounded-lg border" :class="commercialAllowed ? 'bg-yellow-50 border-yellow-200' : 'bg-red-50 border-red-200'">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 mr-2 flex-shrink-0" :class="commercialAllowed ? 'text-yellow-600' : 'text-red-600'" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="font-medium" :class="commercialAllowed ? 'text-yellow-800' : 'text-red-800'">
                                        Licence: <span x-text="productData.license"></span>
                                    </p>
                                    <p class="text-sm mt-1" :class="commercialAllowed ? 'text-yellow-700' : 'text-red-700'" x-text="commercialAllowed ? 'Verifiez que l\'usage commercial est autorise.' : 'Cette licence interdit l\'usage commercial !'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between">
                        <button type="button" @click="prevStep()" class="text-gray-500 hover:text-gray-700 font-medium">
                            Retour
                        </button>
                        <button type="button" 
                                @click="nextStep()"
                                :disabled="(sourceType !== 'manual' && !analyzed) || analyzing"
                                class="inline-flex items-center px-6 py-3 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Continuer
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Step 3: Preview & Edit --}}
            <div x-show="step === 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
                
                {{-- Images Selection --}}
                <div x-show="productData.images && productData.images.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Images du produit</h3>
                        <span class="text-sm text-gray-500">
                            <span x-text="selectedImages.length"></span>/10 selectionnees
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">Cliquez sur l'image pour la selectionner/deselectionner (max 10).</p>

                    {{-- Image Carousel --}}
                    <div class="relative" x-data="{ currentImageIndex: 0 }">
                        <div class="overflow-hidden rounded-lg">
                            <template x-for="(img, index) in productData.images" :key="index">
                                <div x-show="currentImageIndex === index"
                                     @click="toggleImage(img)"
                                     class="relative cursor-pointer">
                                    <img :src="img"
                                         class="w-full h-96 object-contain bg-gray-50 rounded-lg border-4 transition-all"
                                         :class="selectedImages.includes(img) ? 'border-orange-500' : 'border-gray-200'"
                                         alt="Product image">
                                    <div x-show="selectedImages.includes(img)"
                                         class="absolute inset-0 bg-orange-500 bg-opacity-10 rounded-lg pointer-events-none"></div>
                                    <div class="absolute top-4 right-4 px-3 py-1 rounded-full flex items-center gap-2 font-medium"
                                         :class="selectedImages.includes(img) ? 'bg-orange-500 text-white' : 'bg-white border-2 border-gray-300 text-gray-700'">
                                        <svg x-show="selectedImages.includes(img)" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span x-text="selectedImages.includes(img) ? 'Selectionnee' : 'Cliquez pour selectionner'"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Navigation Arrows --}}
                        <button type="button"
                                x-show="productData.images && productData.images.length > 1"
                                @click="currentImageIndex = currentImageIndex > 0 ? currentImageIndex - 1 : productData.images.length - 1"
                                class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-3 shadow-lg transition-all hover:scale-110">
                            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>

                        <button type="button"
                                x-show="productData.images && productData.images.length > 1"
                                @click="currentImageIndex = currentImageIndex < productData.images.length - 1 ? currentImageIndex + 1 : 0"
                                class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white rounded-full p-3 shadow-lg transition-all hover:scale-110">
                            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>

                        {{-- Indicator Dots --}}
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 bg-black/20 backdrop-blur-sm px-3 py-2 rounded-full">
                            <template x-for="(img, index) in productData.images" :key="index">
                                <button type="button"
                                        @click="currentImageIndex = index"
                                        :class="currentImageIndex === index ? 'bg-white w-8' : 'bg-white/50 w-2 hover:bg-white/75'"
                                        class="h-2 rounded-full transition-all"></button>
                            </template>
                        </div>

                        {{-- Image Counter --}}
                        <div class="absolute bottom-4 left-4 bg-black/60 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm font-medium">
                            <span x-text="currentImageIndex + 1"></span>/<span x-text="productData.images ? productData.images.length : 0"></span>
                        </div>
                    </div>
                </div>

                {{-- Product Details Form --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Details du produit</h3>

                    <div class="space-y-6">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">Titre (optimise pour Etsy)</label>
                                <button type="button"
                                        @click="copyToClipboard(productData.title, 'Titre copie!')"
                                        class="text-gray-400 hover:text-blue-600 transition-colors"
                                        title="Copier le titre">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                            <input type="text"
                                   x-model="productData.title"
                                   class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                                   placeholder="Titre accrocheur pour votre produit...">
                            <p class="mt-1 text-xs text-gray-500"><span x-text="(productData.title || '').length"></span>/140 caracteres</p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <button type="button"
                                        @click="copyToClipboard(productData.description, 'Description copiee!')"
                                        class="text-gray-400 hover:text-blue-600 transition-colors"
                                        title="Copier la description">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                            <textarea x-model="productData.description"
                                      rows="6"
                                      class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                                      placeholder="Description detaillee de votre produit..."></textarea>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">Tags Etsy (13 max)</label>
                                <button type="button"
                                        @click="copyToClipboard(productData.tags_string, 'Tags copies!')"
                                        class="text-gray-400 hover:text-blue-600 transition-colors"
                                        title="Copier les tags">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                            <input type="text"
                                   x-model="productData.tags_string"
                                   class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                                   placeholder="tag1, tag2, tag3...">
                            <p class="mt-1 text-xs text-gray-500">Separes par des virgules, 20 caracteres max par tag</p>
                        </div>

                        {{-- Re-optimize with AI --}}
                        <div class="p-4 bg-purple-50 rounded-lg border border-purple-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-purple-900">Optimiser avec l'IA</p>
                                    <p class="text-sm text-purple-700">Regenerer le titre, la description et les tags</p>
                                </div>
                                <button type="button" 
                                        @click="optimizeContent()"
                                        :disabled="optimizing"
                                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 disabled:opacity-50 transition-colors">
                                    <svg x-show="!optimizing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    <svg x-show="optimizing" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span x-text="optimizing ? 'Optimisation...' : 'Optimiser'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between">
                        <button type="button" @click="prevStep()" class="text-gray-500 hover:text-gray-700 font-medium">
                            Retour
                        </button>
                        <button type="button"
                                @click="nextStep()"
                                :disabled="!productData.title"
                                class="inline-flex items-center px-6 py-3 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Continuer
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Step 4: Pricing & Confirm --}}
            <div x-show="step === 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
                <form method="POST" action="{{ route('products.store') }}" @submit="submitForm">
                    @csrf
                    <input type="hidden" name="source_type" :value="sourceType">
                    <input type="hidden" name="source_url" :value="sourceUrl">
                    <input type="hidden" name="title" :value="productData.title">
                    <input type="hidden" name="description" :value="productData.description">
                    <input type="hidden" name="tags" :value="productData.tags_string">
                    <input type="hidden" name="images" :value="JSON.stringify(selectedImages)">
                    <input type="hidden" name="license" :value="productData.license || ''">
                    <input type="hidden" name="attribution" :value="productData.attribution || ''">
                    <input type="hidden" name="quantity" :value="stockQuantity">
                    <input type="hidden" name="is_digital" :value="sourceType === 'printables' ? '1' : '0'">

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {{-- Pricing --}}
                        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6">Prix et parametres</h3>

                            <div class="grid grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Prix de vente ({{ $shop->currency }})</label>
                                    <div class="relative">
                                        <input type="number" 
                                               name="price"
                                               x-model="sellingPrice"
                                               step="0.01" 
                                               min="0"
                                               required
                                               class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 pr-12">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm">{{ $shop->currency }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Cout d'achat ({{ $shop->currency }})</label>
                                    <div class="relative">
                                        <input type="number" 
                                               name="cost_price"
                                               x-model="costPrice"
                                               step="0.01" 
                                               min="0"
                                               class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 pr-12">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm">{{ $shop->currency }}</span>
                                        </div>
                                    </div>
                                    <p x-show="sourceType === 'printables'" class="mt-1 text-xs text-gray-500">Digital = 0</p>
                                </div>
                            </div>

                            {{-- Profit Calculator --}}
                            <div class="p-4 bg-gray-50 rounded-lg mb-6">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-gray-600">Profit par vente</span>
                                    <span class="text-lg font-bold" :class="profit >= 0 ? 'text-green-600' : 'text-red-600'" x-text="(profit >= 0 ? '+' : '') + profit.toFixed(2) + ' {{ $shop->currency }}'"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Marge</span>
                                    <span class="text-sm font-medium" :class="margin >= 30 ? 'text-green-600' : (margin >= 15 ? 'text-yellow-600' : 'text-red-600')" x-text="margin.toFixed(1) + '%'"></span>
                                </div>
                            </div>

                            {{-- Stock Management --}}
                            <div class="grid grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Stock</label>
                                    <div class="relative">
                                        <input type="number"
                                               x-model="stockQuantity"
                                               :disabled="isUnlimitedStock"
                                               min="0"
                                               class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 disabled:bg-gray-100 disabled:text-gray-500">
                                    </div>
                                    <p x-show="sourceType === 'printables'" class="mt-1 text-xs text-green-600">
                                        Produit digital = Stock illimite
                                    </p>
                                </div>

                                <div class="flex items-end pb-2">
                                    <label class="flex items-center" x-show="sourceType !== 'printables'">
                                        <input type="checkbox"
                                               x-model="isUnlimitedStock"
                                               @change="if(isUnlimitedStock) stockQuantity = 999"
                                               class="w-4 h-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                        <span class="ml-2 text-sm text-gray-700">Stock illimite</span>
                                    </label>
                                </div>
                            </div>

                            {{-- Low Stock Alert --}}
                            <div x-show="!isUnlimitedStock && sourceType !== 'printables'" class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Alerte stock bas</label>
                                <div class="flex items-center gap-3">
                                    <input type="number"
                                           name="low_stock_threshold"
                                           x-model="lowStockThreshold"
                                           min="0"
                                           max="100"
                                           class="w-24 rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500">
                                    <span class="text-sm text-gray-500">Notifier quand le stock descend sous ce seuil</span>
                                </div>
                            </div>

                            <div class="mt-6 space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_active" value="1" checked
                                           class="w-4 h-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                    <span class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">Produit actif</span>
                                    </span>
                                </label>

                                <label class="flex items-center">
                                    <input type="checkbox" name="auto_sync" value="1"
                                           class="w-4 h-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                    <span class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">Synchronisation automatique Etsy</span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        {{-- Preview --}}
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4">Apercu</h3>
                            
                            <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden mb-4">
                                <template x-if="selectedImages.length > 0">
                                    <img :src="selectedImages[0]" class="w-full h-full object-cover">
                                </template>
                                <template x-if="selectedImages.length === 0">
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                </template>
                            </div>

                            <p class="text-sm font-medium text-gray-900 line-clamp-2" x-text="productData.title || 'Titre du produit'"></p>
                            <p class="text-lg font-bold text-gray-900 mt-2" x-text="sellingPrice + ' {{ $shop->currency }}'"></p>
                            
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <x-ui.source-badge :type="'aliexpress'" x-show="sourceType === 'aliexpress'" />
                                <x-ui.source-badge :type="'printables'" x-show="sourceType === 'printables'" />
                                <x-ui.source-badge :type="'manual'" x-show="sourceType === 'manual'" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between">
                        <button type="button" @click="prevStep()" class="text-gray-500 hover:text-gray-700 font-medium">
                            Retour
                        </button>
                        <button type="submit" 
                                :disabled="submitting"
                                class="inline-flex items-center px-8 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 disabled:opacity-50 transition-colors">
                            <svg x-show="!submitting" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg x-show="submitting" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="submitting ? 'Creation...' : 'Creer le produit'"></span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        function productWizard() {
            return {
                step: 0,
                steps: [
                    { label: 'Source' },
                    { label: 'Import' },
                    { label: 'Details' },
                    { label: 'Prix' }
                ],
                
                sourceType: '',
                sourceUrl: '',
                costPrice: 0,
                sellingPrice: 0,
                stockQuantity: 10,
                isUnlimitedStock: false,
                lowStockThreshold: 5,
                
                analyzing: false,
                analyzed: false,
                optimizing: false,
                submitting: false,
                
                statusMessage: '',
                statusType: 'info',
                licenseWarning: false,
                commercialAllowed: true,
                
                productData: {
                    title: '',
                    description: '',
                    tags_string: '',
                    images: [],
                    license: '',
                    attribution: '',
                    author: ''
                },
                
                selectedImages: [],
                
                get profit() {
                    return (parseFloat(this.sellingPrice) || 0) - (parseFloat(this.costPrice) || 0);
                },
                
                get margin() {
                    const price = parseFloat(this.sellingPrice) || 0;
                    if (price <= 0) return 0;
                    return (this.profit / price) * 100;
                },
                
                nextStep() {
                    if (this.step === 0 && this.sourceType === 'manual') {
                        // Skip import step for manual
                        this.analyzed = true;
                    }
                    
                    // Set default prices and stock when moving to pricing step
                    if (this.step === 2) {
                        if (this.sourceType === 'printables') {
                            this.costPrice = 0;
                            this.stockQuantity = 999; // Unlimited for digital
                            this.isUnlimitedStock = true;
                            if (!this.sellingPrice || this.sellingPrice == 0) {
                                this.sellingPrice = 4.99;
                            }
                        } else if (this.sourceType === 'aliexpress') {
                            // If no cost price set, use a default
                            if (!this.sellingPrice || this.sellingPrice == 0) {
                                if (this.costPrice > 0) {
                                    this.sellingPrice = (this.costPrice * 3).toFixed(2);
                                } else {
                                    this.sellingPrice = 29.99; // Default selling price
                                }
                            }
                        } else {
                            // Manual - set defaults
                            if (!this.sellingPrice || this.sellingPrice == 0) {
                                this.sellingPrice = 19.99;
                            }
                        }
                    }
                    
                    if (this.step < this.steps.length - 1) {
                        this.step++;
                    }
                },
                
                prevStep() {
                    if (this.step > 0) {
                        this.step--;
                    }
                },
                
                async analyzeUrl() {
                    if (!this.sourceUrl) return;
                    
                    this.analyzing = true;
                    this.statusMessage = 'Extraction des donnees en cours... (20-40 secondes)';
                    this.statusType = 'info';
                    
                    const endpoint = this.sourceType === 'aliexpress' 
                        ? '{{ route("products.analyze-aliexpress") }}'
                        : '{{ route("products.analyze-printables") }}';
                    
                    const bodyKey = this.sourceType === 'aliexpress' ? 'aliexpress_url' : 'printables_url';
                    
                    try {
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ [bodyKey]: this.sourceUrl })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            this.productData.title = result.data.title || '';
                            this.productData.description = result.data.description || '';
                            this.productData.tags_string = result.data.tags_string || '';
                            this.productData.images = result.data.images || [];
                            this.productData.license = result.data.license || '';
                            this.productData.attribution = result.data.attribution || '';
                            this.productData.author = result.data.author || '';
                            
                            // Auto-select first 5 images
                            this.selectedImages = this.productData.images.slice(0, 5);
                            
                            // Set prices
                            if (this.sourceType === 'aliexpress') {
                                if (result.data.original_price) {
                                    this.costPrice = result.data.original_price;
                                }
                                if (this.costPrice > 0) {
                                    this.sellingPrice = (this.costPrice * 3).toFixed(2);
                                }
                            } else if (this.sourceType === 'printables') {
                                this.costPrice = 0;
                                if (!this.sellingPrice) this.sellingPrice = 4.99;
                            }
                            
                            // License warning for Printables
                            if (this.sourceType === 'printables' && result.data.license) {
                                this.licenseWarning = true;
                                this.commercialAllowed = result.data.commercial_allowed !== false;
                            }
                            
                            this.statusMessage = `<strong>Import reussi !</strong> ${this.productData.images.length} images recuperees.`;
                            this.statusType = 'success';
                            this.analyzed = true;
                        } else {
                            // Check if we should switch to manual mode
                            if (result.use_manual) {
                                this.statusMessage = `<strong>Mode manuel requis</strong><br>${result.message}<br><small class="text-gray-600">${result.tip || 'Cliquez sur Continuer pour entrer les details manuellement.'}</small>`;
                                this.statusType = 'warning';
                                // Allow continuing in manual mode
                                this.analyzed = true;
                            } else {
                                this.statusMessage = result.message || 'Erreur lors de l\'analyse';
                                this.statusType = 'error';
                            }
                        }
                    } catch (error) {
                        this.statusMessage = 'Erreur de connexion. Veuillez reessayer.';
                        this.statusType = 'error';
                    } finally {
                        this.analyzing = false;
                    }
                },
                
                async optimizeContent() {
                    if (!this.productData.title) return;
                    
                    this.optimizing = true;
                    
                    try {
                        const response = await fetch('{{ route("products.optimize-content") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                title: this.productData.title,
                                description: this.productData.description,
                                price: this.sellingPrice
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            this.productData.title = result.data.title || this.productData.title;
                            this.productData.description = result.data.description || this.productData.description;
                            this.productData.tags_string = result.data.tags_string || this.productData.tags_string;
                        }
                    } catch (error) {
                        console.error('Optimization failed:', error);
                    } finally {
                        this.optimizing = false;
                    }
                },
                
                toggleImage(img) {
                    if (this.selectedImages.includes(img)) {
                        this.selectedImages = this.selectedImages.filter(i => i !== img);
                    } else if (this.selectedImages.length < 10) {
                        this.selectedImages.push(img);
                    }
                },

                copyToClipboard(text, successMessage = 'Copie!') {
                    if (!text) {
                        alert('Aucun contenu a copier');
                        return;
                    }

                    navigator.clipboard.writeText(text).then(() => {
                        this.statusMessage = `<strong>${successMessage}</strong> Le contenu est dans votre presse-papier.`;
                        this.statusType = 'success';

                        // Clear message after 3 seconds
                        setTimeout(() => {
                            if (this.statusType === 'success') {
                                this.statusMessage = '';
                            }
                        }, 3000);
                    }).catch(err => {
                        alert('Erreur lors de la copie: ' + err);
                    });
                },

                submitForm() {
                    this.submitting = true;
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
