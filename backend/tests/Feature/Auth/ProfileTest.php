<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.default'));
        $this->user = User::factory()->create(['password' => 'secret123']);
        $this->token = $this->user->createToken('spa')->plainTextToken;
    }

    public function test_profile_can_be_updated(): void
    {
        $this->withToken($this->token)->putJson('/api/v1/profile', [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'headline' => 'Lifelong learner',
            'bio' => 'I like courses.',
        ])->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.email_verified', false);

        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'email' => 'new@example.com', 'headline' => 'Lifelong learner']);
    }

    public function test_profile_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->withToken($this->token)
            ->putJson('/api/v1/profile', ['name' => 'X Y', 'email' => 'taken@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_profile_update_cannot_change_role(): void
    {
        $this->withToken($this->token)
            ->putJson('/api/v1/profile', ['name' => 'Sneaky', 'email' => $this->user->email, 'role' => 'admin'])
            ->assertOk()
            ->assertJsonPath('data.role', 'student');
    }

    public function test_avatar_can_be_uploaded_and_replaced(): void
    {
        $disk = Storage::disk(config('filesystems.default'));

        $this->withToken($this->token)
            ->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg', 300, 300)])
            ->assertOk();

        $first = $this->user->fresh()->avatar_path;
        $disk->assertExists($first);

        $this->withToken($this->token)
            ->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('me2.png', 300, 300)])
            ->assertOk()
            ->assertJsonPath('data.avatar_url', fn ($url) => str_contains($url, 'avatars/'.$this->user->id));

        $disk->assertMissing($first);
        $disk->assertExists($this->user->fresh()->avatar_path);
    }

    public function test_avatar_upload_rejects_non_images_and_large_files(): void
    {
        $this->withToken($this->token)
            ->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar');

        $this->withToken($this->token)
            ->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('huge.jpg', 300, 300)->size(3000)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar');
    }

    public function test_avatar_can_be_removed(): void
    {
        $this->withToken($this->token)
            ->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg', 300, 300)]);
        $path = $this->user->fresh()->avatar_path;

        $this->withToken($this->token)->deleteJson('/api/v1/profile/avatar')
            ->assertOk()
            ->assertJsonPath('data.avatar_url', null);

        Storage::disk(config('filesystems.default'))->assertMissing($path);
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $this->withToken($this->token)->putJson('/api/v1/profile/password', [
            'current_password' => 'wrong-one1',
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
    }

    public function test_password_change_keeps_this_session_and_signs_out_others(): void
    {
        $this->user->createToken('other-device');

        $this->withToken($this->token)->putJson('/api/v1/profile/password', [
            'current_password' => 'secret123',
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ])->assertOk();

        $this->assertTrue(Hash::check('newsecret123', $this->user->fresh()->password));
        $this->assertSame(1, $this->user->tokens()->count());

        $this->app['auth']->forgetGuards();
        $this->withToken($this->token)->getJson('/api/v1/auth/me')->assertOk();
    }
}
