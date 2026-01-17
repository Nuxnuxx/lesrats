<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etsy Mock - Authorization</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-orange-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="bg-orange-500 px-6 py-4">
            <div class="flex items-center justify-center">
                <svg class="w-8 h-8 text-white mr-2" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M8.559 2.002c-.404 0-.759.291-.833.69L6.108 12.31c-.082.435.234.847.677.847h3.648l-.96 7.155c-.08.597.404 1.119.979 1.119.341 0 .667-.174.854-.464l6.835-10.6c.27-.418-.02-.967-.513-.967h-3.752l1.808-6.622c.118-.435-.238-.876-.694-.876z"/>
                </svg>
                <span class="text-white text-xl font-bold">Etsy Mock</span>
            </div>
            <p class="text-orange-100 text-center text-sm mt-1">Mode Développement</p>
        </div>

        <!-- Content -->
        <div class="p-6">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-yellow-700">
                        <strong>Mode Mock actif</strong> - Ceci simule l'autorisation Etsy pour le développement.
                    </p>
                </div>
            </div>

            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                LesRats demande l'accès à votre boutique Etsy
            </h2>

            <p class="text-sm text-gray-600 mb-6">
                Cette application souhaite accéder à vos informations Etsy avec les permissions suivantes :
            </p>

            <ul class="text-sm text-gray-600 space-y-2 mb-6">
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Lire et modifier vos listings
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Accéder aux informations de votre boutique
                </li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Lire vos transactions
                </li>
            </ul>

            <!-- Mock Shop Configuration -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Configurer la boutique mock :</h3>
                <form id="approveForm" action="{{ route('etsy.mock.approve') }}" method="POST">
                    @csrf
                    <input type="hidden" name="state" value="{{ $state }}">
                    <input type="hidden" name="redirect_uri" value="{{ $redirect_uri }}">

                    <div class="space-y-3">
                        <div>
                            <label for="shop_name" class="block text-xs font-medium text-gray-600">Nom de la boutique</label>
                            <input type="text" id="shop_name" name="shop_name" value="Ma Boutique Test" 
                                class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="shop_id" class="block text-xs font-medium text-gray-600">Shop ID</label>
                                <input type="text" id="shop_id" name="shop_id" value="{{ rand(10000000, 99999999) }}" 
                                    class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            </div>
                            <div>
                                <label for="user_id" class="block text-xs font-medium text-gray-600">User ID</label>
                                <input type="text" id="user_id" name="user_id" value="{{ rand(10000000, 99999999) }}" 
                                    class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            </div>
                        </div>
                        <div>
                            <label for="currency" class="block text-xs font-medium text-gray-600">Devise</label>
                            <select id="currency" name="currency" 
                                class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="EUR">EUR (Euro)</option>
                                <option value="USD">USD (Dollar US)</option>
                                <option value="GBP">GBP (Livre Sterling)</option>
                                <option value="CAD">CAD (Dollar Canadien)</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Actions -->
            <div class="flex space-x-3">
                <form action="{{ route('etsy.mock.deny') }}" method="POST" class="flex-1">
                    @csrf
                    <input type="hidden" name="state" value="{{ $state }}">
                    <input type="hidden" name="redirect_uri" value="{{ $redirect_uri }}">
                    <button type="submit" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-md font-medium hover:bg-gray-300 transition">
                        Refuser
                    </button>
                </form>
                <button type="submit" form="approveForm" class="flex-1 px-4 py-2 bg-orange-500 text-white rounded-md font-medium hover:bg-orange-600 transition">
                    Autoriser
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-3 border-t">
            <p class="text-xs text-gray-500 text-center">
                Client ID: {{ $client_id ?? 'N/A' }} | Scopes: {{ $scope ?? 'N/A' }}
            </p>
        </div>
    </div>
</body>
</html>
