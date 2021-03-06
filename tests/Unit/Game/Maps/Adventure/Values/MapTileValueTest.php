<?php

namespace Tests\Unit\Game\Maps\Adventure\Values;


use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use App\Game\Maps\Values\MapTileValue;
use App\Flare\Models\GameMap;
use Tests\Setup\Character\CharacterFactory;
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
        $path = Storage::disk('maps')->putFile('Surface', resource_path('maps/surface.jpg'));

        GameMap::first()->update([
            'path' => $path
        ]);

        $mapTileValue = resolve(MapTileValue::class);

        $this->assertNotEquals("", $mapTileValue->getTileColor($this->character, 0, 0));
    }

    public function testGetIsWater() {
        $path = Storage::disk('maps')->putFile('Surface', resource_path('maps/surface.jpg'));

        GameMap::first()->update([
            'path' => $path
        ]);
        
        $mapTileValue = resolve(MapTileValue::class);

        $this->assertTrue($mapTileValue->isWaterTile(114217255));
    }

    protected function setUpCharacter(array $options = []) {
        

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->getCharacter();
    }
}
