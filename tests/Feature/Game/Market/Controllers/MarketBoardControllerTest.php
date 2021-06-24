<?php

namespace Tests\Feature\Game\Kingdom;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateLocation;

class MarketBoardControllerTest extends TestCase {

    use RefreshDatabase, CreateLocation;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCannotAccessMarketBoard() {
        $this->actingAs($this->character->getUser())->visitRoute('game.market')->see('You must first travel to a port to access the market board. Ports are blue ship icons on the map.');
    }

    public function testCannotAccessMarketBoardIsLocationButNotPort() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'game_map_id' => 1,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $this->actingAs($this->character->getUser())->visitRoute('game.market')->see('You must first travel to a port to access the market board. Ports are blue ship icons on the map.');
    }

    public function testCanSeeMarket() {
        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'is_port' => true,
            'game_map_id' => 1,
            'name' => Str::random(10),
            'description' => Str::random(40),
        ]);

        $this->actingAs($this->character->getUser())->visitRoute('game.market')->see('You can click on the row in the table to open the modal to buy or browse.');
    }
}
