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
