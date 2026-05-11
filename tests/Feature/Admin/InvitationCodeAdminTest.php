<?php

namespace Tests\Feature\Admin;

use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationCodeAdminTest extends TestCase
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

    public function test_guests_cannot_access_invitations_page(): void
    {
        $this->get(route('admin.invitations.index'))->assertRedirect(route('login'));
    }

    public function test_beta_testers_get_403_on_invitations_index(): void
    {
        $this->actingAs($this->betaTester())
            ->get(route('admin.invitations.index'))
            ->assertForbidden();
    }

    public function test_beta_testers_cannot_create_invitation(): void
    {
        $this->actingAs($this->betaTester())
            ->post(route('admin.invitations.store'))
            ->assertForbidden();

        $this->assertDatabaseCount('invitation_codes', 0);
    }

    public function test_admin_can_view_invitations_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.invitations.index'))
            ->assertOk()
            ->assertSee('Générer un code');
    }

    public function test_admin_can_generate_invitation_code(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.invitations.store'))
            ->assertRedirect(route('admin.invitations.index'))
            ->assertSessionHas('new_invitation_code');

        $this->assertDatabaseCount('invitation_codes', 1);

        $code = InvitationCode::first();
        $this->assertSame($admin->id, $code->created_by);
        $this->assertNull($code->used_at);
        $this->assertSame(16, strlen($code->code));
    }

    public function test_admin_can_delete_unused_code(): void
    {
        $admin = $this->admin();
        $code = InvitationCode::create([
            'code' => 'ABCDEF1234567890',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.invitations.destroy', $code))
            ->assertRedirect(route('admin.invitations.index'));

        $this->assertDatabaseMissing('invitation_codes', ['id' => $code->id]);
    }

    public function test_admin_cannot_delete_used_code(): void
    {
        $admin = $this->admin();
        $beta = $this->betaTester();

        $code = InvitationCode::create([
            'code' => 'USEDCODEUSEDCODE',
            'created_by' => $admin->id,
            'used_by' => $beta->id,
            'used_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.invitations.destroy', $code))
            ->assertRedirect(route('admin.invitations.index'))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('invitation_codes', ['id' => $code->id]);
    }
}
