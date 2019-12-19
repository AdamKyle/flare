<?php

namespace Tests\Feature\Game\Map;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use App\Game\Maps\Adventure\Events\MoveTimeOutEvent;

class MapControllerApiTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRace,
        CreateClass,
        CreateCharacter;

    private $user;

    private $character;

    public function setUp(): void {
        parent::setUp();

        Queue::fake();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->user      = null;
        $this->character = null;
        $this->monster   = null;
    }

    public function testGetMap() {
        $this->setUpCharacter();

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

        $this->setUpCharacter();

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

        File::copy(resource_path('tests/surface.png'), Storage::disk('public')->path('/') . 'surface.png');

        $this->setUpCharacter();

        $response = $this->actingAs($this->user, 'api')
                         ->json('GET', '/api/is-water/' . $this->character->id, [
                             'character_position_x' => 336,
                             'character_position_y' => 304,
                         ])
                         ->response;

        $this->assertEquals(422, $response->status());

        unlink(Storage::disk('public')->path('/') . 'surface.png');
    }

    public function testIsNotWater() {
        Event::fake([
            MoveTimeOutEvent::class,
        ]);
        
        File::copy(resource_path('tests/surface.png'), Storage::disk('public')->path('/') . 'surface.png');

        $this->setUpCharacter();

        $response = $this->actingAs($this->user, 'api')
                         ->json('GET', '/api/is-water/' . $this->character->id, [
                             'character_position_x' => 336,
                             'character_position_y' => 288,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        unlink(Storage::disk('public')->path('/') . 'surface.png');
    }

    protected function setUpCharacter(array $options = []) {
        $race = $this->createRace([
            'str_mod' => 3,
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
        ]);

        $this->user = $this->createUser();

        $this->character = $this->createCharacter([
            'name' => 'Sample',
            'user_id' => $this->user->id,
            'level' => isset($options['level']) ? $options['level'] : 1,
            'xp' => isset($options['xp']) ? $options['xp'] : 0,
            'can_attack' => true,
        ]);

        $this->character->inventory()->create([
            'character_id' => $this->character->id,
        ]);

        $this->character->map()->create([
            'character_id' => $this->character->id,
        ]);
    }
}
