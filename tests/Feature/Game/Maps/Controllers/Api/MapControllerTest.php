<?php

namespace Tests\Feature\Game\Maps\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateKingdom;

class MapControllerTest extends TestCase
{
    use CreateGameMap, CreateKingdom, RefreshDatabase;

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
        $this->assertIsArray($data['character_kingdoms']);
        $this->assertIsArray($data['locations']);
        $this->assertIsArray($data['npc_kingdoms']);
        $this->assertIsArray($data['enemy_kingdoms']);
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
}
