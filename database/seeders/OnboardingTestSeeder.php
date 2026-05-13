<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Cree (ou reinitialise) un utilisateur dans l'etat "doit faire l'onboarding".
 *
 *   php artisan db:seed --class=OnboardingTestSeeder
 *
 * Login : onboarding@lesrats.fr / password
 *
 * Reset complet a chaque execution :
 *   - onboarded_at remis a NULL
 *   - toutes les boutiques possedees sont detachees (puis supprimees si l'utilisateur
 *     etait le seul owner)
 *   - tous les tokens API ("Extension Auto-Connect" inclus) sont revoques
 *
 * Apres seed, login avec l'email ci-dessus -> redirection automatique vers /onboarding.
 */
class OnboardingTestSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'onboarding@lesrats.fr'],
            [
                'name' => 'Onboarding Test',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'onboarded_at' => null,
            ]
        );

        // S'il avait deja une boutique d'un seed precedent, on la nettoie pour repartir a zero.
        foreach ($user->ownedShops()->get() as $shop) {
            $shop->users()->detach($user->id);

            if ($shop->users()->count() === 0) {
                $shop->delete();
            }
        }

        // Revoke tout token Sanctum (y compris "Extension Auto-Connect").
        $user->tokens()->delete();

        // Force a nouveau onboarded_at = null au cas ou un cast aurait persistee une valeur.
        $user->forceFill(['onboarded_at' => null])->save();

        $this->command->info('Onboarding test user ready.');
        $this->command->info('  Login    : onboarding@lesrats.fr / password');
        $this->command->info('  Expected : redirection automatique vers /onboarding apres login.');
    }
}
