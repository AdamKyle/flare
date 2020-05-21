<?php

namespace Tests\Feature\Game\Map;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Setup\CharacterSetup;
use App\Game\Maps\Adventure\Events\MoveTimeOutEvent;
use App\Admin\Models\GameMap;

class MapControllerApiTest extends TestCase
{
    use RefreshDatabase,
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

    public function testIsWater() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);

        $this->setUpCharacter();

        $response = $this->actingAs($this->user, 'api')
                         ->json('GET', '/api/is-water/' . $this->character->id, [
                             'character_position_x' => 1680,
                             'character_position_y' => 1000,
                         ])
                         ->response;

        $this->assertEquals(422, $response->status());
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
