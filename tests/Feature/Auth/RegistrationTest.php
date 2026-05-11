<?php

namespace Tests\Feature\Auth;

use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function freshCode(): InvitationCode
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        return InvitationCode::create([
            'code' => 'TESTCODE12345678',
            'created_by' => $admin->id,
        ]);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_new_users_can_register_with_valid_invitation_code(): void
    {
        $code = $this->freshCode();

        $response = $this->post('/register', [
            'invitation_code' => $code->code,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(User::ROLE_BETA_TESTER, $user->role);

        // Code is now consumed.
        $code->refresh();
        $this->assertSame($user->id, $code->used_by);
        $this->assertNotNull($code->used_at);
    }

    public function test_registration_fails_without_invitation_code(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('invitation_code');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_fails_with_invalid_invitation_code(): void
    {
        $response = $this->post('/register', [
            'invitation_code' => 'INVALIDCODE00000',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('invitation_code');
        $this->assertGuest();
    }

    public function test_registration_fails_with_already_used_invitation_code(): void
    {
        $code = $this->freshCode();
        // Mark code as already used by another user.
        $other = User::factory()->create();
        $code->used_by = $other->id;
        $code->used_at = now();
        $code->save();

        $response = $this->post('/register', [
            'invitation_code' => $code->code,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('invitation_code');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
