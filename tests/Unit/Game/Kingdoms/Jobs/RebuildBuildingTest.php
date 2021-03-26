<?php

namespace Tests\Unit\Game\Kingdoms\Handlers;

use App\Game\Kingdoms\Jobs\RebuildBuilding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;

class RebuildBuildingTest extends TestCase {

    use RefreshDatabase;

    private $character;
    
    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->kingdomManagement()
                                                 ->assignKingdom()
                                                 ->assignBuilding();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testJobCouldNotBeExcecutedNoQueueFound() {
        $building = $this->character->getKingdom()->buildings->first();
        $user     = $this->character->getUser();

        $building->update([
            'current_durability' => 0
        ]);

        $building = $building->refresh();

        RebuildBuilding::dispatchNow($building, $user, 1);

        $building = $building->refresh();

        $this->assertEquals(0, $building->current_durability);
    }
}