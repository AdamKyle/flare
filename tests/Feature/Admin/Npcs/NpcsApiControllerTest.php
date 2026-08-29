<?php

namespace Tests\Feature\Admin\Npcs;

use App\Game\Maps\Contracts\CoordinatesQuery;
use App\Game\Maps\Values\Coordinates;
use App\Game\Npcs\Values\NpcType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class NpcsApiControllerTest extends TestCase
{
    use CreateGameMap, CreateNpc, CreateRole, CreateUser, RefreshDatabase;

    public function test_unauthenticated_request_receives_json_401(): void
    {
        $gameMap = $this->createGameMap();

        $response = $this->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/npcs/options', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_non_admin_request_receives_json_403(): void
    {
        $gameMap = $this->createGameMap();
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/npcs/options', [], [], [], [
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

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/npcs/options');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['id' => $gameMap->id, 'name' => 'Surface'], $data['game_map']);
        $this->assertSame(['x' => [0, 250, 500], 'y' => [0, 250, 500]], $data['coordinates']);
    }

    public function test_options_returns_all_npc_types(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/npcs/options');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(
            array_map(fn (NpcType $npcType): int => $npcType->value, NpcType::cases()),
            $data['npc_types']
        );
    }

    public function test_show_returns_exact_response(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $npc = $this->createNpc([
            'game_map_id' => $gameMap->id,
            'real_name' => 'Old Merchant',
            'name' => 'OldMerchant',
            'type' => NpcType::QUEST_GIVER->value,
            'x_position' => 32,
            'y_position' => 64,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/npcs/'.$npc->id);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertArrayNotHasKey('type_name', $data);
        $this->assertSame([
            'id' => $npc->id,
            'game_map_id' => $gameMap->id,
            'name' => 'OldMerchant',
            'real_name' => 'Old Merchant',
            'type' => NpcType::QUEST_GIVER->value,
            'x_position' => 32,
            'y_position' => 64,
        ], $data);
    }

    public function test_show_returns_404_for_npc_on_a_different_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $otherMap = $this->createGameMap();
        $npc = $this->createNpc(['game_map_id' => $otherMap->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/npcs/'.$npc->id);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_store_creates_npc_and_returns_201(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/game-maps/'.$gameMap->id.'/npcs',
            [
                'real_name' => 'Old Merchant',
                'type' => NpcType::KINGDOM_HOLDER->value,
                'x_position' => 250,
                'y_position' => 250,
            ],
        );

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_store_derives_name_by_removing_spaces_from_real_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/game-maps/'.$gameMap->id.'/npcs',
            [
                'real_name' => 'The Old Merchant',
                'type' => NpcType::KINGDOM_HOLDER->value,
                'x_position' => 250,
                'y_position' => 250,
            ],
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame('TheOldMerchant', $data['name']);
    }

    public function test_store_requires_real_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/game-maps/'.$gameMap->id.'/npcs',
            [
                'real_name' => '',
                'type' => NpcType::KINGDOM_HOLDER->value,
                'x_position' => 250,
                'y_position' => 250,
            ],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_store_rejects_invalid_npc_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/game-maps/'.$gameMap->id.'/npcs',
            [
                'real_name' => 'Old Merchant',
                'type' => 999,
                'x_position' => 250,
                'y_position' => 250,
            ],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_store_rejects_x_position_outside_the_coordinate_grid(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/game-maps/'.$gameMap->id.'/npcs',
            [
                'real_name' => 'Old Merchant',
                'type' => NpcType::KINGDOM_HOLDER->value,
                'x_position' => 999,
                'y_position' => 250,
            ],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_store_rejects_y_position_outside_the_coordinate_grid(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/game-maps/'.$gameMap->id.'/npcs',
            [
                'real_name' => 'Old Merchant',
                'type' => NpcType::KINGDOM_HOLDER->value,
                'x_position' => 250,
                'y_position' => 999,
            ],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_update_modifies_the_npc_and_regenerates_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $npc = $this->createNpc(['game_map_id' => $gameMap->id, 'real_name' => 'Original', 'name' => 'Original']);
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'PUT',
            '/api/admin/game-maps/'.$gameMap->id.'/npcs/'.$npc->id,
            [
                'real_name' => 'Brand New Name',
                'type' => NpcType::KINGDOM_HOLDER->value,
                'x_position' => 250,
                'y_position' => 250,
            ],
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Brand New Name', $data['real_name']);
        $this->assertSame('BrandNewName', $data['name']);
    }

    public function test_update_returns_404_for_npc_on_a_different_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $otherMap = $this->createGameMap();
        $npc = $this->createNpc(['game_map_id' => $otherMap->id]);
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'PUT',
            '/api/admin/game-maps/'.$gameMap->id.'/npcs/'.$npc->id,
            [
                'real_name' => 'Old Merchant',
                'type' => NpcType::KINGDOM_HOLDER->value,
                'x_position' => 250,
                'y_position' => 250,
            ],
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_move_updates_only_coordinates(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $npc = $this->createNpc([
            'game_map_id' => $gameMap->id,
            'real_name' => 'Stays The Same',
            'name' => 'StaysTheSame',
            'x_position' => 0,
            'y_position' => 0,
        ]);
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'PATCH',
            '/api/admin/game-maps/'.$gameMap->id.'/npcs/'.$npc->id.'/position',
            ['x_position' => 250, 'y_position' => 500],
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(250, $data['x_position']);
        $this->assertSame(500, $data['y_position']);
        $this->assertSame('Stays The Same', $data['real_name']);
    }

    public function test_move_returns_404_for_npc_on_a_different_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $otherMap = $this->createGameMap();
        $npc = $this->createNpc(['game_map_id' => $otherMap->id]);
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'PATCH',
            '/api/admin/game-maps/'.$gameMap->id.'/npcs/'.$npc->id.'/position',
            ['x_position' => 250, 'y_position' => 250],
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_move_rejects_coordinates_outside_the_grid(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $npc = $this->createNpc(['game_map_id' => $gameMap->id]);
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call(
            'PATCH',
            '/api/admin/game-maps/'.$gameMap->id.'/npcs/'.$npc->id.'/position',
            ['x_position' => 999, 'y_position' => 999],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }
}
