<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Login
    // -------------------------------------------------------------------------

    public function test_employee_can_login_with_valid_credentials(): void
    {
        $user = $this->createEmployee(['password' => Hash::make('secret123')]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token']);
    }

    public function test_login_returns_user_data_without_password(): void
    {
        $user = $this->createEmployee(['password' => Hash::make('secret123')]);

        $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'secret123',
        ])->assertOk()->assertJsonMissingPath('user.password');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = $this->createEmployee();

        $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_for_unknown_email(): void
    {
        $this->postJson('/api/auth/login', [
            'email'    => 'nobody@example.com',
            'password' => 'password',
        ])->assertStatus(422);
    }

    public function test_login_fails_for_inactive_user(): void
    {
        $user = $this->createEmployee(['is_active' => false, 'password' => Hash::make('password')]);

        $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_rejects_invalid_email_format(): void
    {
        $this->postJson('/api/auth/login', [
            'email'    => 'not-an-email',
            'password' => 'password',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    // -------------------------------------------------------------------------
    // Authenticated profile
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_get_their_profile(): void
    {
        $user = $this->asEmployee();

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonFragment(['id' => $user->id, 'email' => $user->email]);
    }

    public function test_unauthenticated_request_to_me_is_rejected(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // Logout
    //
    // Using actingAs() gives a TransientToken that has no delete() method.
    // We create a real Sanctum token so the controller can call delete() on it.
    // -------------------------------------------------------------------------

    public function test_user_can_logout(): void
    {
        $user  = $this->createEmployee(['password' => Hash::make('password')]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
    }

    public function test_logout_deletes_the_token_from_the_database(): void
    {
        $user  = $this->createEmployee(['password' => Hash::make('password')]);
        $token = $user->createToken('test')->plainTextToken;
        [$tokenId] = explode('|', $token, 2);

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    // -------------------------------------------------------------------------
    // Change password
    // -------------------------------------------------------------------------

    public function test_user_can_change_their_password(): void
    {
        $this->asEmployee(['password' => Hash::make('old-pass-1')]);

        $this->putJson('/api/auth/password', [
            'current_password'      => 'old-pass-1',
            'password'              => 'new-pass-99',
            'password_confirmation' => 'new-pass-99',
        ])->assertOk();
    }

    public function test_change_password_fails_with_wrong_current_password(): void
    {
        $this->asEmployee(['password' => Hash::make('correct')]);

        $this->putJson('/api/auth/password', [
            'current_password'      => 'wrong',
            'password'              => 'new-pass-99',
            'password_confirmation' => 'new-pass-99',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);
    }

    public function test_change_password_requires_confirmation_to_match(): void
    {
        $this->asEmployee(['password' => Hash::make('current')]);

        $this->putJson('/api/auth/password', [
            'current_password'      => 'current',
            'password'              => 'new-pass-99',
            'password_confirmation' => 'does-not-match',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_new_password_must_be_at_least_8_characters(): void
    {
        $this->asEmployee(['password' => Hash::make('current')]);

        $this->putJson('/api/auth/password', [
            'current_password'      => 'current',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    // -------------------------------------------------------------------------
    // Avatar
    // -------------------------------------------------------------------------

    public function test_user_can_update_their_avatar(): void
    {
        $this->asEmployee();

        $avatar = 'data:image/png;base64,' . base64_encode('fake-image-data');

        $this->putJson('/api/auth/avatar', ['avatar' => $avatar])
            ->assertOk()
            ->assertJsonFragment(['avatar' => $avatar]);
    }

    public function test_avatar_field_is_required(): void
    {
        $this->asEmployee();

        $this->putJson('/api/auth/avatar', [])->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Forgot / reset password
    // -------------------------------------------------------------------------

    public function test_forgot_password_always_returns_200_to_prevent_email_enumeration(): void
    {
        $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ])->assertOk();
    }

    public function test_forgot_password_queues_reset_notification_for_existing_user(): void
    {
        Notification::fake();

        $user = $this->createEmployee();

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        Notification::assertSentTo($user, \App\Notifications\ResetPasswordNotification::class);
    }

    public function test_reset_password_with_valid_token_succeeds(): void
    {
        $user  = $this->createEmployee();
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'email'                 => $user->email,
            'token'                 => $token,
            'password'              => 'brand-new-pw',
            'password_confirmation' => 'brand-new-pw',
        ])->assertOk();
    }

    public function test_reset_password_fails_with_invalid_token(): void
    {
        $user = $this->createEmployee();

        $this->postJson('/api/auth/reset-password', [
            'email'                 => $user->email,
            'token'                 => 'invalid-token',
            'password'              => 'brand-new-pw',
            'password_confirmation' => 'brand-new-pw',
        ])->assertStatus(422);
    }

    public function test_reset_password_requires_all_fields(): void
    {
        $this->postJson('/api/auth/reset-password', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'token', 'password']);
    }
}
