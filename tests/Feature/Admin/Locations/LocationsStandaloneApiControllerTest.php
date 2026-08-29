<?php

namespace Tests\Feature\Admin\Locations;

use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class LocationsStandaloneApiControllerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateLocation, CreateRole, CreateUser, RefreshDatabase;

    public function test_index_rejects_unauthenticated_request(): void
    {
        $response = $this->call('GET', '/api/admin/locations', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_index_rejects_non_admin_request(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/locations', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_index_returns_paginated_locations(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $this->createLocation(['game_map_id' => $gameMap->id, 'name' => 'Old Church', 'x' => 10, 'y' => 10]);
        $this->createLocation(['game_map_id' => $gameMap->id, 'name' => 'Broken Anvil', 'x' => 20, 'y' => 20]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/locations');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, $data['data']);
        $this->assertSame(['id', 'name', 'map_name', 'type', 'x', 'y'], array_keys($data['data'][0]));
    }

    public function test_index_search_filters_by_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $this->createLocation(['game_map_id' => $gameMap->id, 'name' => 'Old Church']);
        $this->createLocation(['game_map_id' => $gameMap->id, 'name' => 'Broken Anvil']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/locations?search_text=Church');
        $data = json_decode($response->getContent(), true);

        $this->assertCount(1, $data['data']);
        $this->assertSame('Old Church', $data['data'][0]['name']);
    }

    public function test_index_rejects_invalid_sort_key(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/locations?sort_key=description',
            [], [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_show_location_returns_exact_detail_shape(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $rewardItem = $this->createItem(['name' => 'Relic', 'type' => 'quest']);
        $requiredItem = $this->createItem(['name' => 'Key', 'type' => 'quest']);
        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'name' => 'Old Church',
            'description' => 'A crumbling church.',
            'x' => 100,
            'y' => 100,
            'type' => LocationType::GOLD_MINES->value,
            'quest_reward_item_id' => $rewardItem->id,
            'required_quest_item_id' => $requiredItem->id,
            'hours_to_drop' => 4,
            'minutes_between_delve_fights' => null,
        ]);
        $this->createItem(['name' => 'Dropped Quest Item', 'type' => 'quest', 'drop_location_id' => $location->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/locations/'.$location->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($location->id, $data['id']);
        $this->assertSame(['id' => $gameMap->id, 'name' => 'Surface'], $data['game_map']);
        $this->assertSame('Old Church', $data['name']);
        $this->assertSame('A crumbling church.', $data['description']);
        $this->assertSame(LocationType::GOLD_MINES->value, $data['type']);
        $this->assertSame(['id' => $requiredItem->id, 'name' => 'Key'], $data['required_quest_item']);
        $this->assertSame(['id' => $rewardItem->id, 'name' => 'Relic'], $data['quest_reward_item']);
        $this->assertSame(4, $data['hours_to_drop']);
        $this->assertSame(1, $data['quest_item_drop_count']);
        $this->assertFalse($data['is_cave_of_memories']);
        $this->assertTrue($data['manual_fighting_only']);
    }

    public function test_show_location_marks_cave_of_memories_as_not_manual_fighting_only(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'type' => LocationType::CAVE_OF_MEMORIES->value,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/locations/'.$location->id);
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['is_cave_of_memories']);
        $this->assertFalse($data['manual_fighting_only']);
    }

    public function test_show_location_with_no_type_is_not_manual_fighting_only(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => null]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/locations/'.$location->id);
        $data = json_decode($response->getContent(), true);

        $this->assertNull($data['type']);
        $this->assertFalse($data['manual_fighting_only']);
        $this->assertFalse($data['is_cave_of_memories']);
    }

    public function test_quest_items_endpoint_only_includes_quest_type_drops_for_this_location(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => LocationType::GOLD_MINES->value]);
        $otherLocation = $this->createLocation(['game_map_id' => $gameMap->id]);

        $this->createItem(['name' => 'Dropped Quest Item', 'type' => 'quest', 'drop_location_id' => $location->id]);
        $this->createItem(['name' => 'Non Quest Drop', 'type' => 'weapon', 'drop_location_id' => $location->id]);
        $this->createItem(['name' => 'Other Location Item', 'type' => 'quest', 'drop_location_id' => $otherLocation->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/locations/'.$location->id.'/quest-items');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains('Dropped Quest Item', $names);
        $this->assertNotContains('Non Quest Drop', $names);
        $this->assertNotContains('Other Location Item', $names);
        $this->assertTrue($data['meta']['location_drop_mode']['manual_fighting_only']);
        $this->assertFalse($data['meta']['location_drop_mode']['is_cave_of_memories']);
    }

    public function test_quest_items_endpoint_paginates_results(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $this->createDistinctlyNamedItems(3, 'Quest Drop', [
            'type' => 'quest',
            'drop_location_id' => $location->id,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/locations/'.$location->id.'/quest-items?per_page=2&page=1');
        $data = json_decode($response->getContent(), true);

        $this->assertCount(2, $data['data']);
        $this->assertSame(3, $data['meta']['pagination']['total']);
        $this->assertSame(2, $data['meta']['pagination']['total_pages']);
    }
}
