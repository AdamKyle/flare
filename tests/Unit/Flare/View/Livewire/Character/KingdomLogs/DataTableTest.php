<?php

namespace Tests\Unit\Flare\View\Livewire\Character\KingdomLogs;

use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;
use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Kingdom\Logs\DataTable;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateKingdom;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateKingdom;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)
                                ->createBaseCharacter()
                                ->givePlayerLocation()
                                ->kingdomManagement()
                                ->assignKingdom()
                                ->assignBuilding()
                                ->assignUnits();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testComponentLoads() {
        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter(false)->id,
            'from_kingdom_id' => $this->character->getKingdom()->id,
            'to_kingdom_id'   => $this->character->getKingdom()->id,
            'status'          => KingdomLogStatusValue::UNITS_RETURNING,
            'units_sent'      => [],
            'units_survived'  => [],
            'old_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'new_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'published'       => true,
        ]);

        Livewire::test(DataTable::class, [
            'attackLogs' => KingdomLog::all(),
            'character'  => $this->character->getCharacter(false),
        ])->set('selected', [1])
          ->call('selectAll')
          ->set('search', KingdomLogStatusValue::UNITS_RETURNING);
    }
}
