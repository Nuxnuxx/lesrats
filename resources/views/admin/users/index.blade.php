<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Utilisateurs') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('status') === 'user-promoted')
                <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
                    {{ __('Utilisateur promu admin.') }}
                </div>
            @elseif(session('status') === 'user-demoted')
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                    {{ __('Utilisateur rétrogradé en beta tester.') }}
                </div>
            @elseif(session('status') === 'role-unchanged')
                <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
                    {{ __('Aucun changement.') }}
                </div>
            @endif

            @if($errors->any())
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                <header class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Tous les utilisateurs') }}</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ __('Promouvez un beta tester en admin ou rétrogradez un admin.') }}
                        </p>
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ __('Admins') }} : <span class="font-semibold text-gray-900">{{ $adminCount }}</span>
                    </div>
                </header>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-gray-200 text-left text-gray-500">
                            <tr>
                                <th class="py-2 pr-4">{{ __('Nom') }}</th>
                                <th class="py-2 pr-4">{{ __('Email') }}</th>
                                <th class="py-2 pr-4">{{ __('Rôle') }}</th>
                                <th class="py-2 pr-4">{{ __('Photos IA') }}</th>
                                <th class="py-2 pr-4">{{ __('Boutiques') }}</th>
                                <th class="py-2 pr-4">{{ __('Inscrit le') }}</th>
                                <th class="py-2 pr-4 text-right">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($users as $u)
                                @php
                                    $isSelf = $u->id === auth()->id();
                                    $isAdmin = $u->role === \App\Models\User::ROLE_ADMIN;
                                    $isLastAdmin = $isAdmin && $adminCount <= 1;
                                @endphp
                                <tr>
                                    <td class="py-2 pr-4 text-gray-900">{{ $u->name }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $u->email }}</td>
                                    <td class="py-2 pr-4">
                                        @if($isAdmin)
                                            <span class="inline-block px-2 py-0.5 rounded text-xs bg-orange-100 text-orange-800">{{ __('Admin') }}</span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">{{ __('Beta tester') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4 text-gray-600">
                                        @if($isAdmin)
                                            <span class="text-gray-400">{{ __('illimité') }}</span>
                                        @else
                                            {{ $u->ai_photos_count }} / {{ \App\Models\User::BETA_PHOTO_LIMIT }}
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $u->owned_shops_count }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $u->created_at->format('d/m/Y') }}</td>
                                    <td class="py-2 pr-4 text-right">
                                        @if($isSelf)
                                            <span class="text-xs text-gray-400">{{ __('vous-même') }}</span>
                                        @elseif($isLastAdmin)
                                            <span class="text-xs text-gray-400">{{ __('dernier admin') }}</span>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.role', $u) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                @if($isAdmin)
                                                    <input type="hidden" name="role" value="{{ \App\Models\User::ROLE_BETA_TESTER }}">
                                                    <button type="submit"
                                                            class="text-blue-600 hover:text-blue-800"
                                                            onclick="return confirm('Rétrograder {{ $u->email }} en beta tester ?')">
                                                        {{ __('Rétrograder') }}
                                                    </button>
                                                @else
                                                    <input type="hidden" name="role" value="{{ \App\Models\User::ROLE_ADMIN }}">
                                                    <button type="submit"
                                                            class="text-orange-600 hover:text-orange-800"
                                                            onclick="return confirm('Promouvoir {{ $u->email }} en admin ? L\'admin a tous les droits.')">
                                                        {{ __('Promouvoir admin') }}
                                                    </button>
                                                @endif
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
