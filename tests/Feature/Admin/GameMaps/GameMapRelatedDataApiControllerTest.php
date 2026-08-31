<?php

namespace Tests\Feature\Admin\GameMaps;

use App\Flare\Models\Monster;
use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class GameMapRelatedDataApiControllerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateLocation, CreateMonster, CreateNpc, CreateQuest, CreateRole, CreateUser, RefreshDatabase;

    public function test_related_locations_returns_the_paginated_response_shape(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->createLocation(['game_map_id' => $gameMap->id, 'name' => 'Old Church']);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-locations");
        $data = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('pagination', $data['meta']);
        $this->assertArrayHasKey('can_load_more', $data['meta']);
    }

    public function test_related_locations_only_returns_locations_on_the_given_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $otherMap = $this->createGameMap();
        $this->createLocation(['game_map_id' => $gameMap->id, 'name' => 'Old Church']);
        $this->createLocation(['game_map_id' => $otherMap->id, 'name' => 'Elsewhere']);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-locations");
        $data = json_decode($response->getContent(), true)['data'];

        $this->assertCount(1, $data);
        $this->assertSame('Old Church', $data[0]['name']);
    }

    public function test_related_locations_filters_by_search_text(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->createLocation(['game_map_id' => $gameMap->id, 'name' => 'Old Church']);
        $this->createLocation(['game_map_id' => $gameMap->id, 'name' => 'Twisted Gate']);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-locations", [
            'search_text' => 'Church',
        ]);
        $data = json_decode($response->getContent(), true)['data'];

        $this->assertCount(1, $data);
        $this->assertSame('Old Church', $data[0]['name']);
    }

    public function test_related_locations_are_ordered_by_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->createLocation(['game_map_id' => $gameMap->id, 'name' => 'Zeta Post']);
        $this->createLocation(['game_map_id' => $gameMap->id, 'name' => 'Alpha Post']);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-locations");
        $data = json_decode($response->getContent(), true)['data'];

        $this->assertSame('Alpha Post', $data[0]['name']);
        $this->assertSame('Zeta Post', $data[1]['name']);
    }

    public function test_related_npcs_only_returns_npcs_on_the_given_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $otherMap = $this->createGameMap();
        $this->createNpc(['game_map_id' => $gameMap->id, 'real_name' => 'Merchant']);
        $this->createNpc(['game_map_id' => $otherMap->id, 'real_name' => 'Elsewhere Npc']);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-npcs");
        $data = json_decode($response->getContent(), true)['data'];

        $this->assertCount(1, $data);
        $this->assertSame('Merchant', $data[0]['name']);
    }

    public function test_related_npcs_filters_by_search_text(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->createNpc(['game_map_id' => $gameMap->id, 'real_name' => 'Merchant']);
        $this->createNpc(['game_map_id' => $gameMap->id, 'real_name' => 'Guard']);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-npcs", [
            'search_text' => 'Merch',
        ]);
        $data = json_decode($response->getContent(), true)['data'];

        $this->assertCount(1, $data);
        $this->assertSame('Merchant', $data[0]['name']);
    }

    public function test_related_quests_uses_the_quest_givers_map_not_access_or_faction_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $otherMap = $this->createGameMap();
        $npcOnMap = $this->createNpc(['game_map_id' => $gameMap->id]);
        $npcElsewhere = $this->createNpc(['game_map_id' => $otherMap->id]);

        $matchingQuest = $this->createQuest(['name' => 'Given Here', 'npc_id' => $npcOnMap->id]);
        $this->createQuest([
            'name' => 'Given Elsewhere',
            'npc_id' => $npcElsewhere->id,
            'access_to_map_id' => $gameMap->id,
            'faction_game_map_id' => $gameMap->id,
        ]);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-quests");
        $data = json_decode($response->getContent(), true)['data'];

        $this->assertCount(1, $data);
        $this->assertSame($matchingQuest->id, $data[0]['id']);
        $this->assertSame('Given Here', $data[0]['name']);
    }

    public function test_related_quests_are_ordered_by_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $npc = $this->createNpc(['game_map_id' => $gameMap->id]);
        $this->createQuest(['name' => 'Zeta Quest', 'npc_id' => $npc->id]);
        $this->createQuest(['name' => 'Alpha Quest', 'npc_id' => $npc->id]);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-quests");
        $data = json_decode($response->getContent(), true)['data'];

        $this->assertSame('Alpha Quest', $data[0]['name']);
        $this->assertSame('Zeta Quest', $data[1]['name']);
    }

    public function test_related_monsters_includes_directly_assigned_monsters(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $otherMap = $this->createGameMap();
        $matchingMonster = $this->createMonster(['name' => 'Map Monster', 'game_map_id' => $gameMap->id]);
        $this->createMonster(['name' => 'Elsewhere Monster', 'game_map_id' => $otherMap->id]);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-monsters");
        $data = json_decode($response->getContent(), true)['data'];

        $this->assertCount(1, $data);
        $this->assertSame($matchingMonster->id, $data[0]['id']);
    }

    public function test_related_monsters_includes_special_location_monsters_matching_a_present_location_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->createLocation(['game_map_id' => $gameMap->id, 'type' => LocationType::CAVE_OF_MEMORIES->value]);
        $specialMonster = Monster::factory()->create([
            'name' => 'Cave Monster',
            'game_map_id' => null,
            'only_for_location_type' => LocationType::CAVE_OF_MEMORIES->value,
        ]);
        Monster::factory()->create([
            'name' => 'Unrelated Special Monster',
            'game_map_id' => null,
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH->value,
        ]);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-monsters");
        $data = json_decode($response->getContent(), true)['data'];

        $this->assertCount(1, $data);
        $this->assertSame($specialMonster->id, $data[0]['id']);
    }

    public function test_related_monsters_does_not_duplicate_a_monster_matching_both_conditions(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->createLocation(['game_map_id' => $gameMap->id, 'type' => LocationType::CAVE_OF_MEMORIES->value]);
        $this->createMonster([
            'name' => 'Both Match Monster',
            'game_map_id' => $gameMap->id,
            'only_for_location_type' => LocationType::CAVE_OF_MEMORIES->value,
        ]);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-monsters");
        $data = json_decode($response->getContent(), true)['data'];

        $this->assertCount(1, $data);
    }

    public function test_related_quest_items_deduplicates_the_shared_item_across_all_sources(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $sharedItem = $this->createItem(['name' => 'Shared Quest Item', 'type' => 'quest']);
        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'required_quest_item_id' => $sharedItem->id,
            'quest_reward_item_id' => $sharedItem->id,
        ]);
        $droppedItem = $this->createItem(['name' => 'Dropped Item', 'type' => 'quest', 'drop_location_id' => $location->id]);
        $npc = $this->createNpc(['game_map_id' => $gameMap->id]);
        $this->createQuest([
            'name' => 'Item Quest',
            'npc_id' => $npc->id,
            'item_id' => $sharedItem->id,
            'secondary_required_item' => $sharedItem->id,
            'reward_item' => $sharedItem->id,
        ]);
        $this->createMonster([
            'name' => 'Item Monster',
            'game_map_id' => $gameMap->id,
            'quest_item_id' => $sharedItem->id,
        ]);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-quest-items");
        $data = json_decode($response->getContent(), true)['data'];
        $itemIds = collect($data)->pluck('item_id')->all();

        // The shared Item is connected through six separate sources but must appear only once.
        $this->assertCount(2, $itemIds);
        $this->assertSame(1, count(array_keys($itemIds, $sharedItem->id)));
        $this->assertContains($sharedItem->id, $itemIds);
        $this->assertContains($droppedItem->id, $itemIds);
    }

    public function test_related_quest_items_includes_the_map_required_item(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Labyrinth']);
        $requiredItem = $this->createItem(['name' => 'Map Key', 'type' => 'quest', 'effect' => 'labyrinth']);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-quest-items");
        $data = json_decode($response->getContent(), true)['data'];
        $itemIds = collect($data)->pluck('item_id')->all();

        $this->assertContains($requiredItem->id, $itemIds);
    }

    public function test_related_quest_items_excludes_items_unconnected_to_the_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->createItem(['name' => 'Unrelated Quest Item', 'type' => 'quest']);

        $response = $this->actingAs($admin)->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-quest-items");
        $data = json_decode($response->getContent(), true)['data'];

        $this->assertCount(0, $data);
    }

    public function test_related_endpoints_reject_unauthenticated_requests(): void
    {
        $gameMap = $this->createGameMap();

        $response = $this->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-locations", [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_related_endpoints_reject_non_admin_requests(): void
    {
        $user = $this->createUser();
        $gameMap = $this->createGameMap();

        $response = $this->actingAs($user)->call('GET', "/api/admin/game-maps/{$gameMap->id}/related-locations", [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }
}
