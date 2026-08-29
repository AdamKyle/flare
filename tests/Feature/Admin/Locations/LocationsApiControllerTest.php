<?php

namespace Tests\Feature\Admin\Locations;

use App\Flare\Models\Location;
use App\Game\Maps\Contracts\CoordinatesQuery;
use App\Game\Maps\Values\Coordinates;
use App\Game\Maps\Values\LocationPin;
use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class LocationsApiControllerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateLocation, CreateRole, CreateUser, RefreshDatabase;

    public function test_unauthenticated_request_receives_json_401(): void
    {
        $gameMap = $this->createGameMap();

        $response = $this->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/locations/options', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_non_admin_request_receives_json_403(): void
    {
        $gameMap = $this->createGameMap();
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/locations/options', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_options_returns_exact_shape(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/locations/options');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['id' => $gameMap->id, 'name' => 'Surface'], $data['game_map']);
        $this->assertSame(['x' => [0, 250, 500], 'y' => [0, 250, 500]], $data['coordinates']);
        $this->assertSame([
            LocationPin::CHRISTMAS_TREE->value,
            LocationPin::SNOWMAN->value,
        ], $data['special_pins']);
    }

    public function test_options_returns_only_quest_items(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });
        $this->createItem(['name' => 'Quest Relic', 'type' => 'quest']);
        $this->createItem(['name' => 'Steel Sword', 'type' => 'weapon']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/locations/options');
        $data = json_decode($response->getContent(), true);
        $labels = array_column($data['quest_items'], 'label');

        $this->assertContains('Quest Relic', $labels);
        $this->assertNotContains('Steel Sword', $labels);
    }

    public function test_options_returns_all_location_types(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/locations/options');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(
            array_map(fn (LocationType $locationType): int => $locationType->value, LocationType::cases()),
            $data['location_types']
        );
    }

    public function test_show_returns_exact_response(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'name' => 'Old Church',
            'description' => 'A crumbling church.',
            'x' => 100,
            'y' => 100,
            'hours_to_drop' => 0,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/locations/'.$location->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'id' => $location->id,
            'game_map_id' => $gameMap->id,
            'name' => 'Old Church',
            'description' => 'A crumbling church.',
            'quest_reward_item_id' => null,
            'required_quest_item_id' => null,
            'is_port' => false,
            'can_players_enter' => true,
            'can_auto_battle' => true,
            'x' => 100,
            'y' => 100,
            'type' => null,
            'pin_css_class' => null,
            'hours_to_drop' => 0,
            'minutes_between_delve_fights' => null,
        ], json_decode($response->getContent(), true));
    }

    public function test_show_returns_404_for_location_on_a_different_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $otherMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $otherMap->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/locations/'.$location->id);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_store_creates_location_and_returns_201(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/game-maps/'.$gameMap->id.'/locations',
            [
                'name' => 'Old Church',
                'description' => 'A crumbling church.',
                'quest_reward_item_id' => null,
                'required_quest_item_id' => null,
                'is_port' => false,
                'can_players_enter' => true,
                'can_auto_battle' => true,
                'x' => 250,
                'y' => 250,
                'type' => null,
                'pin_css_class' => null,
                'hours_to_drop' => null,
                'minutes_between_delve_fights' => null,
            ],
        );

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_store_persists_only_allowed_fields(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/game-maps/'.$gameMap->id.'/locations',
            [
                'name' => 'Broken Anvil',
                'description' => 'A crumbling church.',
                'quest_reward_item_id' => null,
                'required_quest_item_id' => null,
                'is_port' => false,
                'can_players_enter' => true,
                'can_auto_battle' => true,
                'x' => 250,
                'y' => 250,
                'type' => null,
                'pin_css_class' => null,
                'hours_to_drop' => 4,
                'minutes_between_delve_fights' => null,
            ],
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame($gameMap->id, $data['game_map_id']);
        $this->assertSame('Broken Anvil', $data['name']);
        $this->assertSame(4, $data['hours_to_drop']);
    }

    public function test_store_does_not_accept_runtime_only_fields(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/game-maps/'.$gameMap->id.'/locations',
            [
                'name' => 'Old Church',
                'description' => 'A crumbling church.',
                'quest_reward_item_id' => null,
                'required_quest_item_id' => null,
                'is_port' => false,
                'can_players_enter' => true,
                'can_auto_battle' => true,
                'x' => 250,
                'y' => 250,
                'type' => null,
                'pin_css_class' => null,
                'hours_to_drop' => null,
                'minutes_between_delve_fights' => null,
                'raid_id' => 999,
                'has_raid_boss' => true,
                'is_corrupted' => true,
            ],
        );

        $this->assertSame(201, $response->getStatusCode());
        $location = Location::find(json_decode($response->getContent(), true)['id']);
        $this->assertNull($location->raid_id);
        $this->assertFalse($location->has_raid_boss);
        $this->assertFalse($location->is_corrupted);
    }

    public function test_store_requires_name_and_description(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/game-maps/'.$gameMap->id.'/locations',
            [
                'name' => '',
                'description' => '',
                'quest_reward_item_id' => null,
                'required_quest_item_id' => null,
                'is_port' => false,
                'can_players_enter' => true,
                'can_auto_battle' => true,
                'x' => 250,
                'y' => 250,
                'type' => null,
                'pin_css_class' => null,
                'hours_to_drop' => null,
                'minutes_between_delve_fights' => null,
            ],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_store_rejects_invalid_location_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/game-maps/'.$gameMap->id.'/locations',
            [
                'name' => 'Old Church',
                'description' => 'A crumbling church.',
                'quest_reward_item_id' => null,
                'required_quest_item_id' => null,
                'is_port' => false,
                'can_players_enter' => true,
                'can_auto_battle' => true,
                'x' => 250,
                'y' => 250,
                'type' => 999,
                'pin_css_class' => null,
                'hours_to_drop' => null,
                'minutes_between_delve_fights' => null,
            ],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_store_rejects_invalid_location_pin(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/game-maps/'.$gameMap->id.'/locations',
            [
                'name' => 'Old Church',
                'description' => 'A crumbling church.',
                'quest_reward_item_id' => null,
                'required_quest_item_id' => null,
                'is_port' => false,
                'can_players_enter' => true,
                'can_auto_battle' => true,
                'x' => 250,
                'y' => 250,
                'type' => null,
                'pin_css_class' => 'invalid-pin',
                'hours_to_drop' => null,
                'minutes_between_delve_fights' => null,
            ],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('pin_css_class', $data['errors']);
    }

    public function test_store_rejects_x_outside_the_coordinate_grid(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/game-maps/'.$gameMap->id.'/locations',
            [
                'name' => 'Old Church',
                'description' => 'A crumbling church.',
                'quest_reward_item_id' => null,
                'required_quest_item_id' => null,
                'is_port' => false,
                'can_players_enter' => true,
                'can_auto_battle' => true,
                'x' => 999,
                'y' => 250,
                'type' => null,
                'pin_css_class' => null,
                'hours_to_drop' => null,
                'minutes_between_delve_fights' => null,
            ],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_store_rejects_y_outside_the_coordinate_grid(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/game-maps/'.$gameMap->id.'/locations',
            [
                'name' => 'Old Church',
                'description' => 'A crumbling church.',
                'quest_reward_item_id' => null,
                'required_quest_item_id' => null,
                'is_port' => false,
                'can_players_enter' => true,
                'can_auto_battle' => true,
                'x' => 250,
                'y' => 999,
                'type' => null,
                'pin_css_class' => null,
                'hours_to_drop' => null,
                'minutes_between_delve_fights' => null,
            ],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_update_modifies_the_location(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'name' => 'Original']);
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'PUT',
            '/api/admin/game-maps/'.$gameMap->id.'/locations/'.$location->id,
            [
                'name' => 'Updated Name',
                'description' => 'A crumbling church.',
                'quest_reward_item_id' => null,
                'required_quest_item_id' => null,
                'is_port' => false,
                'can_players_enter' => true,
                'can_auto_battle' => true,
                'x' => 250,
                'y' => 250,
                'type' => null,
                'pin_css_class' => null,
                'hours_to_drop' => null,
                'minutes_between_delve_fights' => null,
            ],
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Updated Name', $data['name']);
    }

    public function test_update_returns_404_for_location_on_a_different_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $otherMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $otherMap->id]);
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'PUT',
            '/api/admin/game-maps/'.$gameMap->id.'/locations/'.$location->id,
            [
                'name' => 'Old Church',
                'description' => 'A crumbling church.',
                'quest_reward_item_id' => null,
                'required_quest_item_id' => null,
                'is_port' => false,
                'can_players_enter' => true,
                'can_auto_battle' => true,
                'x' => 250,
                'y' => 250,
                'type' => null,
                'pin_css_class' => null,
                'hours_to_drop' => null,
                'minutes_between_delve_fights' => null,
            ],
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_move_updates_only_x_and_y(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'name' => 'Stays The Same', 'x' => 0, 'y' => 0]);
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'PATCH',
            '/api/admin/game-maps/'.$gameMap->id.'/locations/'.$location->id.'/position',
            ['x' => 250, 'y' => 500],
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(250, $data['x']);
        $this->assertSame(500, $data['y']);
        $this->assertSame('Stays The Same', $data['name']);
    }

    public function test_move_returns_404_for_location_on_a_different_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $otherMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $otherMap->id]);
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'PATCH',
            '/api/admin/game-maps/'.$gameMap->id.'/locations/'.$location->id.'/position',
            ['x' => 250, 'y' => 250],
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_move_rejects_coordinates_outside_the_grid(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'PATCH',
            '/api/admin/game-maps/'.$gameMap->id.'/locations/'.$location->id.'/position',
            ['x' => 999, 'y' => 999],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }
}
