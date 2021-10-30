<?php

namespace Tests\Unit\Game\Kingdoms\Handlers;

use App\Game\Kingdoms\Handlers\KingdomHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomHandlerTest extends TestCase {

    use RefreshDatabase;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testDecreaseMorale() {
       $kingdom = (new CharacterFactory())
                        ->createBaseCharacter()
                        ->givePlayerLocation()
                        ->kingdomManagement()
                        ->assignKingdom()
                        ->assignBuilding()
                        ->getKingdom();

       foreach($kingdom->buildings as $building) {
           $building->update([
               'current_durability' => 0
           ]);
       }

       $kingdom = $kingdom->refresh();

       $kingdomHandler = resolve(KingdomHandler::class);

       $kingdom = $kingdomHandler->setKingdom($kingdom)->decreaseMorale()->getKingdom();

       $this->assertLessThan(.50, $kingdom->current_morale);
    }
}
