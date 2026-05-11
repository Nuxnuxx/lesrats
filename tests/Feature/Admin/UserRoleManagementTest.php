<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function betaTester(): User
    {
        return User::factory()->create(['role' => User::ROLE_BETA_TESTER]);
    }

    public function test_guests_cannot_view_users_page(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_beta_testers_cannot_view_users_page(): void
    {
        $this->actingAs($this->betaTester())
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_beta_testers_cannot_promote_anyone(): void
    {
        $attacker = $this->betaTester();
        $target = $this->betaTester();

        $this->actingAs($attacker)
            ->patch(route('admin.users.role', $target), ['role' => User::ROLE_ADMIN])
            ->assertForbidden();

        $this->assertSame(User::ROLE_BETA_TESTER, $target->fresh()->role);
    }

    public function test_admin_can_view_users_page(): void
    {
        // Need at least one admin + one beta to render the table meaningfully
        $admin = $this->admin();
        $this->betaTester();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Tous les utilisateurs');
    }

    public function test_admin_can_promote_beta_tester(): void
    {
        $admin = $this->admin();
        $target = $this->betaTester();

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $target), ['role' => User::ROLE_ADMIN])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'user-promoted');

        $this->assertSame(User::ROLE_ADMIN, $target->fresh()->role);
    }

    public function test_admin_can_demote_another_admin(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $otherAdmin), ['role' => User::ROLE_BETA_TESTER])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'user-demoted');

        $this->assertSame(User::ROLE_BETA_TESTER, $otherAdmin->fresh()->role);
    }

    public function test_admin_cannot_demote_themselves(): void
    {
        $admin = $this->admin();
        // Make sure they aren't the only admin so we test the self-demote rule specifically
        $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $admin), ['role' => User::ROLE_BETA_TESTER])
            ->assertSessionHasErrors('role');

        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
    }

    public function test_cannot_demote_last_remaining_admin(): void
    {
        // Two admins exist. Admin A demotes Admin B → fine.
        // Then Admin A is the last. They cannot demote themselves (self rule),
        // and no other admin can target them — so the system always retains ≥1 admin.
        $adminA = $this->admin();
        $adminB = $this->admin();

        // A demotes B — leaves A as the only admin.
        $this->actingAs($adminA)
            ->patch(route('admin.users.role', $adminB), ['role' => User::ROLE_BETA_TESTER])
            ->assertSessionHas('status', 'user-demoted');

        $this->assertSame(1, User::where('role', User::ROLE_ADMIN)->count());

        // A tries to demote themselves — blocked by the self rule.
        $this->actingAs($adminA)
            ->patch(route('admin.users.role', $adminA), ['role' => User::ROLE_BETA_TESTER])
            ->assertSessionHasErrors('role');

        $this->assertSame(User::ROLE_ADMIN, $adminA->fresh()->role);
        $this->assertSame(1, User::where('role', User::ROLE_ADMIN)->count());
    }

    public function test_invalid_role_value_is_rejected(): void
    {
        $admin = $this->admin();
        $target = $this->betaTester();

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $target), ['role' => 'super_admin'])
            ->assertSessionHasErrors('role');

        $this->assertSame(User::ROLE_BETA_TESTER, $target->fresh()->role);
    }

    public function test_role_field_cannot_be_mass_assigned_via_profile_update(): void
    {
        // Defense-in-depth: even if a beta tester tries to fill `role`
        // through their profile update, it must be ignored.
        $user = $this->betaTester();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertSame(User::ROLE_BETA_TESTER, $user->fresh()->role);
    }

    // --- /register is gone ---------------------------------------------------

    public function test_register_route_no_longer_exists(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'X', 'email' => 'x@x.com',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertNotFound();
    }

    // --- Create user --------------------------------------------------------

    public function test_admin_can_view_create_user_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Créer un utilisateur');
    }

    public function test_beta_tester_cannot_view_create_user_page(): void
    {
        $this->actingAs($this->betaTester())
            ->get(route('admin.users.create'))
            ->assertForbidden();
    }

    public function test_admin_can_create_user(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Tester',
                'email' => 'new@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => User::ROLE_BETA_TESTER,
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'user-created');

        $created = User::where('email', 'new@example.com')->first();
        $this->assertNotNull($created);
        $this->assertSame(User::ROLE_BETA_TESTER, $created->role);
        $this->assertNotNull($created->email_verified_at);
    }

    public function test_admin_can_create_another_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Other Admin',
                'email' => 'admin2@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => User::ROLE_ADMIN,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(User::ROLE_ADMIN, User::where('email', 'admin2@example.com')->value('role'));
    }

    public function test_create_user_rejects_duplicate_email(): void
    {
        $existing = $this->betaTester();

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'X',
                'email' => $existing->email,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => User::ROLE_BETA_TESTER,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_create_user_rejects_invalid_role(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'X',
                'email' => 'x@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => 'super_admin',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    public function test_create_user_requires_password_confirmation(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'X',
                'email' => 'x@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'DifferentPassword!',
                'role' => User::ROLE_BETA_TESTER,
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_beta_tester_cannot_create_user(): void
    {
        $this->actingAs($this->betaTester())
            ->post(route('admin.users.store'), [
                'name' => 'X',
                'email' => 'x@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => User::ROLE_BETA_TESTER,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    // --- Delete user --------------------------------------------------------

    public function test_admin_can_delete_beta_tester(): void
    {
        $admin = $this->admin();
        $target = $this->betaTester();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'user-deleted');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = $this->admin();
        // Another admin must exist so the last-admin guard isn't the one stopping us.
        $this->admin();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_delete_last_admin(): void
    {
        $adminA = $this->admin();
        $adminB = $this->admin();

        // A deletes B → fine (still one admin left, $adminA).
        $this->actingAs($adminA)
            ->delete(route('admin.users.destroy', $adminB))
            ->assertSessionHas('status', 'user-deleted');

        $this->assertSame(1, User::where('role', User::ROLE_ADMIN)->count());

        // A then tries to delete themselves → blocked by self rule (defense in depth).
        $this->actingAs($adminA)
            ->delete(route('admin.users.destroy', $adminA))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', ['id' => $adminA->id]);
    }

    public function test_beta_tester_cannot_delete_anyone(): void
    {
        $attacker = $this->betaTester();
        $target = $this->betaTester();

        $this->actingAs($attacker)
            ->delete(route('admin.users.destroy', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }
}
