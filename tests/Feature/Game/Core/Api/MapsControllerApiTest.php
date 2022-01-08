<?php

namespace Tests\Feature\Game\Core\Api;

use App\Flare\Models\GameMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateLocation;

class MapsControllerApiTest extends TestCase {

    use RefreshDatabase, CreateGameMap, CreateLocation;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetMapDetails() {
        $this->createGameMap([
            'name' => 'Surface'
        ]);

        $response = $this->actingAs($this->character->getUser())
                         ->json('GET', '/api/maps/' . $this->character->getCharacter(false)->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());

        $this->assertEquals($this->character->getCharacter(false)->map->gameMap->name, $content->current_map);
        $this->assertNotEmpty($content->maps);
    }

    public function testGetPurgatoryInMapInfo() {
        $this->createGameMap([
            'name' => 'Purgatory',
            'required_location_id' => $this->createLocation([
                'name'                 => 'Sample',
                'game_map_id'          => GameMap::where('name', 'Surface')->first()->id,
                'quest_reward_item_id' => null,
                'description'          => 'Test',
                'is_port'              => false,
                'x'                    => 16,
                'y'                    => 16,
            ])->id
        ]);

        $response = $this->actingAs($this->character->getUser())
            ->json('GET', '/api/maps/' . $this->character->getCharacter(false)->id)
            ->response;

        $content = json_decode($response->content());


        $this->assertEquals(200, $response->status());

        $this->assertEquals($this->character->getCharacter(false)->map->gameMap->name, $content->current_map);
        $this->assertNotEmpty($content->maps);
        $this->assertEquals('Purgatory', $content->maps[0]->name);
    }
}
