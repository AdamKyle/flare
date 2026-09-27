<?php

namespace Tests\Feature\Admin\Kingdoms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class UnitsControllerTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_from_units_index(): void
    {
        $response = $this->call('GET', '/admin/kingdoms/units');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_non_admin_user_is_denied_access_to_units_index(): void
    {
        $response = $this->actingAs($this->createUser())->call('GET', '/admin/kingdoms/units');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_units_index_mounts_the_admin_app_container(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/kingdoms/units');

        $response->assertOk();
        $response->assertSee('id="kingdom-units-admin-app"', false);
    }
}
