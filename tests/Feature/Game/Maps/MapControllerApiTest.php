<?php

namespace Tests\Feature\Game\Maps;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Values\ItemEffectsValue;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Values\MapTileValue;
use Cache;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateAdventure;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateItem;

class MapControllerApiTest extends TestCase
{
    use RefreshDatabase,
        CreateLocation,
        CreateAdventure,
        CreateItem,
        CreateGameMap;

    private $character;

    public function setUp(): void {
        parent::setUp();

        Queue::fake();

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
        $this->monster   = null;
    }

    public function testGetMap() {

        $user = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('GET', '/api/map/' . $user->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals(16, $content->character_map->position_x);
        $this->assertEquals(16, $content->character_map->character_position_x);
    }

    public function testGetMapWithKingdomCache() {

        Cache::put('character-kingdoms-Sample-' . $this->character->getCharacter()->id, [['sample']]);

        $user = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('GET', '/api/map/' . $user->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals(1, count($content->my_kingdoms));
        $this->assertEquals(16, $content->character_map->position_x);
        $this->assertEquals(16, $content->character_map->character_position_x);
    }

    public function testGetMapWithPort() {
        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 16,
            'y'           => 16,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $user = $this->character->getUser();

        $response = $this->actingAs($user)
            ->json('GET', '/api/map/' . $user->id)
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());

        $this->assertNotNull($content->port_details);
    }

    public function testMoveCharacter() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->once()->andReturn(false);

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/move/' . $character->id, [
                             'character_position_x' => 48,
                             'character_position_y' => 48,
                             'position_x'           => 0,
                             'position_y'           => 0,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $character = $this->character->getCharacter();

        $this->assertEquals(0, $character->map->position_x);
        $this->assertEquals(48, $character->map->character_position_x);
        $this->assertEquals(48, $character->map->character_position_y);
    }

    public function testCannotMoveAnotherCharacter() {
        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $otherUser = (new CharacterFactory())->createBaseCharacter()->getUser();

        $response = $this->actingAs($otherUser)
            ->json('POST', '/api/move/' . $character->id, [
                'character_position_x' => 48,
                'character_position_y' => 48,
                'position_x'           => 0,
                'position_y'           => 0,
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('You don\'t have permission to do that.', $content->error);
    }

    public function testMoveCharacterToLocationWithAdventure() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->once()->andReturn(false);

        $location = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $adventure = $this->createNewAdventure();

        $location->adventures()->attach($adventure->id);

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/move/' . $character->id, [
                             'character_position_x' => $location->x,
                             'character_position_y' => $location->y,
                             'position_x'           => 0,
                             'position_y'           => 0,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $content = json_decode($response->content());

        $this->assertTrue(!empty($content->adventure_details));
        $this->assertEquals($adventure->name, $content->adventure_details[0]->name);
    }

    public function testMoveCharacterToLocationWithDrop() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->once()->andReturn(false);

        $location = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $item = $location->questRewardItem()->create([
            'name'           => 'Artifact',
            'type'           => 'artifact',
            'base_damage'    => 10,
            'cost'           => 10,
        ]);

        $location->update([
            'quest_reward_item_id' => $item->id,
        ]);

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/move/' . $character->id, [
                             'character_position_x' => $location->x,
                             'character_position_y' => $location->y,
                             'position_x'           => 0,
                             'position_y'           => 0,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $character = $this->character->getCharacter();

        $this->assertEquals($location->x, $character->map->character_position_x);
        $this->assertEquals($location->y, $character->map->character_position_y);

        $questInventory = $character->inventory->slots;

        // Gained the item:
        $this->assertTrue($questInventory->isNotEmpty());

        // Check the item matches:
        $item = $questInventory->filter(function($slot) use($location) {
            return $slot->item_id === $location->questRewardItem->id;
        })->first();

        $this->assertFalse(is_null($item));
    }

    public function testCharacterCannotGetDropTwiceFromLocation() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->once()->andReturn(false);

