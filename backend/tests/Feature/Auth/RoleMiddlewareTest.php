<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Test-only routes exercising the same middleware stack as real role groups.
        Route::middleware(['api', 'auth:sanctum', 'active', 'role:admin'])
            ->get('/api/v1/__test/admin-only', fn () => response()->json(['ok' => true]));
        Route::middleware(['api', 'auth:sanctum', 'active', 'role:instructor,admin'])
            ->get('/api/v1/__test/staff-only', fn () => response()->json(['ok' => true]));
    }

    public function test_admin_route_allows_admins_only(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $this->getJson('/api/v1/__test/admin-only')->assertOk();
    }

    public function test_admin_route_rejects_other_roles_with_the_standard_envelope(): void
    {
        Sanctum::actingAs(User::factory()->instructor()->create());

        $this->getJson('/api/v1/__test/admin-only')
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'You do not have permission to access this resource.',
                'data' => null,
            ]);
    }

    public function test_multiple_roles_can_be_allowed(): void
    {
        Sanctum::actingAs(User::factory()->instructor()->create());
        $this->getJson('/api/v1/__test/staff-only')->assertOk();

        Sanctum::actingAs(User::factory()->student()->create());
        $this->getJson('/api/v1/__test/staff-only')->assertForbidden();
    }
}
