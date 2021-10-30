<?php

namespace Tests\Console\Kingdoms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateKingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;

class DeleteKingdomLogsTest extends TestCase
{

    use RefreshDatabase, CreateKingdom;

    public function testDeleteAllAdventureLogs() {

        $character = (new CharacterFactory())->createBaseCharacter()
                                             ->givePlayerLocation()
                                             ->kingdomManagement()
                                             ->assignKingdom()
                                             ->assignBuilding()
                                             ->assignUnits();

        $this->createKingdomLog([
            'character_id'    => $character->getCharacter(false)->id,
            'from_kingdom_id' => $character->getKingdom()->id,
            'to_kingdom_id'   => $character->getKingdom()->id,
            'status'          => KingdomLogStatusValue::UNITS_RETURNING,
            'units_sent'      => [],
            'units_survived'  => [],
            'old_defender'    => $character->getKingdom()->load('buildings', 'units')->toArray(),
            'new_defender'    => $character->getKingdom()->load('buildings', 'units')->toArray(),
            'published'       => true,
        ]);

        KingdomLog::first()->update(['created_at' => now()->subDays(550)]);

        $this->assertEquals(0, $this->artisan('clean:kingdomLogs'));

        $this->assertTrue(KingdomLog::all()->isEmpty());
    }

}
