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
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'invitation_code' => ['required', 'string', 'max:32', Rule::exists('invitation_codes', 'code')->whereNull('used_at')],
        ], [
            'invitation_code.exists' => 'Code d\'invitation invalide ou déjà utilisé.',
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
