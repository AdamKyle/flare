<?php

namespace Tests\Feature\Admin\Locations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class LocationsLegacyRouteTest extends TestCase
{
    use CreateGameMap, CreateLocation, CreateRole, CreateUser, RefreshDatabase;

    public function test_legacy_location_information_route_renders_for_admin(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $location = $this->createLocation(['game_map_id' => $this->createGameMap()->id]);

        $response = $this->actingAs($admin)->call('GET', '/admin/location/'.$location->id);

        $this->assertSame(200, $response->getStatusCode());
        $response->assertSee($location->name);
    }

    public function test_legacy_location_edit_route_redirects_into_the_modern_app(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $location = $this->createLocation(['game_map_id' => $this->createGameMap()->id]);

        $response = $this->actingAs($admin)->call('GET', '/admin/locations/'.$location->id.'/edit');

        $this->assertSame(302, $response->getStatusCode());
        $response->assertRedirect(route('admin.locations.index'));
    }
}
