<?php

namespace Tests\Unit\Game\Kingdoms\Transformers;

use App\Flare\Models\GameUnit;
use App\Flare\Models\UnitInQueue;
use App\Game\Kingdoms\Transformers\KingdomResourceHourlyProductionTransformer;
use App\Game\Kingdoms\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateKingdom;

class KingdomTransformerTest extends TestCase
{
    use CreateGameBuilding, CreateKingdom, RefreshDatabase;

    public function test_transform_includes_estimated_hourly_production(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $kingdom = $this->createKingdom([
            'character_id' => $character->id,
            'game_map_id' => $character->map->game_map_id,
            'treasury' => 0,
            'gold_bars' => 0,
        ]);

        $kingdom->buildings()->create([
            'game_building_id' => $this->createGameBuilding(['is_resource_building' => true, 'increase_stone_amount' => 100, 'increase_clay_amount' => 0, 'increase_wood_amount' => 0, 'increase_iron_amount' => 0])->id,
            'kingdom_id' => $kingdom->id,
            'level' => 1,
            'max_defence' => 100,
            'max_durability' => 100,
            'current_durability' => 100,
            'current_defence' => 100,
        ]);

        $result = resolve(KingdomTransformer::class)->transform($kingdom->refresh());

        $this->assertSame([
            'stone' => 100.0,
            'clay' => 0.0,
            'wood' => 0.0,
            'iron' => 0.0,
            'population' => 0.0,
        ], $result['estimated_hourly_production']);
    }

    public function testTransformIncludesCapitalCityBuildingQueues(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
        ])->getKingdom();
        $kingdomManagement = $characterFactory->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $startedAt = now()->subMinute();
        $completedAt = now()->addHour();

        $kingdomManagement->assignCapitalCityBuildingQueue([
            'requested_kingdom' => $capitalCity->id,
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ], [
            'secondary_status' => CapitalCityQueueStatus::BUILDING,
        ]);

        $result = resolve(KingdomTransformer::class)->transform($kingdom->refresh());

        $this->assertCount(1, $result['building_queue']);
        $this->assertSame($building->id, $result['building_queue'][0]['building_id']);
        $this->assertSame($startedAt->toDateTimeString(), $result['building_queue'][0]['started_at']->toDateTimeString());
        $this->assertSame($completedAt->toDateTimeString(), $result['building_queue'][0]['completed_at']->toDateTimeString());
        $this->assertSame(CapitalCityQueueStatus::BUILDING, $result['building_queue'][0]['phase_status']);
        $this->assertSame('Building', $result['building_queue'][0]['phase_timer_label']);
        $this->assertTrue($result['building_queue'][0]['is_capital_city_managed']);
    }

    public function testTransformIncludesCapitalCityUnitQueues(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
        ])->getKingdom();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $manualUnit = GameUnit::create([
            'name' => 'Spearmen',
            'description' => 'Spearmen',
            'attack' => 1,
            'defence' => 1,
            'wood_cost' => 1,
            'clay_cost' => 1,
            'stone_cost' => 1,
            'iron_cost' => 1,
            'steel_cost' => 0,
            'required_population' => 1,
            'time_to_recruit' => 60,
        ]);
        $capitalCityUnit = GameUnit::create([
            'name' => 'Archers',
            'description' => 'Archers',
            'attack' => 1,
            'defence' => 1,
            'wood_cost' => 1,
            'clay_cost' => 1,
            'stone_cost' => 1,
            'iron_cost' => 1,
            'steel_cost' => 0,
            'required_population' => 1,
            'time_to_recruit' => 60,
        ]);
        $startedAt = now()->subMinute();
        $completedAt = now()->addHour();

        $manualQueue = UnitInQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $manualUnit->id,
            'amount' => 10,
            'gold_paid' => 100,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ]);
        $kingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => $capitalCityUnit->name,
                'amount' => 25,
                'secondary_status' => CapitalCityQueueStatus::RECRUITING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::RECRUITING,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ]);
        $capitalCityQueue = $kingdomManagement->getCapitalCityUnitQueue();
        $kingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => $capitalCityUnit->name,
                'amount' => 50,
                'secondary_status' => CapitalCityQueueStatus::FINISHED,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::FINISHED,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ]);

        $result = (new KingdomTransformer(new KingdomResourceHourlyProductionTransformer))->transform($kingdom->refresh());

        $this->assertCount(2, $result['unit_queue']);
        $this->assertSame($manualQueue->id, $result['unit_queue'][0]['id']);
        $this->assertFalse((bool) $result['unit_queue'][0]['is_capital_city_managed']);
        $this->assertSame($capitalCityQueue->id, $result['unit_queue'][1]['id']);
        $this->assertSame($character->id, $result['unit_queue'][1]['character_id']);
        $this->assertSame($kingdom->id, $result['unit_queue'][1]['kingdom_id']);
        $this->assertSame($capitalCityUnit->id, $result['unit_queue'][1]['game_unit_id']);
        $this->assertSame(25, $result['unit_queue'][1]['amount']);
        $this->assertSame(0, $result['unit_queue'][1]['gold_paid']);
        $this->assertSame($startedAt->toDateTimeString(), $result['unit_queue'][1]['started_at']->toDateTimeString());
        $this->assertSame($completedAt->toDateTimeString(), $result['unit_queue'][1]['completed_at']->toDateTimeString());
        $this->assertTrue($result['unit_queue'][1]['is_capital_city_managed']);
        $this->assertSame($capitalCityQueue->id, $result['unit_queue'][1]['capital_city_unit_queue_id']);
    }
}
