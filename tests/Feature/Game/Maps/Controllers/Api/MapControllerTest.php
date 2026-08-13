<?php

namespace Tests\Feature\Game\Maps\Controllers\Api;

use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Maps\Values\MapTileValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class MapControllerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateKingdom, CreateLocation, CreateMonster, RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_map_information_returns_complete_top_level_shape(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertArrayHasKey('tiles', $data);
        $this->assertArrayHasKey('character_kingdoms', $data);
        $this->assertArrayHasKey('locations', $data);
        $this->assertArrayHasKey('npc_kingdoms', $data);
        $this->assertArrayHasKey('enemy_kingdoms', $data);
        $this->assertArrayHasKey('character_position', $data);
        $this->assertArrayHasKey('time_out_details', $data);
        $this->assertArrayHasKey('has_conjurable_celestials', $data);
        $this->assertIsArray($data['character_kingdoms']);
        $this->assertIsArray($data['locations']);
        $this->assertIsArray($data['npc_kingdoms']);
        $this->assertIsArray($data['enemy_kingdoms']);
        $this->assertIsBool($data['has_conjurable_celestials']);
    }

    public function test_map_information_has_conjurable_celestials_is_false_when_current_map_has_no_eligible_celestial(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => false,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['has_conjurable_celestials']);
    }

    public function test_map_information_has_conjurable_celestials_is_true_when_current_map_has_eligible_celestial(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => true,
            'celestial_type' => null,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['has_conjurable_celestials']);
    }

    public function test_map_information_has_conjurable_celestials_is_false_when_eligible_celestial_only_on_another_map(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $otherMap = $this->createGameMap(['name' => 'Other Map', 'path' => 'path']);

        $this->createMonster([
            'game_map_id' => $otherMap->id,
            'is_celestial_entity' => true,
            'celestial_type' => null,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['has_conjurable_celestials']);
    }

    public function test_map_information_returns_the_stored_tile_grid_unchanged(): void
    {
        $tileGrid = [['tile-a.png', 'tile-b.png'], ['tile-c.png', 'tile-d.png']];

        $gameMap = $this->createGameMap([
            'name' => 'Surface',
            'path' => 'path',
            'default' => true,
            'tile_map' => $tileGrid,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame($tileGrid, $data['tiles']);
    }

    public function test_map_information_returns_an_empty_array_when_the_stored_tile_grid_is_null(): void
    {
        $gameMap = $this->createGameMap([
            'name' => 'Surface',
            'path' => 'path',
            'default' => true,
            'tile_map' => null,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertArrayHasKey('tiles', $data);
        $this->assertSame([], $data['tiles']);
    }

    public function test_map_information_returns_numeric_character_position(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(20, 25)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $this->assertSame(20, $data['character_position']['x_position']);
        $this->assertSame(25, $data['character_position']['y_position']);
    }

    public function test_map_information_returns_time_out_details(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('can_move', $data['time_out_details']);
        $this->assertArrayHasKey('time_left', $data['time_out_details']);
        $this->assertArrayHasKey('show_timer', $data['time_out_details']);
        $this->assertTrue($data['time_out_details']['can_move']);
        $this->assertFalse($data['time_out_details']['show_timer']);
    }

    public function test_map_information_returns_condensed_character_kingdom_rows(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createKingdom([
            'character_id' => $character->id,
            'game_map_id' => $character->map->game_map_id,
            'name' => 'My Kingdom',
            'x_position' => 10,
            'y_position' => 10,
            'npc_owned' => false,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $this->assertCount(1, $data['character_kingdoms']);
        $this->assertSame([
            'id' => $data['character_kingdoms'][0]['id'],
            'name' => 'My Kingdom',
            'x_position' => 10,
            'y_position' => 10,
        ], $data['character_kingdoms'][0]);
    }

    public function test_map_information_returns_condensed_npc_kingdom_rows(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createKingdom([
            'character_id' => null,
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Npc Kingdom',
            'x_position' => 30,
            'y_position' => 30,
            'npc_owned' => true,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $this->assertCount(1, $data['npc_kingdoms']);
        $this->assertSame([
            'id' => $data['npc_kingdoms'][0]['id'],
            'name' => 'Npc Kingdom',
            'x_position' => 30,
            'y_position' => 30,
        ], $data['npc_kingdoms'][0]);
    }

    public function test_map_information_returns_condensed_enemy_kingdom_rows_and_excludes_own_kingdom(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createKingdom([
            'character_id' => $character->id,
            'game_map_id' => $character->map->game_map_id,
            'name' => 'My Kingdom',
            'npc_owned' => false,
        ]);

        $this->createKingdom([
            'character_id' => $otherCharacter->id,
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Enemy Kingdom',
            'x_position' => 40,
            'y_position' => 40,
            'npc_owned' => false,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $this->assertCount(1, $data['enemy_kingdoms']);
        $this->assertSame([
            'id' => $data['enemy_kingdoms'][0]['id'],
            'name' => 'Enemy Kingdom',
            'x_position' => 40,
            'y_position' => 40,
        ], $data['enemy_kingdoms'][0]);
    }

    public function test_map_information_returns_empty_arrays_for_kingdom_categories_with_no_records(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $this->assertSame([], $data['character_kingdoms']);
        $this->assertSame([], $data['npc_kingdoms']);
        $this->assertSame([], $data['enemy_kingdoms']);
    }

    public function test_traverse_blocks_when_character_is_missing_required_item_for_destination_plane(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $hellMap = $this->createGameMap(['name' => 'Hell', 'path' => 'path']);

        $response = $this->actingAs($character->user)
            ->postJson('/api/map/traverse/'.$character->id, ['map_id' => $hellMap->id]);

        $response->assertStatus(422);
        $this->assertSame(
            'You are missing a required item to travel to that plane.',
            json_decode($response->getContent(), true)['message']
        );
    }

    public function test_traverse_moves_character_to_new_plane_when_required_item_is_owned(): void
    {
        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock): void {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
            })
        );

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $hellMap = $this->createGameMap(['name' => 'Hell', 'path' => 'path']);

        $hellPassItem = $this->createItem(['effect' => ItemEffectType::HELL->value]);

        $characterFactory->inventoryManagement()->giveItem($hellPassItem);

        $response = $this->actingAs($character->user)
            ->postJson('/api/map/traverse/'.$character->id, ['map_id' => $hellMap->id]);

        $response->assertOk();
        $this->assertSame($hellMap->id, $character->refresh()->map->game_map_id);
    }

    public function test_fetch_teleport_coordinates_returns_teleport_location_shape(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/map/teleport-coordinates/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertArrayHasKey('character_kingdoms', $data);
        $this->assertArrayHasKey('npc_kingdoms', $data);
        $this->assertArrayHasKey('enemy_kingdoms', $data);
        $this->assertArrayHasKey('locations', $data);
        $this->assertArrayHasKey('coordinates', $data);
    }

    public function test_fetch_set_sail_ports_returns_condensed_current_port_and_destination_list(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16)
            ->updateCharacter(['gold' => 5000])
            ->getCharacter();

        $currentPort = $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Current Port',
            'x' => 16,
            'y' => 16,
            'is_port' => true,
        ]);

        $destinationPort = $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Destination Port',
            'x' => 80,
            'y' => 16,
            'is_port' => true,
        ]);

        $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Not A Port',
            'x' => 50,
            'y' => 50,
            'is_port' => false,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/map/set-sail-ports/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame([
            'id' => $currentPort->id,
            'name' => 'Current Port',
            'x' => 16,
            'y' => 16,
        ], $data['current_port']);

        $this->assertCount(1, $data['port_list']);
        $this->assertSame([
            'id' => $destinationPort->id,
            'name' => 'Destination Port',
            'x' => 80,
            'y' => 16,
            'distance' => 64,
            'time' => 1,
            'cost' => 1000,
            'can_afford' => true,
        ], $data['port_list'][0]);
        $this->assertFalse(collect($data['port_list'])->contains('id', $currentPort->id));
    }

    public function test_fetch_set_sail_ports_excludes_ports_on_a_different_map(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16)
            ->updateCharacter(['gold' => 5000])
            ->getCharacter();

        $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Current Port',
            'x' => 16,
            'y' => 16,
            'is_port' => true,
        ]);

        $destinationPort = $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Destination Port',
            'x' => 80,
            'y' => 16,
            'is_port' => true,
        ]);

        $otherMap = $this->createGameMap(['name' => 'Other Map', 'path' => 'path']);

        $otherMapPort = $this->createLocation([
            'game_map_id' => $otherMap->id,
            'name' => 'Other Map Port',
            'x' => 80,
            'y' => 16,
            'is_port' => true,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/map/set-sail-ports/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertTrue(collect($data['port_list'])->contains('id', $destinationPort->id));
        $this->assertFalse(collect($data['port_list'])->contains('id', $otherMapPort->id));
    }

    public function test_fetch_set_sail_ports_returns_422_when_character_is_not_standing_on_a_port(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16)->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/map/set-sail-ports/'.$character->id);

        $response->assertStatus(422);
        $this->assertSame(
            'Invalid port location.',
            json_decode($response->getContent(), true)['message']
        );
    }

    public function test_get_location_information_returns_wrapped_location_data(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $location = $this->createLocation([
            'game_map_id' => $character->map->gameMap->id,
            'name' => 'The Old Bridge',
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/map/location-details/'.$location->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame($location->id, $data['data']['id']);
        $this->assertSame('The Old Bridge', $data['data']['name']);
        $this->assertNull($data['data']['quest_reward_item']['data']);
        $this->assertNull($data['data']['required_quest_item']);
    }

    public function test_get_location_information_includes_transformed_quest_reward_item(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $questItem = $this->createItem(['name' => 'Ancient Coin']);

        $location = $this->createLocation([
            'game_map_id' => $character->map->gameMap->id,
            'quest_reward_item_id' => $questItem->id,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/map/location-details/'.$location->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame($questItem->id, $data['data']['quest_reward_item']['data']['item_id']);
        $this->assertSame('Ancient Coin', $data['data']['quest_reward_item']['data']['name']);
    }

    public function test_get_location_droppable_quest_items_returns_paginated_shape(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $location = $this->createLocation([
            'game_map_id' => $character->map->gameMap->id,
        ]);

        $this->createItem(['name' => 'Rusty Key', 'drop_location_id' => $location->id]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/map/location-droppable-items/'.$location->id.'?per_page=10&page=1&search_text=');

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('can_load_more', $data['meta']);
        $this->assertSame('Rusty Key', $data['data'][0]['name']);
    }
}
