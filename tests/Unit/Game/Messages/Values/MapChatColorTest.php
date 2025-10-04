<?php

namespace Tests\Unit\Game\Messages\Values;

use App\Game\Messages\Values\MapChatColor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateMap;

class MapChatColorTest extends TestCase
{
    use CreateGameMap, CreateMap, RefreshDatabase;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_surface_color()
    {
        $character = $this->character->getCharacter();

        $map = $this->createMap([
            'character_id' => $character->id,
            'character_position_x' => 0,
            'character_position_y' => 0,
            'game_map_id' => $this->createGameMap([
                'name' => 'Surface',
            ]),
        ]);

        $mapColor = new MapChatColor($map->gameMap->name);

        $this->assertEquals($mapColor->getColor(), MapChatColor::SURFACE);
    }

    public function test_labyrinth_color()
    {
        $character = $this->character->getCharacter();

        $map = $this->createMap([
            'character_id' => $character->id,
            'character_position_x' => 0,
            'character_position_y' => 0,
            'game_map_id' => $this->createGameMap([
                'name' => 'Labyrinth',
            ]),
        ]);

        $mapColor = new MapChatColor($map->gameMap->name);

        $this->assertEquals($mapColor->getColor(), MapChatColor::LABYRINTH);
    }

    public function test_dungeons_color()
    {
        $character = $this->character->getCharacter();

        $map = $this->createMap([
            'character_id' => $character->id,
            'character_position_x' => 0,
            'character_position_y' => 0,
            'game_map_id' => $this->createGameMap([
                'name' => 'Dungeons',
            ]),
        ]);

        $mapColor = new MapChatColor($map->gameMap->name);

        $this->assertEquals($mapColor->getColor(), MapChatColor::DUNGEONS);
    }

    public function test_hell_color()
    {
        $character = $this->character->getCharacter();

        $map = $this->createMap([
            'character_id' => $character->id,
            'character_position_x' => 0,
            'character_position_y' => 0,
            'game_map_id' => $this->createGameMap([
                'name' => 'Hell',
            ]),
        ]);

        $mapColor = new MapChatColor($map->gameMap->name);

        $this->assertEquals($mapColor->getColor(), MapChatColor::HELL);
    }

    public function test_shadow_plane_color()
    {
        $character = $this->character->getCharacter();

        $map = $this->createMap([
            'character_id' => $character->id,
            'character_position_x' => 0,
            'character_position_y' => 0,
            'game_map_id' => $this->createGameMap([
                'name' => 'Shadow Plane',
            ]),
        ]);

        $mapColor = new MapChatColor($map->gameMap->name);

        $this->assertEquals($mapColor->getColor(), MapChatColor::SHP);
    }

    public function test_purgatory_color()
    {
        $character = $this->character->getCharacter();

        $map = $this->createMap([
            'character_id' => $character->id,
            'character_position_x' => 0,
            'character_position_y' => 0,
            'game_map_id' => $this->createGameMap([
                'name' => 'Purgatory',
            ]),
        ]);

        $mapColor = new MapChatColor($map->gameMap->name);

        $this->assertEquals($mapColor->getColor(), MapChatColor::PURGATORY);
    }

    public function test_the_ice_plane_color()
    {
        $character = $this->character->getCharacter();

        $map = $this->createMap([
            'character_id' => $character->id,
            'character_position_x' => 0,
            'character_position_y' => 0,
            'game_map_id' => $this->createGameMap([
                'name' => 'The Ice Plane',
            ]),
        ]);

        $mapColor = new MapChatColor($map->gameMap->name);

        $this->assertEquals($mapColor->getColor(), MapChatColor::ICE_PLANE);
    }
}
