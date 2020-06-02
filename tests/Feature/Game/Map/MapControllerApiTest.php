<?php

namespace Tests\Feature\Game\Map;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateUser;
use Tests\Setup\CharacterSetup;
use App\Game\Maps\Adventure\Events\MoveTimeOutEvent;
use App\Admin\Models\GameMap;

class MapControllerApiTest extends TestCase
{
    use RefreshDatabase,
     CreateLocation,
        CreateUser;

    private $user;

    private $character;

    public function setUp(): void {
        parent::setUp();

        Queue::fake();

        $this->setUpCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->user      = null;
        $this->character = null;
        $this->monster   = null;

        Storage::disk('maps')->deleteDirectory('Surface/');
    }

    public function testGetMap() {

        $response = $this->actingAs($this->user, 'api')
                         ->json('GET', '/api/map/' . $this->user->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals(0, $content->character_map->position_x);
        $this->assertEquals(32, $content->character_map->character_position_x);
    }

    public function testGetMapWithPort() {
        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'asset_path'  => null,
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->json('GET', '/api/map/' . $this->user->id)
            ->response;
        
        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status()); 

        $this->assertNotNull($content->port_details);
    }

    public function testMoveCharacter() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/move/' . $this->character->id, [
                             'character_position_x' => 48,
                             'character_position_y' => 48,
                             'position_x'           => 0,
                             'position_y'           => 0,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $this->character->refresh();

        $this->assertEquals(0, $this->character->map->position_x);
        $this->assertEquals(48, $this->character->map->character_position_x);
        $this->assertEquals(48, $this->character->map->character_position_y);
    }

    public function testMoveCharacterToPort() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $location = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'asset_path'  => null,
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
        ]);

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/move/' . $this->character->id, [
                             'character_position_x' => 64,
                             'character_position_y' => 64,
                             'position_x'           => 0,
                             'position_y'           => 0,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals($location->name, $content->current_port->name);
    }

    public function testIsNotWater() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $this->setUpCharacter();

        $response = $this->actingAs($this->user, 'api')
                         ->json('GET', '/api/is-water/' . $this->character->id, [
                             'character_position_x' => 336,
                             'character_position_y' => 288,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testCannotSetSailUnrecognizedPort() {
        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'asset_path'  => null,
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/map/set-sail/1/' . $this->character->id, [
                'current_port_id' => 3,
                'time_out_value'  => 1,
                'cost'            => 3000,
            ])
            ->response;
        
        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status()); 
        $this->assertEquals('This is not a recognized port.', $content->message);
    }

    public function testCannotSetSailNotEnoughGold() {
        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'asset_path'  => null,
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
        ]);

        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'asset_path'  => null,
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/map/set-sail/1/' . $this->character->id, [
                'current_port_id' => 2,
                'time_out_value'  => 1,
                'cost'            => 3000,
            ])
            ->response;
        
        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status()); 
        $this->assertEquals('Not enough gold.', $content->message);
    }

    public function testCannotSetSailMissingParams() {
        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'asset_path'  => null,
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/map/set-sail/1/' . $this->character->id, [])
            ->response;
        
        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status()); 

        $this->assertEquals('Current Port Is required.', $content->errors->current_port_id[0]);
        $this->assertEquals('Cost is required.', $content->errors->cost[0]);
        $this->assertEquals('Time out value is required.', $content->errors->time_out_value[0]);
    }

    public function testCannotSetSailInValidData() {
        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'asset_path'  => null,
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
        ]);

        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'asset_path'  => null,
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/map/set-sail/1/' . $this->character->id, [
                'current_port_id' => 2,
                'time_out_value'  => 0,
                'cost'            => 0,
            ])
            ->response;
        
        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status()); 

        $this->assertEquals('Invalid input. Please refresh and try again.', $content->message);
    }

    public function testCanSetSail() {
        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'asset_path'  => null,
            'is_port'     => true,
            'x'           => 64,
            'y'           => 64,
        ]);

        $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'asset_path'  => null,
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
        ]);

        $this->character->gold = 1000;
        $this->character->save();

        $this->character->refresh();

        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/map/set-sail/1/' . $this->character->id, [
                'current_port_id' => 2,
                'time_out_value'  => 1,
                'cost'            => 100,
            ])
            ->response;
        
        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status()); 

        $this->assertEquals(64, $content->character_position_details->character_position_x);
        $this->assertEquals(64, $content->character_position_details->character_position_y);

        $character = $this->character->refresh();

        $this->assertNotNull($character->can_move_again_at);
        $this->assertFalse($character->can_move);
    }

    protected function setUpCharacter(array $options = []) {
        $this->user = $this->createUser();

        $path = Storage::disk('maps')->putFile('Surface', resource_path('maps/surface.png'));

        $gameMap = GameMap::create([
            'name'    => 'surface',
            'path'    => $path,
            'default' => true,
        ]);

        $this->character = (new CharacterSetup)->setupCharacter($this->user, $options)
                                               ->getCharacter();

        $this->character->map()->create([
            'character_id' => $this->character->id,
            'game_map_id'  => $gameMap->id,
        ]);
    }
}
