<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'ai_photos_count', 'created_at'])
            ->withCount('ownedShops')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.users.index', [
            'users' => $users,
            'adminCount' => User::where('role', User::ROLE_ADMIN)->count(),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:'.User::ROLE_ADMIN.','.User::ROLE_BETA_TESTER],
        ]);

        $newRole = $validated['role'];
        $currentAdmin = $request->user();

        // No-op if role isn't actually changing.
        if ($user->role === $newRole) {
            return redirect()->route('admin.users.index')->with('status', 'role-unchanged');
        }

        // Empêche un admin de se rétrograder lui-même (lockout accidentel).
        if ($user->id === $currentAdmin->id && $newRole !== User::ROLE_ADMIN) {
            throw ValidationException::withMessages([
                'role' => 'Vous ne pouvez pas modifier votre propre rôle.',
            ]);
        }

        // Garde-fou : on refuse de rétrograder le dernier admin restant.
        // Verrou pessimiste pour éviter une race où deux demotions concurrentes
        // passeraient la check séparément.
        DB::transaction(function () use ($user, $newRole) {
            $target = User::where('id', $user->id)->lockForUpdate()->first();

            if ($target->role === User::ROLE_ADMIN && $newRole !== User::ROLE_ADMIN) {
                $remainingAdmins = User::where('role', User::ROLE_ADMIN)
                    ->where('id', '!=', $target->id)
                    ->count();

                if ($remainingAdmins === 0) {
                    throw ValidationException::withMessages([
                        'role' => 'Impossible de rétrograder le dernier admin.',
                    ]);
                }
            }

            // role n'est pas fillable — assignation explicite obligatoire.
            $target->role = $newRole;
            $target->save();
        });

        Log::warning('User role changed via admin UI', [
            'admin_id' => $currentAdmin->id,
            'target_user_id' => $user->id,
            'new_role' => $newRole,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', $newRole === User::ROLE_ADMIN ? 'user-promoted' : 'user-demoted');
    }
}
