<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Learner',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'student',
        ], $overrides);
    }

    public function test_a_student_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->registrationData());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'jane@example.com')
            ->assertJsonPath('data.user.role', 'student')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['success', 'message', 'data' => ['user', 'token', 'expires_at'], 'errors', 'meta']);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'role' => 'student']);
        $this->assertNotSame('secret123', User::first()->getRawOriginal('password'));
    }

    public function test_an_instructor_can_register(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registrationData(['role' => 'instructor']))
            ->assertCreated()
            ->assertJsonPath('data.user.role', 'instructor');
    }

    public function test_nobody_can_self_register_as_admin(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registrationData(['role' => 'admin']))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_validates_input(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'taken@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
            'role' => 'student',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_a_user_can_log_in_with_correct_credentials(): void
    {
        $user = User::factory()->instructor()->create(['email' => 'teach@example.com', 'password' => 'secret123']);

        $this->postJson('/api/v1/auth/login', ['email' => 'TEACH@example.com ', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.role', 'instructor');

        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_login_fails_with_wrong_password_without_revealing_which_field(): void
    {
        User::factory()->create(['email' => 'jane@example.com', 'password' => 'secret123']);

        $wrongPassword = $this->postJson('/api/v1/auth/login', ['email' => 'jane@example.com', 'password' => 'nope12345']);
        $unknownEmail = $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'nope12345']);

        $wrongPassword->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertSame($wrongPassword->json('errors.email'), $unknownEmail->json('errors.email'));
    }

    public function test_a_deactivated_user_cannot_log_in(): void
    {
        User::factory()->inactive()->create(['email' => 'off@example.com', 'password' => 'secret123']);

        $this->postJson('/api/v1/auth/login', ['email' => 'off@example.com', 'password' => 'secret123'])
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'jane@example.com', 'password' => 'secret123']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'jane@example.com', 'password' => 'wrong-pass1'])
                ->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', ['email' => 'jane@example.com', 'password' => 'secret123'])
            ->assertTooManyRequests()
            ->assertJsonPath('success', false);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated. Please log in.');
    }

    public function test_me_returns_the_current_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('spa')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.password');
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('spa')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertSame(0, $user->tokens()->count());

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_a_deactivated_user_with_a_valid_token_is_blocked_and_logged_out(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('spa')->plainTextToken;
        $user->forceFill(['is_active' => false])->save();

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertForbidden()
            ->assertJsonPath('errors.account.0', 'deactivated');
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_role_cannot_be_escalated_through_the_register_payload(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registrationData(['role' => 'student', 'is_active' => false]))
            ->assertCreated();

        $user = User::first();
        $this->assertSame(UserRole::Student, $user->role);
        $this->assertTrue($user->is_active);
    }
}