        $location = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
        ]);

        $item = $location->questRewardItem()->create([
            'name'           => 'Artifact',
            'type'           => 'artifact',
            'base_damage'    => 10,
            'cost'           => 10,
        ]);

        $location->update([
            'quest_reward_item_id' => $item->id,
        ]);

        $character = $this->character->inventoryManagement()
                                     ->giveItem($item)
                                     ->getCharacterFactory()
                                     ->getCharacter();

        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/move/' . $character->id, [
                             'character_position_x' => $location->x,
                             'character_position_y' => $location->y,
                             'position_x'           => 0,
                             'position_y'           => 0,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $character = $this->character->getCharacter();

        $this->assertEquals($location->x, $character->map->character_position_x);
        $this->assertEquals($location->y, $character->map->character_position_y);

        // Did not gain the item again:
        $this->assertEquals(1, $character->inventory->slots->count());
    }

    public function testMoveCharacterToPort() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $location = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->once()->andReturn(false);

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/move/' . $character->id, [
                             'character_position_x' => 64,
                             'character_position_y' => 64,
                             'position_x'           => 0,
                             'position_y'           => 0,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals($location->name, $content->port_details->current_port->name);
    }

    public function testIsNotWater() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->once()->andReturn(false);

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/move/' . $character->id, [
                             'character_position_x' => 336,
                             'character_position_y' => 288,
                             'position_x'           => 0,
                             'position_y'           => 0,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testIsWaterNoItem() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->andReturn(true);

        $response = $this->actingAs($user)
                         ->json('POST', '/api/move/' . $character->id, [
                             'character_position_x' => 160,
                             'character_position_y' => 64,
                             'position_x'           => 0,
                             'position_y'           => 0,
                         ])
                         ->response;

        $this->assertEquals(422, $response->status());
    }

    public function testCannotTeleportToWater() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $this->createItem([
            'name'                 => 'Flask of Fresh Air',
            'type'                 => 'quest',
            'description'          => 'Allows you to walk on water.',
            'effect'               => 'walk-on-water',
            'can_craft'            => false,
        ]);

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->once()->andReturn(true);

        $response = $this->actingAs($user)
                         ->json('POST', '/api/map/teleport/' . $character->id, [
                             'x'       => 160,
                             'y'       => 64,
                             'cost'    => 1,
                             'timeout' => 1,
                         ])
                         ->response;

        $this->assertEquals(422, $response->status());
    }

    public function testCannotTeleportNotEnoughGold() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $character = $this->character->updateCharacter(['gold' => 0])->getCharacter();
        $user      = $this->character->getUser();

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->once()->andReturn(false);

        $response = $this->actingAs($user)
                         ->json('POST', '/api/map/teleport/' . $character->id, [
                             'x' => 160,
                             'y' => 64,
                             'cost' => 10000,
                             'timeout' => 1,
                         ])
                         ->response;

        $this->assertEquals(422, $response->status());
    }

    public function testCannotTeleportLocationsDoNotExist() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->once()->andReturn(false);

        $response = $this->actingAs($user)
                         ->json('POST', '/api/map/teleport/' . $character->id, [
                             'x' => 860,
                             'y' => 864,
                             'cost' => 0,
                             'timeout' => 1
                         ])
                         ->response;

        $this->assertEquals(422, $response->status());
    }

    public function testIsWaterWithItem() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $character = $this->character->inventoryManagement()
                                     ->giveItem($this->createItem([
                                        'name'           => 'Artifact',
                                        'type'           => 'artifact',
                                        'base_damage'    => 10,
                                        'cost'           => 10,
                                        'effect'         => 'walk-on-water',
                                    ]))
                                     ->getCharacter();
        $user      = $this->character->getUser();

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->andReturn(true);

        $response = $this->actingAs($user)
                         ->json('POST', '/api/move/' . $character->id, [
                             'character_position_x' => 174,
                             'character_position_y' => 64,
                             'position_x'           => 0,
                             'position_y'           => 0,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testTeleportToWaterWithItem() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $character = $this->character->inventoryManagement()
                                     ->giveItem($this->createItem([
                                        'name'           => 'Artifact',
                                        'type'           => 'artifact',
                                        'base_damage'    => 10,
                                        'cost'           => 10,
                                        'effect'         => 'walk-on-water',
                                    ]))
                                     ->getCharacter();
        $user      = $this->character->getUser();

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->once()->andReturn(true);

        $response = $this->actingAs($user)
                            ->json('POST', '/api/map/teleport/' . $character->id, [
                                'x' => 176,
                                'y' => 64,
                                'cost' => 0,
                                'timeout' => 1,
                            ])
                            ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testCannotSetSailUnrecognizedPort() {
        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $port = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
        ]);

        $response = $this->actingAs($user)
            ->json('POST', '/api/map/set-sail/'.$port->id.'/' . $character->id, [
                'current_port_id' => 3,
                'time_out_value'  => 1,
                'cost'            => 3000,
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('Invalid port to set sail from.', $content->message);
    }

    public function testCannotSetSailNotEnoughGold() {
        $secondPort = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
        ]);

        $port = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
        ]);

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
            ->json('POST', '/api/map/set-sail/'.$port->id.'/' . $character->id, [
                'current_port_id' => $secondPort->id,
                'time_out_value'  => 1,
                'cost'            => 3000,
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('You don\'t have the gold', $content->message);
    }

    public function testCannotSetSailMissingParams() {
        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
        ]);

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
            ->json('POST', '/api/map/set-sail/'.Location::first()->id.'/' . $character->id, [])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());

        $this->assertEquals('Current Port Is required.', $content->errors->current_port_id[0]);
        $this->assertEquals('Cost is required.', $content->errors->cost[0]);
        $this->assertEquals('Time out value is required.', $content->errors->time_out_value[0]);
    }

    public function testCannotSetSailInValidData() {
        $portOne = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $portTwo = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
            ->json('POST', '/api/map/set-sail/'.$portOne->id.'/' . $character->id, [
                'current_port_id' => $portTwo->id,
                'time_out_value'  => 0,
                'cost'            => 0,
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());

        $this->assertEquals('The port you are trying to go doesn\'t exist.', $content->message);
    }

    public function testCanSetSail() {
        $portOne = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $portTwo = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $character = $this->character->updateCharacter(['gold' => 1000])->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
            ->json('POST', '/api/map/set-sail/'.$portOne->id.'/' . $character->id, [
                'current_port_id' => $portTwo->id,
                'time_out_value'  => 1,
                'cost'            => 100,
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());

        $this->assertEquals(64, $content->character_position_details->character_position_x);
        $this->assertEquals(64, $content->character_position_details->character_position_y);

        $character = $this->character->getCharacter();

        $this->assertNotNull($character->can_move_again_at);
        $this->assertFalse($character->can_move);
    }

    public function testCanSetSailAndGetReward() {
        $port = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $location = $this->createLocation([
            'name'        => 'Sample 2',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $item = $location->questRewardItem()->create([
            'name'           => 'Artifact',
            'type'           => 'artifact',
            'base_damage'    => 10,
            'cost'           => 10,
        ]);

        $location->update([
            'quest_reward_item_id' => $item->id,
        ]);

        $character = $this->character->updateCharacter(['gold' => 1000])->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
            ->json('POST', '/api/map/set-sail/'.$location->id.'/' . $character->id, [
                'current_port_id' => $port->id,
                'time_out_value'  => 1,
                'cost'            => 100,
            ])
            ->response;

        $this->assertEquals(200, $response->status());

        $character = $this->character->getCharacter();

        $slots = $character->inventory->slots;

        // Gained the item:
        $this->assertTrue($slots->isNotEmpty());

        // Check the item matches:
        $item = $slots->filter(function($slot) use($location) {
            return $slot->item_id === $location->questRewardItem->id;
        })->first();

        $this->assertFalse(is_null($item));
    }

    public function testCanTeleportAndGetReward() {
        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $location = $this->createLocation([
            'name'        => 'Sample 2',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $item = $location->questRewardItem()->create([
            'name'           => 'Artifact',
            'type'           => 'artifact',
            'base_damage'    => 10,
            'cost'           => 10,
        ]);

        $location->update([
            'quest_reward_item_id' => $item->id,
        ]);

        $character = $this->character->updateCharacter(['gold' => 1000])->getCharacter();
        $user      = $this->character->getUser();

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->once()->andReturn(false);

        $response = $this->actingAs($user)
            ->json('POST', '/api/map/teleport/' . $character->id, [
                'x' => 32,
                'y'  => 32,
                'cost' => 100,
                'timeout' => 1
            ])
            ->response;

        $this->assertEquals(200, $response->status());

        $character = $this->character->getCharacter();

        $slots = $character->inventory->slots;

        // Gained the item:
        $this->assertTrue($slots->isNotEmpty());

        // Check the item matches:
        $item = $slots->filter(function($slot) use($location) {
            return $slot->item_id === $location->questRewardItem->id;
        })->first();

        $this->assertFalse(is_null($item));
    }

    public function testCanSetSailAndNotGetReward() {
        $port = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $location = $this->createLocation([
            'name'        => 'Sample 2',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $item = $location->questRewardItem()->create([
            'name'           => 'Artifact',
            'type'           => 'artifact',
            'base_damage'    => 10,
            'cost'           => 10,
        ]);

        $location->update([
            'quest_reward_item_id' => $item->id,
        ]);

        $character = $this->character->updateCharacter([
            'gold' => 1000,
        ])->inventoryManagement()->giveItem($item)->getCharacterFactory()->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
            ->json('POST', '/api/map/set-sail/'.$location->id.'/' . $character->id, [
                'current_port_id' => $port->id,
                'time_out_value'  => 1,
                'cost'            => 100,
            ])
            ->response;

        $this->assertEquals(200, $response->status());

        // Did not gain the item again:
        $this->assertEquals(1, $this->character->getCharacter()->inventory->slots->count());
    }

    public function testCanTeleportAndNotGetReward() {
        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $location = $this->createLocation([
            'name'        => 'Sample 2',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
            'game_map_id' => $this->character->getCharacter()->map->game_map_id,
        ]);

        $item = $location->questRewardItem()->create([
            'name'           => 'Artifact',
            'type'           => 'artifact',
            'base_damage'    => 10,
            'cost'           => 10,
        ]);

        $location->update([
            'quest_reward_item_id' => $item->id,
        ]);

        $character = $this->character->updateCharacter([
            'gold' => 1000,
        ])->inventoryManagement()->giveItem($item)->getCharacterFactory()->getCharacter();
        $user      = $this->character->getUser();

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->once()->andReturn(false);

        $response = $this->actingAs($user)
            ->json('POST', '/api/map/teleport/' . $character->id, [
                'x' => 32,
                'y'  => 32,
                'cost' => 10,
                'timeout' => 1,
            ])
            ->response;

        $this->assertEquals(200, $response->status());

        // Did not gain the item again:
        $this->assertEquals(1, $this->character->getCharacter()->inventory->slots->count());
    }

    public function testCannotTraverseMissingParams() {
        $user = $this->character->getUser();
        $character = $this->character->getCharacter();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/map/traverse/' . $character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('Map id is required.', $content->errors->map_id[0]);
    }

    public function testMissingItemCannotTraverse() {
        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'name' => 'Labyrinth'
        ]);

        $response = $this->actingAs($user)
            ->json('POST', '/api/map/traverse/' . $character->id, [
                'map_id' => $gameMap->id,
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('You are missing a required item to travel to that plane.', $content->message);
    }

    public function testMissingItemCannotTraverseToUnknownMap() {
        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'name' => 'Bananas'
        ]);

        $response = $this->actingAs($user)
            ->json('POST', '/api/map/traverse/' . $character->id, [
                'map_id' => $gameMap->id,
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('You are missing a required item to travel to that plane.', $content->message);
    }

    public function testCanTraverse() {
        $user      = $this->character->getUser();
        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem(['effect' => ItemEffectsValue::LABYRINTH])
        )->getCharacter();

        $gameMap = $this->createGameMap([
            'name' => 'Labyrinth'
        ]);

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->once()->andReturn(false);

        $response = $this->actingAs($user)
            ->json('POST', '/api/map/traverse/' . $character->id, [
                'map_id' => $gameMap->id,
            ])
            ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testCanTraverseBackToSurface() {
        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $gameMap = $this->createGameMap([
            'name' => 'Surface'
        ]);

        $water = Mockery::mock(MapTileValue::class)->makePartial();

        $this->app->instance(MapTileValue::class, $water);

        $water->shouldReceive('getTileColor')->andReturn("1");
        $water->shouldReceive('isWaterTile')->once()->andReturn(false);

        $response = $this->actingAs($user)
            ->json('POST', '/api/map/traverse/' . $character->id, [
                'map_id' => $gameMap->id,
            ])
            ->response;

        $this->assertEquals(200, $response->status());
    }
}
