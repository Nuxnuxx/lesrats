<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Étape 1 : valider d'abord le code d'invitation SEUL.
        // Sans code valide, on refuse avant même de lire l'email — un attaquant
        // sans code ne peut pas s'en servir pour énumérer les comptes.
        $request->validate([
            'invitation_code' => ['required', 'string', 'max:32', Rule::exists('invitation_codes', 'code')->whereNull('used_at')],
        ], [
            'invitation_code.exists' => 'Code d\'invitation invalide ou déjà utilisé.',
        ]);

        // Étape 2 : valider le reste du formulaire avec un message d'erreur générique
        // pour l'email (anti-énumération : pas de "cet email existe déjà").
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.unique' => 'Impossible de créer le compte avec cet email.',
        ]);

        $user = DB::transaction(function () use ($request) {
            $invitation = InvitationCode::where('code', $request->invitation_code)
                ->whereNull('used_at')
                ->lockForUpdate()
                ->first();

            if (! $invitation) {
                throw ValidationException::withMessages([
                    'invitation_code' => 'Code d\'invitation invalide ou déjà utilisé.',
                ]);
            }

            $user = new User([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // role n'est volontairement pas $fillable (anti-mass-assignment).
            // On le pose explicitement ici comme beta_tester — seul moyen de le créer
            // hors admin manuel via DB / commande.
            $user->role = User::ROLE_BETA_TESTER;
            $user->save();

            $invitation->update([
                'used_by' => $user->id,
                'used_at' => now(),
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
