<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Location;
use App\Flare\Models\Quest;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class LocationsControllerTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateRole, CreateGameMap, CreateItem, CreateNpc, CreateQuest;

    private $gameMap;

    public function setUp(): void
    {
        parent::setUp();

        // Create and act as admin
        $role  = $this->createAdminRole();
        $admin = $this->createAdmin($role);
        $this->actingAs($admin);

        // Prepare GameMap for locations
        $this->gameMap = $this->createGameMap();
    }

    public function testIndexDisplaysAllLocations()
    {
        Location::factory()->count(3)->create(['game_map_id' => $this->gameMap->id]);

        $response = $this->call('GET', route('locations.list'));
        $this->assertEquals(200, $response->getStatusCode());

        $view = $response->original;
        $this->assertEquals('admin.locations.locations', $view->getName());
        $data = $view->getData();
        $this->assertCount(3, $data['locations']);
    }

    public function testCreateDisplaysManageView()
    {
        $response = $this->call('GET', route('locations.create'));
        $this->assertEquals(200, $response->getStatusCode());

        $view = $response->original;
        $this->assertEquals('admin.locations.manage', $view->getName());
        $data = $view->getData();
        $this->assertArrayHasKey('gameMaps', $data);
    }

    public function testEditDisplaysManageViewWithLocation()
    {
        $location = Location::factory()->create(['game_map_id' => $this->gameMap->id]);

        $response = $this->call('GET', route('location.edit', ['location' => $location->id]));
        $this->assertEquals(200, $response->getStatusCode());

        $view = $response->original;
        $this->assertEquals('admin.locations.manage', $view->getName());
        $data = $view->getData();
        $this->assertArrayHasKey('gameMaps', $data);
        $this->assertEquals($location->id, $data['location']->id);
    }

    public function testStoreCreatesNewLocationAndRedirects()
    {
        $data = [
            'name'                 => 'TestLocation',
            'game_map_id'          => $this->gameMap->id,
            'can_players_enter'    => true,
            'can_auto_battle'      => false,
            'quest_reward_item_id' => null,
            'description'          => 'A test location',
            'is_port'              => false,
            'x'                    => 5,
            'y'                    => 10,
        ];

        $response = $this->call('POST', route('locations.store'), $data);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(url(route('locations.list')), $response->headers->get('Location'));

        // Confirm record exists
        $this->assertTrue(
            Location::where('name', 'TestLocation')
                ->where('description', 'A test location')
                ->exists()
        );
    }

    public function testStoreUpdatesExistingLocation()
    {
        $location = Location::factory()->create([
            'name'        => 'OldName',
            'game_map_id' => $this->gameMap->id,
        ]);

        $response = $this->call('POST', route('locations.store'), [
            'id'   => $location->id,
            'name' => 'NewName',
        ]);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(url(route('locations.list')), $response->headers->get('Location'));

        // Confirm update applied
        $this->assertTrue(
            Location::where('id', $location->id)
                ->where('name', 'NewName')
                ->exists()
        );
    }

    public function testShowDefaultViewData()
    {
        $location = Location::factory()->create([
            'game_map_id'          => $this->gameMap->id,
            'enemy_strength_type'  => null,
            'type'                 => null,
            'quest_reward_item_id' => null,
        ]);

        $response = $this->call('GET', route('locations.location', ['location' => $location->id]));
        $this->assertEquals(200, $response->getStatusCode());

        $view = $response->original;
        $this->assertEquals('information.locations.location', $view->getName());
        $data = $view->getData();
        $this->assertEquals($location->id, $data['location']->id);
        $this->assertNull($data['increasesEnemyStrengthBy']);
        $this->assertEquals(0.0, $data['increasesDropChanceBy']);
        $this->assertNull($data['locationType']);
        $this->assertNull($data['usedInQuest']);
    }

    public function testShowWithQuestRewardItemUsesPrimaryQuest()
    {
        $item     = $this->createItem();
        $location = Location::factory()->create([
            'game_map_id'          => $this->gameMap->id,
            'quest_reward_item_id' => $item->id,
        ]);

        $npc   = $this->createNpc(['game_map_id' => $this->gameMap->id]);
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'item_id'=> $item->id,
        ]);

        $response = $this->call('GET', route('locations.location', ['location' => $location->id]));
        $this->assertEquals(200, $response->getStatusCode());

        $view = $response->original;
        $data = $view->getData();
        $this->assertInstanceOf(Quest::class, $data['usedInQuest']);
        $this->assertEquals($quest->id, $data['usedInQuest']->id);
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
