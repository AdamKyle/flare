<?php

namespace Tests\Unit\Game\Maps\Services;

use App\Game\Maps\Services\LocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\KingdomManagement;

class LocationServiceTest extends TestCase {

    use RefreshDatabase;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->kingdomManagement()
                                                 ->assignKingdom()
                                                 ->assignUnits();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanManageOwnKingdom() {
        $locationService = resolve(LocationService::class);

        $data = $locationService->getLocationData($this->character->getCharacter());

        $this->assertTrue($data['can_manage_kingdom']);
    }

    public function testCanAttackKingdom() {
        $locationService = resolve(LocationService::class);

        $character = $this->character->getCharacter();

        $secondCharacter = $this->createEnemyKingdom()->getCharacter();

        $character->map()->update([
            'character_position_x' => $secondCharacter->map->character_position_x,
            'character_position_y' => $secondCharacter->map->character_position_y,
        ]);

        $character = $character->refresh();

        $data = $locationService->getLocationData($character);

        $this->assertFalse($data['can_manage_kingdom']);
        $this->assertNotEmpty($data['kingdom_to_attack']);
        $this->assertTrue($data['can_attack_kingdom']);
    }

    protected function createEnemyKingdom(): KingdomManagement {
        return (new CharacterFactory)->createBaseCharacter()
                                     ->givePlayerLocation(32, 32)
                                     ->kingdomManagement()
                                     ->assignKingdom([
                                        'x_position'  => 32,
                                        'y_position' => 32,
                                    ]);
    }
}
