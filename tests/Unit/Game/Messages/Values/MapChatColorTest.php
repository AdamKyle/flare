<?php

namespace Tests\Unit\Game\Messages\Values;

use App\Game\Messages\Values\MapChatColor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class MapChatColorTest extends TestCase
{
    use RefreshDatabase, CreateGameMap;

    public function testGetSurfaceColor() {
        $gameMap = $this->createGameMap(['name' => 'Surface']);

        $value = new MapChatColor($gameMap->name);

        $this->assertEquals(MapChatColor::SURFACE, $value->getColor());
    }

    public function testGetLabyrinthColor() {
        $gameMap = $this->createGameMap(['name' => 'Labyrinth']);

        $value = new MapChatColor($gameMap->name);

        $this->assertEquals(MapChatColor::LABYRINTH, $value->getColor());
    }

    public function testGetHellColor() {
        $gameMap = $this->createGameMap(['name' => 'Hell']);

        $value = new MapChatColor($gameMap->name);

        $this->assertEquals(MapChatColor::HELL, $value->getColor());
    }

    public function testGetPurgatoryColor() {
        $gameMap = $this->createGameMap(['name' => 'Purgatory']);

        $value = new MapChatColor($gameMap->name);

        $this->assertEquals(MapChatColor::PURGATORY, $value->getColor());
    }
}
