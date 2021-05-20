<?php

namespace Tests\Feature\Game\Core\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class MapsControllerApiTest extends TestCase {

    use RefreshDatabase, CreateGameMap;

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
            'name' => 'Apples'
        ]);
        $response = $this->actingAs($this->character->getUser())
                         ->json('GET', '/api/maps/' . $this->character->getCharacter()->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());

        $this->assertEquals($this->character->getCharacter()->map->gameMap->name, $content->current_map);
        $this->assertNotEmpty($content->maps);
    }
}
