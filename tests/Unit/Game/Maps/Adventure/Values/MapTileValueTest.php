<?php

namespace Tests\Unit\Game\Maps\Adventure\Values;

use App\Admin\Models\GameMap;
use App\Game\Maps\Adventure\Values\MapTileValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Setup\CharacterSetup;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class MapTileValueTest extends TestCase
{

    use RefreshDatabase, CreateUser;

    private $character = null;

    public function setUp(): void {
        parent::setUp();

        $this->setUpCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        Storage::disk('maps')->deleteDirectory('Surface/');

        $this->character = null;
    }

    public function testGetMapColor() {
        $mapTileValue = resolve(MapTileValue::class);

        $this->assertNotEquals("", $mapTileValue->getTileColor($this->character, 0, 0));
    }

    public function testGetIsWater() {
        $mapTileValue = resolve(MapTileValue::class);

        $this->assertTrue($mapTileValue->isWaterTile(114217255));
    }

    protected function setUpCharacter(array $options = []) {
        $user = $this->createUser();

        $path = Storage::disk('maps')->putFile('Surface', resource_path('maps/surface.jpg'));

        $gameMap = GameMap::create([
            'name'    => 'surface',
            'path'    => $path,
            'default' => true,
        ]);

        $this->character = (new CharacterSetup)->setupCharacter($user, $options)
                                               ->getCharacter();

        $this->character->map()->create([
            'character_id' => $this->character->id,
            'game_map_id'  => $gameMap->id,
        ]);
    }
}
