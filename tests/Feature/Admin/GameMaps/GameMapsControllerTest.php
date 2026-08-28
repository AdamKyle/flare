<?php

namespace Tests\Feature\Admin\GameMaps;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class GameMapsControllerTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_from_game_maps_index(): void
    {
        $response = $this->call('GET', '/admin/game-maps');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_non_admin_user_is_denied_access_to_game_maps_index(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/game-maps');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_admin_can_view_game_maps_index(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/game-maps');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_game_maps_index_mounts_the_admin_app_container(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/game-maps');

        $response->assertSee('id="game-maps-admin-app"', false);
    }

    public function test_game_maps_index_does_not_render_serialized_resource_data(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/game-maps');

        $this->assertStringNotContainsString('window.gameMaps', $response->getContent());
        $this->assertStringNotContainsString('data-game-maps=', $response->getContent());
    }
}
