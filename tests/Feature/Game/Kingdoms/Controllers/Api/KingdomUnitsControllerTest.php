<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\GameUnit;
use App\Flare\Models\UnitInQueue;
use App\Flare\Values\AutomationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomUnitsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_cancel_rejects_capital_city_owned_unit_queue(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create();
        $queue = UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => 1,
            'capital_city_unit_queue_id' => 123,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/recruit-units/cancel', [
                'queue_id' => $queue->id,
            ]);

        $response->assertStatus(422);
        $this->assertNotNull(UnitInQueue::find($queue->id));
    }

    public function test_manual_recruit_rejects_during_automation(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->assignUnits([], 1)->getKingdom();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();
        $gameUnit = $kingdom->units()->first()->gameUnit;

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$kingdom->id.'/recruit-units/'.$gameUnit->id, [
                'amount' => 1,
                'recruitment_type' => 'resources',
            ]);

        $response->assertStatus(422);
        $this->assertSame(0, UnitInQueue::where('kingdom_id', $kingdom->id)->count());
    }

    public function test_manual_recruit_cancel_rejects_during_automation(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create();
        $queue = UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => 1,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/recruit-units/cancel', [
                'queue_id' => $queue->id,
            ]);

        $response->assertStatus(422);
        $this->assertNotNull(UnitInQueue::find($queue->id));
    }
}
