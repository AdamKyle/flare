<?php

namespace Tests\Feature\Admin\Locations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class LocationsControllerTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_from_locations_index(): void
    {
        $response = $this->call('GET', '/admin/locations');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_non_admin_user_is_denied_access_to_locations_index(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/locations');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_admin_can_view_locations_index(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/locations');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_locations_index_mounts_the_admin_app_container(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/locations');

        $response->assertSee('id="locations-admin-app"', false);
    }
}
