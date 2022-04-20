<?php

namespace Tests\Unit\Admin\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\GameMap;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateUser;
use Tests\TestCase;

class GameMapTest extends TestCase
{
    use RefreshDatabase, CreateUser;

    private $character;

    public function setUp(): void {
        parent::setup();

        $this->setUpCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testGameMapCanGetMaps()
    {
        $this->assertTrue(GameMap::first()->maps->isNotEmpty());
    }

    protected function setUpCharacter(array $options = []) {

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->getCharacter();
    }
}
