<?php

namespace Tests\Feature\Admin\GameMaps;

use App\Flare\MapGenerator\Services\MapTileGenerationService;
use App\Flare\MapGenerator\Values\PreparedMapTileReplacement;
use App\Flare\Models\GameMap;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Events\Values\EventType;
use App\Game\Maps\Contracts\CoordinatesQuery;
use App\Game\Maps\Values\Coordinates;
use App\Game\Npcs\Values\NpcType;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class GameMapsApiControllerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateKingdom, CreateLocation, CreateNpc, CreateQuest, CreateRole, CreateUser, RefreshDatabase;

    public function test_unauthenticated_request_receives_json_401(): void
    {
        $response = $this->call('GET', '/api/admin/game-maps', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_non_admin_request_receives_json_403(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/game-maps', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_index_returns_paginated_response_shape(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMap(['name' => 'Surface']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('pagination', $data['meta']);
        $this->assertArrayHasKey('can_load_more', $data['meta']);
    }

    public function test_index_filters_by_name_search_text(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMap(['name' => 'Surface Realm']);
        $this->createGameMap(['name' => 'Shadow Plane']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps', ['search_text' => 'Surface']);
        $data = json_decode($response->getContent(), true);

        $this->assertCount(1, $data['data']);
        $this->assertSame('Surface Realm', $data['data'][0]['name']);
    }

    public function test_index_sorts_ascending_by_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMap(['name' => 'Zeta']);
        $this->createGameMap(['name' => 'Alpha']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps', [
            'sort_key' => 'name',
            'sort_direction' => 'asc',
        ]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('Alpha', $data['data'][0]['name']);
    }

    public function test_index_sorts_descending_by_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMap(['name' => 'Zeta']);
        $this->createGameMap(['name' => 'Alpha']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps', [
            'sort_key' => 'name',
            'sort_direction' => 'desc',
        ]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('Zeta', $data['data'][0]['name']);
    }

    public function test_index_rejects_invalid_sort_direction(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMap();

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps', [
            'sort_direction' => 'sideways',
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_index_rejects_invalid_sort_key(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMap();

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps', [
            'sort_key' => 'not_a_real_column',
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_index_rejects_per_page_above_the_allowed_maximum(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMap();

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps', [
            'per_page' => 101,
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_index_rejects_page_below_one(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMap();

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps', [
            'page' => 0,
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_show_returns_exact_detail_shape(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap([
            'name' => 'Surface',
            'path' => 'surface.png',
            'tile_map' => [['tile-a']],
            'can_traverse' => false,
            'character_attack_reduction' => 0,
            'only_during_event_type' => EventType::WINTER_EVENT,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'id' => $gameMap->id,
            'name' => 'Surface',
            'map_url' => Storage::disk('maps')->url('surface.png'),
            'tiles' => [['tile-a']],
            'description' => null,
            'kingdom_color' => '#ffffff',
            'default' => true,
            'can_traverse' => false,
            'event_restriction' => EventType::WINTER_EVENT,
            'xp_bonus' => null,
            'skill_training_bonus' => null,
            'drop_chance_bonus' => null,
            'enemy_stat_bonus' => null,
            'character_attack_reduction' => 0,
            'required_location' => null,
            'required_quest_item' => null,
        ], json_decode($response->getContent(), true));
    }

    public function test_show_returns_null_required_quest_item_without_an_item(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Surface']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($data['required_quest_item']);
    }

    public function test_show_returns_the_stored_markdown_description_unchanged(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['description' => '## The Frozen Reach']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id);

        $response->assertStatus(200);
        $this->assertSame('## The Frozen Reach', $response->json('description'));
    }

    public function test_show_returns_required_item_with_null_quest_when_no_quest_matches(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Labyrinth']);
        $item = $this->createItem(['name' => 'Labyrinth Key', 'effect' => ItemEffectType::LABYRINTH->value]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($item->id, $data['required_quest_item']['id']);
        $this->assertSame('Labyrinth Key', $data['required_quest_item']['name']);
        $this->assertNull($data['required_quest_item']['quest']);
    }

    public function test_show_returns_required_item_and_rewarding_quest(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Labyrinth']);
        $item = $this->createItem(['name' => 'Labyrinth Key', 'effect' => ItemEffectType::LABYRINTH->value]);
        $quest = $this->createQuest([
            'name' => 'The Lost Survey',
            'npc_id' => $this->createNpc()->id,
            'reward_item' => $item->id,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'id' => $quest->id,
            'name' => 'The Lost Survey',
        ], $data['required_quest_item']['quest']);
    }

    public function test_show_does_not_return_a_quest_that_only_requires_the_item(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Labyrinth']);
        $item = $this->createItem(['name' => 'Labyrinth Key', 'effect' => ItemEffectType::LABYRINTH->value]);
        $this->createQuest([
            'name' => 'The Lost Survey',
            'npc_id' => $this->createNpc()->id,
            'item_id' => $item->id,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($data['required_quest_item']['quest']);
    }

    public function test_show_does_not_return_a_quest_that_only_secondarily_requires_the_item(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Labyrinth']);
        $item = $this->createItem(['name' => 'Labyrinth Key', 'effect' => ItemEffectType::LABYRINTH->value]);
        $this->createQuest([
            'name' => 'The Lost Survey',
            'npc_id' => $this->createNpc()->id,
            'secondary_required_item' => $item->id,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($data['required_quest_item']['quest']);
    }

    public function test_show_returns_404_for_missing_game_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/999999');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_editor_returns_coordinate_grid_shape(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/editor');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['x' => [0, 250, 500], 'y' => [0, 250, 500]], $data['coordinates']);
    }

    public function test_editor_returns_only_selected_map_locations(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $otherMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $this->createLocation(['game_map_id' => $gameMap->id, 'name' => 'Old Church']);
        $this->createLocation(['game_map_id' => $otherMap->id, 'name' => 'Elsewhere']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/editor');
        $data = json_decode($response->getContent(), true);

        $this->assertCount(1, $data['locations']);
        $this->assertSame('Old Church', $data['locations'][0]['name']);
    }

    public function test_editor_returns_only_selected_map_npcs(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $otherMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $this->createNpc(['game_map_id' => $gameMap->id, 'real_name' => 'Merchant', 'type' => NpcType::QUEST_GIVER->value]);
        $this->createNpc(['game_map_id' => $otherMap->id, 'real_name' => 'Elsewhere Npc']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/editor');
        $data = json_decode($response->getContent(), true);

        $this->assertCount(1, $data['npcs']);
        $this->assertSame('Merchant', $data['npcs'][0]['real_name']);
        $this->assertSame(NpcType::QUEST_GIVER->value, $data['npcs'][0]['type']);
        $this->assertArrayNotHasKey('type_name', $data['npcs'][0]);
    }

    public function test_editor_returns_both_player_and_npc_owned_kingdoms_for_selected_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $this->createKingdom(['game_map_id' => $gameMap->id, 'npc_owned' => false, 'name' => 'Player Hold']);
        $this->createKingdom(['game_map_id' => $gameMap->id, 'npc_owned' => true, 'name' => 'Npc Hold']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/editor');
        $data = json_decode($response->getContent(), true);
        $kingdomsByName = collect($data['kingdoms'])->keyBy('name');

        $this->assertCount(2, $data['kingdoms']);
        $this->assertSame('player', $kingdomsByName['Player Hold']['owner_type']);
        $this->assertSame('npc', $kingdomsByName['Npc Hold']['owner_type']);
    }

    public function test_editor_excludes_kingdoms_from_other_maps(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $otherMap = $this->createGameMap();
        $this->mock(CoordinatesQuery::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->andReturn(new Coordinates([0, 250, 500], [0, 250, 500]));
        });

        $this->createKingdom(['game_map_id' => $otherMap->id, 'npc_owned' => false, 'name' => 'Other Hold']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/editor');
        $data = json_decode($response->getContent(), true);

        $this->assertCount(0, $data['kingdoms']);
    }

    public function test_editor_returns_404_for_missing_game_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/999999/editor');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_options_unauthenticated_request_receives_json_401(): void
    {
        $response = $this->call('GET', '/api/admin/game-maps/options', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_options_non_admin_request_receives_json_403(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/game-maps/options', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_options_returns_event_types_and_ordered_locations(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->createLocation(['game_map_id' => $gameMap->id, 'name' => 'Zeta Post']);
        $this->createLocation(['game_map_id' => $gameMap->id, 'name' => 'Alpha Post']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/options');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(array_keys(EventType::getOptionsForSelect()), $data['event_types']);
        $this->assertSame('Alpha Post', $data['locations'][0]['name']);
        $this->assertSame('Zeta Post', $data['locations'][1]['name']);
    }

    public function test_edit_returns_the_exact_form_shape(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap([
            'name' => 'Surface',
            'path' => 'surface.png',
            'kingdom_color' => '#ffffff',
            'default' => false,
            'can_traverse' => true,
            'xp_bonus' => 1.5,
            'skill_training_bonus' => 1.5,
            'drop_chance_bonus' => 1.5,
            'enemy_stat_bonus' => 1.5,
            'character_attack_reduction' => 1.5,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/edit');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($gameMap->id, $data['id']);
        $this->assertSame('Surface', $data['name']);
        $this->assertNull($data['description']);
        $this->assertSame('#ffffff', $data['kingdom_color']);
    }

    public function test_edit_returns_the_stored_markdown_description_unchanged(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['description' => '## The Frozen Reach']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/'.$gameMap->id.'/edit');

        $response->assertStatus(200);
        $this->assertSame('## The Frozen Reach', $response->json('description'));
    }

    public function test_edit_returns_404_for_missing_game_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/game-maps/999999/edit');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_store_requires_authentication(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $map = new UploadedFile(
            base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'),
            'map.png',
            'image/png',
            null,
            true
        );

        $response = $this->call('POST', '/api/admin/game-maps', [
            'name' => 'Surface',
            'kingdom_color' => '#ffffff',
            'default' => '0',
            'can_traverse' => '1',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], ['map' => $map], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_store_forbids_non_admin(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $user = $this->createUser();
        $map = new UploadedFile(
            base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'),
            'map.png',
            'image/png',
            null,
            true
        );

        $response = $this->actingAs($user)->call('POST', '/api/admin/game-maps', [
            'name' => 'Surface',
            'kingdom_color' => '#ffffff',
            'default' => '0',
            'can_traverse' => '1',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], ['map' => $map], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_store_validates_required_fields(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_store_requires_an_image(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [
            'name' => 'Surface',
            'kingdom_color' => '#ffffff',
            'default' => '0',
            'can_traverse' => '1',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_store_rejects_a_non_image_upload(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $map = new UploadedFile(base_path('composer.json'), 'map.txt', 'text/plain', null, true);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [
            'name' => 'Surface',
            'kingdom_color' => '#ffffff',
            'default' => '0',
            'can_traverse' => '1',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], ['map' => $map], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_store_rejects_a_kingdom_color_shorter_than_seven_characters(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $map = new UploadedFile(base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'), 'map.png', 'image/png', null, true);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [
            'name' => 'Surface', 'kingdom_color' => '#fffff', 'default' => '0', 'can_traverse' => '1',
            'only_during_event_type' => null, 'xp_bonus' => '1.5', 'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5', 'enemy_stat_bonus' => '1.5', 'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], ['map' => $map], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['kingdom_color']);
    }

    public function test_store_rejects_a_non_hexadecimal_kingdom_color(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $map = new UploadedFile(base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'), 'map.png', 'image/png', null, true);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [
            'name' => 'Surface', 'kingdom_color' => 'invalid', 'default' => '0', 'can_traverse' => '1',
            'only_during_event_type' => null, 'xp_bonus' => '1.5', 'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5', 'enemy_stat_bonus' => '1.5', 'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], ['map' => $map], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['kingdom_color']);
    }

    public function test_store_rejects_an_arbitrary_event_type(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $map = new UploadedFile(base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'), 'map.png', 'image/png', null, true);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [
            'name' => 'Surface', 'kingdom_color' => '#ffffff', 'default' => '0', 'can_traverse' => '1',
            'only_during_event_type' => 'weekly', 'xp_bonus' => '1.5', 'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5', 'enemy_stat_bonus' => '1.5', 'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], ['map' => $map], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['only_during_event_type']);
    }

    public function test_store_rejects_an_out_of_range_event_type(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $map = new UploadedFile(base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'), 'map.png', 'image/png', null, true);
        $invalidEventType = max(array_keys(EventType::getOptionsForSelect())) + 1;

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [
            'name' => 'Surface', 'kingdom_color' => '#ffffff', 'default' => '0', 'can_traverse' => '1',
            'only_during_event_type' => $invalidEventType, 'xp_bonus' => '1.5', 'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5', 'enemy_stat_bonus' => '1.5', 'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], ['map' => $map], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['only_during_event_type']);
    }

    public function test_store_accepts_an_omitted_event_type(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldReceive('tile')->once()->with(
            Mockery::on(fn (GameMap $createdGameMap): bool => $createdGameMap->is(GameMap::find($createdGameMap->id)))
        );
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $map = new UploadedFile(base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'), 'map.png', 'image/png', null, true);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->with('Surface', Mockery::type(UploadedFile::class))->andReturn('Surface/map.png');
        $disk->shouldReceive('url')->with('Surface/map.png')->andReturn('https://example.test/maps/Surface/map.png');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [
            'name' => 'Surface', 'kingdom_color' => '#ffffff', 'default' => '0', 'can_traverse' => '1',
            'xp_bonus' => '1.5', 'skill_training_bonus' => '1.5', 'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5', 'character_attack_reduction' => '1.5', 'required_location_id' => null,
        ], [], ['map' => $map]);
        $data = json_decode($response->getContent(), true);

        $response->assertStatus(201);
        $this->assertNull($data['description']);
        $this->assertNull($data['only_during_event_type']);
        $this->assertDatabaseHas('game_maps', [
            'id' => $data['id'],
            'description' => null,
            'only_during_event_type' => null,
        ]);
    }

    public function test_store_rejects_a_non_string_description(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $map = new UploadedFile(
            base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'),
            'map.png',
            'image/png',
            null,
            true
        );

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [
            'name' => 'Surface', 'description' => ['invalid'], 'kingdom_color' => '#ffffff',
            'default' => '0', 'can_traverse' => '1', 'only_during_event_type' => null, 'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5', 'drop_chance_bonus' => '1.5', 'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5', 'required_location_id' => null,
        ], [], ['map' => $map], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['description']);
    }

    public function test_store_requires_default(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $map = new UploadedFile(
            base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'),
            'map.png',
            'image/png',
            null,
            true
        );

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [
            'name' => 'Surface',
            'kingdom_color' => '#ffffff',
            'can_traverse' => '1',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], ['map' => $map], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['default']);
    }

    public function test_store_requires_can_traverse(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $map = new UploadedFile(
            base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'),
            'map.png',
            'image/png',
            null,
            true
        );

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [
            'name' => 'Surface',
            'kingdom_color' => '#ffffff',
            'default' => '0',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], ['map' => $map], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['can_traverse']);
    }

    public function test_store_accepts_default_false(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldReceive('tile')->once()->with(
            Mockery::on(fn (GameMap $createdGameMap): bool => $createdGameMap->is(GameMap::find($createdGameMap->id)))
        );
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $map = new UploadedFile(
            base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'),
            'map.png',
            'image/png',
            null,
            true
        );

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->with('Twisted Memories', Mockery::type(UploadedFile::class))->andReturn('Twisted Memories/map.png');
        $disk->shouldReceive('url')->with('Twisted Memories/map.png')->andReturn('https://example.test/maps/Twisted%20Memories/map.png');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [
            'name' => 'Twisted Memories',
            'kingdom_color' => '#ffffff',
            'default' => '0',
            'can_traverse' => '1',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], ['map' => $map]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertFalse($data['default']);
    }

    public function test_store_accepts_can_traverse_false(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldReceive('tile')->once()->with(
            Mockery::on(fn (GameMap $createdGameMap): bool => $createdGameMap->is(GameMap::find($createdGameMap->id)))
        );
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $map = new UploadedFile(
            base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'),
            'map.png',
            'image/png',
            null,
            true
        );

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->with('Twisted Memories', Mockery::type(UploadedFile::class))->andReturn('Twisted Memories/map.png');
        $disk->shouldReceive('url')->with('Twisted Memories/map.png')->andReturn('https://example.test/maps/Twisted%20Memories/map.png');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [
            'name' => 'Twisted Memories',
            'kingdom_color' => '#ffffff',
            'default' => '0',
            'can_traverse' => '0',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], ['map' => $map]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertFalse($data['can_traverse']);
    }

    public function test_store_creates_a_game_map(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldReceive('tile')->once()->with(
            Mockery::on(fn (GameMap $createdGameMap): bool => $createdGameMap->name === 'Twisted Memories'
                && $createdGameMap->exists)
        );
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $map = new UploadedFile(
            base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'),
            'map.png',
            'image/png',
            null,
            true
        );

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->with('Twisted Memories', Mockery::type(UploadedFile::class))->andReturn('Twisted Memories/map.png');
        $disk->shouldReceive('url')->with('Twisted Memories/map.png')->andReturn('https://example.test/maps/Twisted%20Memories/map.png');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [
            'name' => 'Twisted Memories',
            'description' => 'A newly charted plane.',
            'kingdom_color' => '#ffffff',
            'default' => '0',
            'can_traverse' => '1',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], ['map' => $map]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('game_maps', [
            'name' => 'Twisted Memories',
            'description' => 'A newly charted plane.',
        ]);
        $gameMap = GameMap::where('name', 'Twisted Memories')->firstOrFail();
        $this->assertNull($gameMap->tile_map);
    }

    public function test_store_writes_the_image_to_the_maps_disk(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldReceive('tile')->once()->with(
            Mockery::on(fn (GameMap $createdGameMap): bool => $createdGameMap->is(GameMap::find($createdGameMap->id)))
        );
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $map = new UploadedFile(
            base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'),
            'map.png',
            'image/png',
            null,
            true
        );

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->with('Twisted Memories', Mockery::type(UploadedFile::class))->andReturn('Twisted Memories/map.png');
        $disk->shouldReceive('url')->with('Twisted Memories/map.png')->andReturn('https://example.test/maps/Twisted%20Memories/map.png');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [
            'name' => 'Twisted Memories',
            'kingdom_color' => '#ffffff',
            'default' => '0',
            'can_traverse' => '1',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], ['map' => $map]);
        $gameMap = GameMap::find(json_decode($response->getContent(), true)['id']);

        $this->assertSame('Twisted Memories/map.png', $gameMap->path);
    }

    public function test_store_returns_the_exact_save_response(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldReceive('tile')->once()->with(
            Mockery::on(fn (GameMap $createdGameMap): bool => $createdGameMap->is(GameMap::find($createdGameMap->id)))
        );
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $existingGameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $existingGameMap->id]);
        $map = new UploadedFile(
            base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'),
            'map.png',
            'image/png',
            null,
            true
        );

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->with('Twisted Memories', Mockery::type(UploadedFile::class))->andReturn('Twisted Memories/map.png');
        $disk->shouldReceive('url')->with('Twisted Memories/map.png')->andReturn('https://example.test/maps/Twisted%20Memories/map.png');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps', [
            'name' => 'Twisted Memories',
            'kingdom_color' => '#ffffff',
            'default' => '0',
            'can_traverse' => '1',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => $location->id,
        ], [], ['map' => $map]);
        $data = json_decode($response->getContent(), true);
        $gameMap = GameMap::find($data['id']);

        $this->assertSame([
            'id' => $gameMap->id,
            'name' => 'Twisted Memories',
            'description' => null,
            'map_url' => 'https://example.test/maps/Twisted%20Memories/map.png',
            'kingdom_color' => '#ffffff',
            'default' => false,
            'can_traverse' => true,
            'only_during_event_type' => null,
            'xp_bonus' => 1.5,
            'skill_training_bonus' => 1.5,
            'drop_chance_bonus' => 1.5,
            'enemy_stat_bonus' => 1.5,
            'character_attack_reduction' => 1.5,
            'required_location_id' => $location->id,
        ], $data);
    }

    public function test_update_requires_authentication_without_invoking_tile_generation(): void
    {
        $gameMap = $this->createGameMap();
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $response = $this->call('POST', '/api/admin/game-maps/'.$gameMap->id, [
            '_method' => 'PUT',
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_update_forbids_non_admin_without_invoking_tile_generation(): void
    {
        $user = $this->createUser();
        $gameMap = $this->createGameMap();
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $response = $this->actingAs($user)->call('POST', '/api/admin/game-maps/'.$gameMap->id, [
            '_method' => 'PUT',
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_update_validates_fields(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/game-maps/'.$gameMap->id,
            ['_method' => 'PUT'],
            [], [], ['HTTP_ACCEPT' => 'application/json']
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_update_rejects_a_kingdom_color_shorter_than_seven_characters(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps/'.$gameMap->id, [
            '_method' => 'PUT',
            'name' => 'Renamed',
            'kingdom_color' => '#fffff',
            'default' => '0',
            'can_traverse' => '1',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['kingdom_color']);
    }

    public function test_update_rejects_a_non_hexadecimal_kingdom_color(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps/'.$gameMap->id, [
            '_method' => 'PUT',
            'name' => 'Renamed',
            'kingdom_color' => 'invalid',
            'default' => '0',
            'can_traverse' => '1',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['kingdom_color']);
    }

    public function test_update_rejects_an_arbitrary_event_type(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps/'.$gameMap->id, [
            '_method' => 'PUT',
            'name' => 'Renamed',
            'kingdom_color' => '#ffffff',
            'default' => '0',
            'can_traverse' => '1',
            'only_during_event_type' => 'weekly',
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['only_during_event_type']);
    }

    public function test_update_rejects_an_out_of_range_event_type(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $invalidEventType = max(array_keys(EventType::getOptionsForSelect())) + 1;

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps/'.$gameMap->id, [
            '_method' => 'PUT', 'name' => 'Renamed', 'kingdom_color' => '#ffffff', 'default' => '0',
            'can_traverse' => '1', 'only_during_event_type' => $invalidEventType, 'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5', 'drop_chance_bonus' => '1.5', 'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5', 'required_location_id' => null,
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['only_during_event_type']);
    }

    public function test_update_accepts_an_omitted_event_type(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['path' => 'original.png', 'only_during_event_type' => EventType::RAID_EVENT]);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('url')->with('original.png')->andReturn('https://example.test/maps/original.png');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps/'.$gameMap->id, [
            '_method' => 'PUT', 'name' => 'Renamed', 'kingdom_color' => '#ffffff', 'default' => '0',
            'can_traverse' => '1', 'xp_bonus' => '1.5', 'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5', 'enemy_stat_bonus' => '1.5', 'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ]);
        $data = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertNull($data['description']);
        $this->assertNull($data['only_during_event_type']);
        $this->assertDatabaseHas('game_maps', ['id' => $gameMap->id, 'only_during_event_type' => null]);
    }

    public function test_update_accepts_a_null_description(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['description' => 'Existing description', 'path' => 'original.png']);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('url')->with('original.png')->andReturn('https://example.test/maps/original.png');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps/'.$gameMap->id, [
            '_method' => 'PUT', 'name' => 'Renamed', 'description' => null, 'kingdom_color' => '#ffffff',
            'default' => '0', 'can_traverse' => '1', 'only_during_event_type' => null, 'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5', 'drop_chance_bonus' => '1.5', 'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5', 'required_location_id' => null,
        ]);

        $response->assertStatus(200);
        $this->assertNull($gameMap->refresh()->description);
    }

    public function test_update_rejects_a_non_string_description(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps/'.$gameMap->id, [
            '_method' => 'PUT', 'name' => 'Renamed', 'description' => ['invalid'],
            'kingdom_color' => '#ffffff', 'default' => '0', 'can_traverse' => '1',
            'only_during_event_type' => null, 'xp_bonus' => '1.5', 'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5', 'enemy_stat_bonus' => '1.5', 'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['description']);
    }

    public function test_update_requires_default(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps/'.$gameMap->id, [
            '_method' => 'PUT',
            'name' => 'Renamed',
            'kingdom_color' => '#ffffff',
            'can_traverse' => '1',
            'only_during_event_type' => EventType::RAID_EVENT,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['default']);
    }

    public function test_update_requires_can_traverse(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps/'.$gameMap->id, [
            '_method' => 'PUT',
            'name' => 'Renamed',
            'kingdom_color' => '#ffffff',
            'default' => '0',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['can_traverse']);
    }

    public function test_update_succeeds_without_a_replacement_image(): void
    {
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Original', 'path' => 'original.png']);

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('url')->with('original.png')->andReturn('https://example.test/maps/original.png');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps/'.$gameMap->id, [
            '_method' => 'PUT',
            'name' => 'Renamed',
            'description' => 'Updated **map** lore.',
            'kingdom_color' => '#ffffff',
            'default' => '0',
            'can_traverse' => '1',
            'only_during_event_type' => EventType::RAID_EVENT,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Renamed', $data['name']);
        $this->assertSame('Updated **map** lore.', $gameMap->refresh()->description);
        $this->assertSame(EventType::RAID_EVENT, $data['only_during_event_type']);
    }

    public function test_update_preserves_the_path_without_a_replacement_image(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap([
            'name' => 'Original',
            'path' => 'original.png',
            'tile_map' => [['original-tile.png']],
        ]);

        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldNotReceive('prepareReplacement');
        $mapTileGenerationService->shouldNotReceive('commitReplacement');
        $mapTileGenerationService->shouldNotReceive('finalizeReplacement');
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('url')->with('original.png')->andReturn('https://example.test/maps/original.png');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps/'.$gameMap->id, [
            '_method' => 'PUT',
            'name' => 'Renamed',
            'kingdom_color' => '#ffffff',
            'default' => '0',
            'can_traverse' => '1',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ]);

        $gameMap->refresh();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('original.png', $gameMap->path);
        $this->assertSame([['original-tile.png']], $gameMap->tile_map);
    }

    public function test_update_succeeds_with_a_replacement_image(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap([
            'name' => 'Original',
            'path' => 'original.png',
            'tile_map' => [['original-tile.png']],
        ]);
        $map = new UploadedFile(
            base_path('resources/backup/info-sections-images/managing-buildings-with-capital-cities/WVz8sLXtqNCupOxuVKKLG5bz4HFuEyBAYcuVzMy0.png'),
            'new-map.png',
            'image/png',
            null,
            true
        );

        $replacement = new PreparedMapTileReplacement(
            tileMap: [['fresh-tile.png']],
            previousFolderName: 'original-pieces',
            currentFolderName: 'renamed-pieces',
            replacementFolderName: 'renamed-pieces-replacement',
            backupFolderName: 'renamed-pieces-backup',
            hadCurrentDirectory: true,
            sameCommittedDirectory: false,
        );
        $mapTileGenerationService = Mockery::mock(MapTileGenerationService::class);
        $mapTileGenerationService->shouldNotReceive('tile');
        $mapTileGenerationService->shouldReceive('prepareReplacement')->once()->with(
            Mockery::on(fn (GameMap $updatedGameMap): bool => $updatedGameMap->is($gameMap)
                && $updatedGameMap->name === 'Renamed'
                && $updatedGameMap->path === 'Renamed/new-map.png'
                && is_null($updatedGameMap->tile_map)),
            'Original'
        )->andReturn($replacement);
        $mapTileGenerationService->shouldReceive('commitReplacement')->once()->with($replacement);
        $mapTileGenerationService->shouldReceive('finalizeReplacement')->once()->with($replacement);
        $mapTileGenerationService->shouldNotReceive('rollbackReplacement');
        $this->instance(MapTileGenerationService::class, $mapTileGenerationService);

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->with('Renamed', Mockery::type(UploadedFile::class))->andReturn('Renamed/new-map.png');
        $disk->shouldReceive('delete')->once()->with('original.png')->andReturn(true);
        $disk->shouldReceive('url')->with('Renamed/new-map.png')->andReturn('https://example.test/maps/Renamed/new-map.png');
        Storage::shouldReceive('disk')->with('maps')->andReturn($disk);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/game-maps/'.$gameMap->id, [
            '_method' => 'PUT',
            'name' => 'Renamed',
            'kingdom_color' => '#ffffff',
            'default' => '0',
            'can_traverse' => '1',
            'only_during_event_type' => null,
            'xp_bonus' => '1.5',
            'skill_training_bonus' => '1.5',
            'drop_chance_bonus' => '1.5',
            'enemy_stat_bonus' => '1.5',
            'character_attack_reduction' => '1.5',
            'required_location_id' => null,
        ], [], ['map' => $map]);

        $this->assertSame(200, $response->getStatusCode());
        $gameMap->refresh();
        $this->assertSame('Renamed/new-map.png', $gameMap->path);
        $this->assertSame([['fresh-tile.png']], $gameMap->tile_map);
    }
}
