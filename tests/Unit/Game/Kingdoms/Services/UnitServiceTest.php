<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\GameUnit;
use App\Flare\Models\KingdomUnit;
use App\Flare\Models\UnitInQueue;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class UnitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_unit_maximum_can_be_queued(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $unit = GameUnit::factory()->create();
        KingdomUnit::factory()->create([
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => KingdomMaxValue::MAX_UNIT - 10,
        ]);

        $this->assertTrue(resolve(UnitService::class)->canQueueUnits($kingdom, $unit, 10));
    }

    public function test_exact_resource_spend_is_allowed(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 10,
            'current_clay' => 10,
            'current_stone' => 10,
            'current_iron' => 10,
            'current_steel' => 0,
            'current_population' => 1,
        ])->getKingdom();
        $unit = GameUnit::factory()->create([
            'wood_cost' => 10,
            'clay_cost' => 10,
            'stone_cost' => 10,
            'iron_cost' => 10,
            'steel_cost' => 0,
            'required_population' => 1,
        ]);

        $result = resolve(UnitService::class)->handlePayment($unit, $kingdom, 1);

        $this->assertSame([], $result);
        $this->assertSame(0, $kingdom->refresh()->current_wood);
    }

    public function test_overspend_is_rejected_without_mutating_resources(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 9,
            'current_clay' => 10,
            'current_stone' => 10,
            'current_iron' => 10,
            'current_steel' => 0,
            'current_population' => 1,
        ])->getKingdom();
        $unit = GameUnit::factory()->create([
            'wood_cost' => 10,
            'clay_cost' => 10,
            'stone_cost' => 10,
            'iron_cost' => 10,
            'steel_cost' => 0,
            'required_population' => 1,
        ]);

        $result = resolve(UnitService::class)->handlePayment($unit, $kingdom, 1);

        $this->assertSame("You don't have the resources.", $result['message']);
        $this->assertSame(9, $kingdom->refresh()->current_wood);
    }

    public function test_manual_and_capital_city_queued_units_count_toward_maximum(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create();
        KingdomUnit::factory()->create([
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => KingdomMaxValue::MAX_UNIT - 15,
        ]);
        UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => 10,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $kingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [[
                'name' => $unit->name,
                'amount' => 5,
                'secondary_status' => CapitalCityQueueStatus::RECRUITING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::RECRUITING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $this->assertFalse(resolve(UnitService::class)->canQueueUnits($kingdom, $unit, 1));
    }

    public function test_cancellation_rejected_capital_city_queued_units_do_not_count_toward_maximum(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create();

        KingdomUnit::factory()->create([
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => KingdomMaxValue::MAX_UNIT - 1,
        ]);
        $kingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [[
                'name' => $unit->name,
                'amount' => 10,
                'secondary_status' => CapitalCityQueueStatus::CANCELLATION_REJECTED,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::CANCELLATION_REJECTED,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);

        $this->assertTrue(resolve(UnitService::class)->canQueueUnits($kingdom, $unit, 1));
    }
}
