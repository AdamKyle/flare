<?php

namespace Tests\Feature\Admin\Middleware;

use App\Admin\Middleware\IsAdminMiddleware;
use App\Flare\Models\Role;
use App\Flare\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class IsAdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_json_request_returns401(): void
    {
        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = (new IsAdminMiddleware)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_authenticated_non_admin_json_request_returns403(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $request->setUserResolver(fn () => $user);

        $response = (new IsAdminMiddleware)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_authenticated_admin_request_passes_through(): void
    {
        $role = Role::firstOrCreate(['name' => 'Admin']);
        $user = User::factory()->create();
        $user->assignRole($role->name);
        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $request->setUserResolver(fn () => $user);

        $response = (new IsAdminMiddleware)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }
}
