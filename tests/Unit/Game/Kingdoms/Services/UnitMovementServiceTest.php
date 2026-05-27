<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\GameUnit;
use App\Flare\Models\KingdomUnit;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Service\UnitMovementService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class UnitMovementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testRemoveUnitsFromKingdomReturnsOnlyRowsActuallyRemoved(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);
        $kingdomUnit = KingdomUnit::factory()->create([
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => 5,
        ]);

        $removedUnits = resolve(UnitMovementService::class)->removeUnitsFromKingdom([
            [
                'kingdom_id' => $kingdom->id,
                'unit_id' => $kingdomUnit->id,
                'amount' => 3,
            ],
            [
                'kingdom_id' => $kingdom->id,
                'unit_id' => $kingdomUnit->id,
                'amount' => 10,
            ],
            [
                'kingdom_id' => $kingdom->id,
                'unit_id' => 999999,
                'amount' => 1,
            ],
        ]);

        $this->assertCount(1, $removedUnits);
        $this->assertSame(2, $kingdomUnit->refresh()->amount);
    }

    public function testMoveUnitsOnlyQueuesRowsActuallyRemovedFromSourceKingdom(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $sourceKingdom = $characterFactory->kingdomManagement()->assignKingdom(['x_position' => 16, 'y_position' => 16])->getKingdom();
        $targetKingdom = $characterFactory->kingdomManagement()->assignKingdom(['x_position' => 32, 'y_position' => 32])->getKingdom();
        $character = $characterFactory->getCharacter();
        $character->skills()->whereHas('baseSkill', function ($query) {
            $query->where('type', SkillTypeValue::EFFECTS_KINGDOM->value);
        })->update(['skill_type' => SkillTypeValue::EFFECTS_KINGDOM->value]);
        $character = $character->refresh();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);
        $kingdomUnit = KingdomUnit::factory()->create([
            'kingdom_id' => $sourceKingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => 5,
        ]);

        resolve(UnitMovementService::class)->moveUnitsToKingdom($character, $targetKingdom, [
            'units_to_move' => [
                [
                    'kingdom_id' => $sourceKingdom->id,
                    'unit_id' => $kingdomUnit->id,
                    'amount' => 4,
                ],
                [
                    'kingdom_id' => $sourceKingdom->id,
                    'unit_id' => $kingdomUnit->id,
                    'amount' => 4,
                ],
            ],
        ]);

        $unitMovementQueue = UnitMovementQueue::first();

        $this->assertCount(1, $unitMovementQueue->units_moving);
        $this->assertSame(4, $unitMovementQueue->units_moving[0]['amount']);
        $this->assertSame(1, $kingdomUnit->refresh()->amount);
    }
}
