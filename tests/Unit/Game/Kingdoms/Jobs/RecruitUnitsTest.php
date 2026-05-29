<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Flare\Models\GameUnit;
use App\Flare\Models\KingdomUnit;
use App\Flare\Models\UnitInQueue;
use App\Game\Kingdoms\Jobs\RecruitUnits;
use App\Game\Kingdoms\Service\CapitalCityUnitManagement;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class RecruitUnitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_completion_rejects_over_maximum_queue_and_refunds_resources(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 0,
            'current_clay' => 0,
            'current_stone' => 0,
            'current_iron' => 0,
            'current_population' => 0,
            'max_wood' => 1000,
            'max_clay' => 1000,
            'max_stone' => 1000,
            'max_iron' => 1000,
            'max_population' => 1000,
        ])->getKingdom();
        $unit = GameUnit::factory()->create([
            'wood_cost' => 10,
            'clay_cost' => 10,
            'stone_cost' => 10,
            'iron_cost' => 10,
            'required_population' => 1,
        ]);
        KingdomUnit::factory()->create([
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => KingdomMaxValue::MAX_UNIT,
        ]);
        $queue = UnitInQueue::factory()->create([
            'character_id' => $characterFactory->getCharacter()->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => 1,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);

        (new RecruitUnits($unit, $kingdom, 1, $queue->id))->handle(
            resolve(UpdateKingdom::class),
            resolve(CapitalCityUnitManagement::class),
            resolve(UnitService::class),
        );

        $kingdom = $kingdom->refresh();

        $this->assertNull(UnitInQueue::find($queue->id));
        $this->assertSame(10, $kingdom->current_wood);
        $this->assertSame(KingdomMaxValue::MAX_UNIT, $kingdom->units()->where('game_unit_id', $unit->id)->first()->amount);
    }
}
