<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\BuildingExpansionQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Game\Kingdoms\Service\KingdomQueueService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameUnit;

class KingdomQueueServiceTest extends TestCase
{
    use CreateGameBuilding, CreateGameUnit, RefreshDatabase;

    public function test_fetch_kingdom_queues_includes_active_capital_city_building_queue(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
        ])->getKingdom();
        $targetKingdomManagement = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding();
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();
        $targetKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => $building->level,
                'to_level' => $building->level + 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $capitalCityBuildingQueue = $targetKingdomManagement->getCapitalCityBuildingQueue();

        $result = resolve(KingdomQueueService::class)->fetchKingdomQueues($targetKingdom->refresh());

        $this->assertNotEmpty($result['building_queues']);
        $this->assertSame($building->name, $result['building_queues'][0]['name']);
        $this->assertSame($capitalCityBuildingQueue->id, $result['building_queues'][0]['capital_city_queue_id']);
        $this->assertTrue($result['building_queues'][0]['is_capital_city_managed']);
        $this->assertSame('Capital City Upgrade', $result['building_queues'][0]['type']);
        $this->assertArrayHasKey('time_remaining', $result['building_queues'][0]);
    }

    public function test_fetch_kingdom_queues_includes_active_capital_city_unit_queue(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
        ])->getKingdom();
        $targetKingdomManagement = $characterFactory->kingdomManagement()->assignKingdom();
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = $this->createGameUnit(['name' => 'Spearmen']);
        $targetKingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => $unit->name,
                'amount' => 10,
                'secondary_status' => CapitalCityQueueStatus::RECRUITING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::RECRUITING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $capitalCityUnitQueue = $targetKingdomManagement->getCapitalCityUnitQueue();

        $result = resolve(KingdomQueueService::class)->fetchKingdomQueues($targetKingdom->refresh());

        $this->assertNotEmpty($result['unit_recruitment_queues']);
        $this->assertSame($unit->name, $result['unit_recruitment_queues'][0]['name']);
        $this->assertSame($capitalCityUnitQueue->id, $result['unit_recruitment_queues'][0]['capital_city_queue_id']);
        $this->assertTrue($result['unit_recruitment_queues'][0]['is_capital_city_managed']);
        $this->assertSame('Capital City Recruitment', $result['unit_recruitment_queues'][0]['type']);
        $this->assertArrayHasKey('time_remaining', $result['unit_recruitment_queues'][0]);
    }

    public function test_fetch_kingdom_queues_keeps_ready_active_capital_city_building_queues(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
        ])->getKingdom();
        $targetKingdomManagement = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding();
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();
        $targetKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::TRAVELING,
                'from_level' => $building->level,
                'to_level' => $building->level + 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $travelingQueue = $targetKingdomManagement->getCapitalCityBuildingQueue();
        $targetKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::PROCESSING,
                'from_level' => $building->level,
                'to_level' => $building->level + 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $processingQueue = $targetKingdomManagement->getCapitalCityBuildingQueue();
        $targetKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::REQUESTING,
                'from_level' => $building->level,
                'to_level' => $building->level + 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::REQUESTING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $requestingQueue = $targetKingdomManagement->getCapitalCityBuildingQueue();
        $targetKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => $building->level,
                'to_level' => $building->level + 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $buildingQueue = $targetKingdomManagement->getCapitalCityBuildingQueue();
        $targetKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'repair',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::REPAIRING,
                'from_level' => $building->level,
                'to_level' => $building->level,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::REPAIRING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $repairingQueue = $targetKingdomManagement->getCapitalCityBuildingQueue();

        $result = resolve(KingdomQueueService::class)->fetchKingdomQueues($targetKingdom->refresh());

        $this->assertCount(5, $result['building_queues']);
        $this->assertNotNull(CapitalCityBuildingQueue::find($travelingQueue->id));
        $this->assertNotNull(CapitalCityBuildingQueue::find($processingQueue->id));
        $this->assertNotNull(CapitalCityBuildingQueue::find($requestingQueue->id));
        $this->assertNotNull(CapitalCityBuildingQueue::find($buildingQueue->id));
        $this->assertNotNull(CapitalCityBuildingQueue::find($repairingQueue->id));
    }

    public function test_fetch_kingdom_queues_keeps_ready_active_capital_city_unit_queues(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
        ])->getKingdom();
        $targetKingdomManagement = $characterFactory->kingdomManagement()->assignKingdom();
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = $this->createGameUnit(['name' => 'Spearmen']);
        $targetKingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => $unit->name,
                'amount' => 10,
                'secondary_status' => CapitalCityQueueStatus::TRAVELING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $travelingQueue = $targetKingdomManagement->getCapitalCityUnitQueue();
        $targetKingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => $unit->name,
                'amount' => 10,
                'secondary_status' => CapitalCityQueueStatus::PROCESSING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $processingQueue = $targetKingdomManagement->getCapitalCityUnitQueue();
        $targetKingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => $unit->name,
                'amount' => 10,
                'secondary_status' => CapitalCityQueueStatus::REQUESTING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::REQUESTING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $requestingQueue = $targetKingdomManagement->getCapitalCityUnitQueue();
        $targetKingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => $unit->name,
                'amount' => 10,
                'secondary_status' => CapitalCityQueueStatus::RECRUITING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::RECRUITING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $recruitingQueue = $targetKingdomManagement->getCapitalCityUnitQueue();

        $result = resolve(KingdomQueueService::class)->fetchKingdomQueues($targetKingdom->refresh());

        $this->assertCount(4, $result['unit_recruitment_queues']);
        $this->assertNotNull(CapitalCityUnitQueue::find($travelingQueue->id));
        $this->assertNotNull(CapitalCityUnitQueue::find($processingQueue->id));
        $this->assertNotNull(CapitalCityUnitQueue::find($requestingQueue->id));
        $this->assertNotNull(CapitalCityUnitQueue::find($recruitingQueue->id));
    }

    public function test_fetch_kingdom_queues_does_not_render_terminal_capital_city_queues(): void
    {
        Log::spy();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
        ])->getKingdom();
        $targetKingdomManagement = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding();
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();
        $targetKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::FINISHED,
                'from_level' => $building->level,
                'to_level' => $building->level + 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::FINISHED,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $buildingQueue = $targetKingdomManagement->getCapitalCityBuildingQueue();

        resolve(KingdomQueueService::class)->fetchKingdomQueues($targetKingdom->refresh());

        Log::shouldNotHaveReceived('warning');
        $this->assertNotNull(CapitalCityBuildingQueue::find($buildingQueue->id));
    }

    public function test_fetch_kingdom_queues_skips_missing_building_queue_and_logs_warning_context(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();
        $completedAt = now()->addHour();
        $queue = BuildingExpansionQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => 999999,
            'completed_at' => $completedAt,
            'started_at' => now(),
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($queue, $character, $kingdom) {
                return $message === 'Skipping invalid building expansion queue.'
                    && $context['building_expansion_queue_id'] === $queue->id
                    && $context['building_id'] === 999999
                    && $context['kingdom_id'] === $kingdom->id
                    && $context['character_id'] === $character->id
                    && $context['completed_at'] instanceof Carbon
                    && $context['reason'] === 'missing_building';
            });

        $result = resolve(KingdomQueueService::class)->fetchKingdomQueues($kingdom);

        $this->assertSame([], $result['building_expansion_queues']);
    }

    public function test_fetch_kingdom_queues_renders_existing_building_with_null_building_expansion_as_first_expansion(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->create([
            'game_building_id' => $this->createGameBuilding([
                'name' => 'Lumber Yard',
                'is_resource_building' => true,
                'increase_wood_amount' => 100,
            ])->id,
            'kingdom_id' => $kingdom->id,
            'level' => 1,
            'max_defence' => 100,
            'max_durability' => 100,
            'current_durability' => 100,
            'current_defence' => 100,
        ]);
        $queue = BuildingExpansionQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'completed_at' => now()->addHour(),
            'started_at' => now(),
        ]);

        $result = resolve(KingdomQueueService::class)->fetchKingdomQueues($kingdom->refresh());

        $this->assertSame('Lumber Yard', $result['building_expansion_queues'][0]['name']);
        $this->assertSame($queue->id, $result['building_expansion_queues'][0]['id']);
        $this->assertSame(0, $result['building_expansion_queues'][0]['from_slot']);
        $this->assertSame(1, $result['building_expansion_queues'][0]['to_slot']);
    }
}
