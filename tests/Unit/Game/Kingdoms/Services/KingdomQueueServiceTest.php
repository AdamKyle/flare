<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\BuildingExpansionQueue;
use App\Game\Kingdoms\Service\KingdomQueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;

class KingdomQueueServiceTest extends TestCase
{
    use CreateGameBuilding, RefreshDatabase;

    public function testFetchKingdomQueuesSkipsMissingBuildingQueueAndLogsWarningContext(): void
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

    public function testFetchKingdomQueuesRendersExistingBuildingWithNullBuildingExpansionAsFirstExpansion(): void
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
