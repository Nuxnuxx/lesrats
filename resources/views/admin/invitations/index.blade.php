<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Codes d\'invitation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Newly generated code (one-shot flash) --}}
            @if(session('new_invitation_code'))
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-sm font-medium text-green-800 mb-2">
                        {{ __('Code créé. Copiez-le et envoyez-le au beta tester.') }}
                    </p>
                    <div class="flex items-center gap-2">
                        <code id="new-invitation-code"
                              class="flex-1 p-2 bg-white rounded border text-sm font-mono break-all tracking-widest">{{ session('new_invitation_code') }}</code>
                        <button type="button"
                                onclick="navigator.clipboard.writeText(document.getElementById('new-invitation-code').textContent).then(() => { this.textContent = 'Copié ✓'; })"
                                class="px-3 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                            {{ __('Copier') }}
                        </button>
                    </div>
                </div>
            @endif

            @if(session('status') === 'invitation-deleted')
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                    {{ __('Code supprimé.') }}
                </div>
            @endif

            @if($errors->any())
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Generate button --}}
            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                <header class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Générer un nouveau code') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('Le code est à usage unique. Le beta tester l\'utilise sur /register.') }}
                    </p>
                </header>

                <form method="POST" action="{{ route('admin.invitations.store') }}">
                    @csrf
                    <x-primary-button>{{ __('Générer un code') }}</x-primary-button>
                </form>
            </div>

            {{-- Codes table --}}
            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                <header class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Historique') }}</h3>
                </header>

                @if($codes->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('Aucun code pour l\'instant.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-b border-gray-200 text-left text-gray-500">
                                <tr>
                                    <th class="py-2 pr-4">{{ __('Code') }}</th>
                                    <th class="py-2 pr-4">{{ __('Statut') }}</th>
                                    <th class="py-2 pr-4">{{ __('Créé par') }}</th>
                                    <th class="py-2 pr-4">{{ __('Créé le') }}</th>
                                    <th class="py-2 pr-4">{{ __('Utilisé par') }}</th>
                                    <th class="py-2 pr-4">{{ __('Utilisé le') }}</th>
                                    <th class="py-2 pr-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($codes as $code)
                                    <tr>
                                        <td class="py-2 pr-4 font-mono tracking-wider">{{ $code->code }}</td>
                                        <td class="py-2 pr-4">
                                            @if($code->used_at)
                                                <span class="inline-block px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">{{ __('Utilisé') }}</span>
                                            @else
                                                <span class="inline-block px-2 py-0.5 rounded text-xs bg-green-100 text-green-800">{{ __('Disponible') }}</span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4 text-gray-600">{{ $code->creator?->email ?? '—' }}</td>
                                        <td class="py-2 pr-4 text-gray-600">{{ $code->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="py-2 pr-4 text-gray-600">{{ $code->user?->email ?? '—' }}</td>
                                        <td class="py-2 pr-4 text-gray-600">{{ $code->used_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                        <td class="py-2 pr-4 text-right">
                                            @unless($code->used_at)
                                                <form method="POST" action="{{ route('admin.invitations.destroy', $code) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="text-red-600 hover:text-red-800"
                                                            onclick="return confirm('Supprimer ce code ?')">
                                                        {{ __('Supprimer') }}
                                                    </button>
                                                </form>
                                            @endunless
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $codes->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
