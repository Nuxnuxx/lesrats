<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\InvitationCode;
use App\Models\User;
use App\Services\PostHogService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        // Étape 1 : valider d'abord LE CODE seul. Si on validait email + code ensemble,
        // un attaquant pourrait sonder les emails existants ("invalid email" vs "invalid code").
        // On exige donc un code valide AVANT toute autre vérification.
        $request->validate([
            'invitation_code' => [
                'required',
                'string',
                Rule::exists('invitation_codes', 'code')->whereNull('used_at'),
            ],
        ], [
            'invitation_code.exists' => 'Code d\'invitation invalide ou déjà utilisé.',
            'invitation_code.required' => 'Code d\'invitation requis.',
        ]);

        // Étape 2 : valider le reste (email unique, mot de passe…).
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Étape 3 : transaction atomique avec lock pessimiste sur le code.
        // Empêche deux requêtes concurrentes de consommer le même code.
        $user = DB::transaction(function () use ($request, $validated) {
            $code = InvitationCode::where('code', $request->input('invitation_code'))
                ->lockForUpdate()
                ->first();

            // Re-vérifier dans le lock : entre validate() et ici, un autre process
            // a pu utiliser le code. C'est ce que le lock empêche, mais on s'en assure.
            if (! $code || $code->used_at !== null) {
                abort(422, 'Invitation code already consumed.');
            }

            $user = new User;
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->password = $validated['password']; // hashed via cast
            // role n'est pas fillable — assignation explicite obligatoire.
            $user->role = User::ROLE_BETA_TESTER;
            $user->save();

            $code->used_by = $user->id;
            $code->used_at = now();
            $code->save();

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        PostHogService::identify($user->id, [
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
        ]);
        PostHogService::capture($user->id, 'user_registered', [
            'role' => $user->role,
        ]);

        return redirect(route('dashboard', absolute: false));
    }
}
