<?php

namespace Tests\Console\Kingdoms;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingCancellation;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitCancellation;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\UnitInQueue;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class RepairKingdomDataTest extends TestCase
{
    use RefreshDatabase;

    public function testDryRunReportsInvalidKingdomDataWithoutMutatingIt(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], [], true, false)->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_stone' => -5,
                'current_iron' => -10,
            ])
            ->assignBuilding([
                'max_level' => 3,
            ], [
                'level' => 5,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'to_level' => 6,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'to_level' => 6,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $this->assertEquals(0, Artisan::call('kingdoms:repair-data'));

        $output = Artisan::output();
        $kingdom = $kingdom->refresh();
        $building = $building->refresh();

        $this->assertStringContainsString('Dry-run mode: no data was changed.', $output);
        $this->assertStringContainsString('over_level_buildings: 1', $output);
        $this->assertStringContainsString('negative_resources: 1', $output);
        $this->assertStringContainsString('duplicate_manual_building_queues: 1', $output);
        $this->assertSame(-5, $kingdom->current_stone);
        $this->assertSame(-10, $kingdom->current_iron);
        $this->assertSame(5, $building->level);
        $this->assertSame(2, BuildingInQueue::where('building_id', $building->id)->count());
    }

    public function testApplyRepairsInvalidKingdomDataOnly(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], [], true, false)->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_stone' => -5,
                'current_iron' => -10,
            ])
            ->assignBuilding([
                'max_level' => 3,
            ], [
                'level' => 5,
            ])
            ->assignUnits([], KingdomMaxValue::MAX_UNIT - 5);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $gameUnit = $kingdom->units()->first()->gameUnit;

        BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'to_level' => 6,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'to_level' => 6,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $overMaxUnitQueue = UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => 10,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $invalidUnitQueue = UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => 0,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $this->assertEquals(0, Artisan::call('kingdoms:repair-data', [
            '--apply' => true,
        ]));

        $kingdom = $kingdom->refresh();
        $building = $building->refresh();

        $this->assertSame(0, $kingdom->current_stone);
        $this->assertSame(0, $kingdom->current_iron);
        $this->assertSame(3, $building->level);
        $this->assertSame(0, BuildingInQueue::where('building_id', $building->id)->count());
        $this->assertNull(UnitInQueue::find($overMaxUnitQueue->id));
        $this->assertNull(UnitInQueue::find($invalidUnitQueue->id));
    }

    public function testApplyRepairsDuplicateValidUnitQueuesReduceSafely(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], [], true, false)->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignUnits([], KingdomMaxValue::MAX_UNIT - 5);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $gameUnit = $kingdom->units()->first()->gameUnit;
        $firstUnitQueue = UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => 3,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $secondUnitQueue = UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => 3,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $this->assertEquals(0, Artisan::call('kingdoms:repair-data', [
            '--apply' => true,
        ]));

        $firstUnitQueue = $firstUnitQueue->refresh();
        $secondUnitQueue = $secondUnitQueue->refresh();

        $this->assertSame(3, $firstUnitQueue->amount);
        $this->assertSame(2, $secondUnitQueue->amount);
    }

    public function testApplyDoesNotChangeValidKingdomData(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], [], true, false)->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_stone' => 500,
                'current_iron' => 500,
            ])
            ->assignBuilding([
                'max_level' => 10,
            ], [
                'level' => 5,
            ])
            ->assignUnits([], 10);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $gameUnit = $kingdom->units()->first()->gameUnit;
        $buildingQueue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'to_level' => 6,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $unitQueue = UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => 10,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $this->assertEquals(0, Artisan::call('kingdoms:repair-data', [
            '--apply' => true,
        ]));

        $output = Artisan::output();
        $kingdom = $kingdom->refresh();
        $building = $building->refresh();
        $buildingQueue = $buildingQueue->refresh();
        $unitQueue = $unitQueue->refresh();

        $this->assertStringContainsString('total_repairs: 0', $output);
        $this->assertSame(500, $kingdom->current_stone);
        $this->assertSame(500, $kingdom->current_iron);
        $this->assertSame(5, $building->level);
        $this->assertSame(6, $buildingQueue->to_level);
        $this->assertSame(10, $unitQueue->amount);
    }

    public function testApplyRepairsStaleCapitalCityRowsAndStuckCancellations(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter([], [], true, false)->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignKingdom()
            ->assignBuilding([
                'max_level' => 2,
            ], [
                'level' => 2,
            ])
            ->assignUnits([], KingdomMaxValue::MAX_UNIT);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $requestingKingdom = $character->kingdoms()->where('id', '!=', $kingdom->id)->first();
        $building = $kingdom->buildings()->first();
        $gameUnit = $kingdom->units()->first()->gameUnit;
        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $requestingKingdom->id,
            'status' => CapitalCityQueueStatus::BUILDING,
            'building_request_data' => [[
                'building_id' => $building->id,
                'name' => $building->name,
                'from_level' => 2,
                'to_level' => 3,
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
            ]],
            'messages' => [],
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $capitalCityUnitQueue = CapitalCityUnitQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $requestingKingdom->id,
            'status' => CapitalCityQueueStatus::RECRUITING,
            'unit_request_data' => [[
                'name' => $gameUnit->name,
                'amount' => 1,
                'secondary_status' => CapitalCityQueueStatus::RECRUITING,
            ]],
            'messages' => [],
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $buildingCancellation = CapitalCityBuildingCancellation::create([
            'building_id' => $building->id,
            'kingdom_id' => $kingdom->id,
            'request_kingdom_id' => $requestingKingdom->id,
            'character_id' => $character->id,
            'capital_city_building_queue_id' => $capitalCityBuildingQueue->id,
            'status' => CapitalCityQueueStatus::PROCESSING,
            'travel_time_completed_at' => now(),
        ]);
        $unitCancellation = CapitalCityUnitCancellation::create([
            'unit_id' => $gameUnit->id,
            'kingdom_id' => $kingdom->id,
            'request_kingdom_id' => $requestingKingdom->id,
            'character_id' => $character->id,
            'capital_city_unit_queue_id' => $capitalCityUnitQueue->id,
            'status' => CapitalCityQueueStatus::PROCESSING,
            'travel_time_completed_at' => now(),
        ]);

        $this->assertEquals(0, Artisan::call('kingdoms:repair-data', [
            '--apply' => true,
        ]));

        $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();
        $capitalCityUnitQueue = $capitalCityUnitQueue->refresh();
        $buildingCancellation = $buildingCancellation->refresh();
        $unitCancellation = $unitCancellation->refresh();

        $this->assertSame(CapitalCityQueueStatus::REJECTED, $capitalCityBuildingQueue->building_request_data[0]['secondary_status']);
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $capitalCityBuildingQueue->status);
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $capitalCityUnitQueue->unit_request_data[0]['secondary_status']);
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $capitalCityUnitQueue->status);
        $this->assertSame(CapitalCityQueueStatus::CANCELLATION_REJECTED, $buildingCancellation->status);
        $this->assertSame(CapitalCityQueueStatus::CANCELLATION_REJECTED, $unitCancellation->status);
    }
}
