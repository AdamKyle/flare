<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Game\Kingdoms\Jobs\CapitalCityUnitRequestCancellationMovement;
use App\Game\Kingdoms\Service\CapitalCityUnitManagement;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use ReflectionProperty;
use Tests\TestCase;

class CapitalCityUnitRequestCancellationMovementTest extends TestCase
{
    use RefreshDatabase;

    public function testDelayedRedispatchPassesAllConstructorArgumentsAndUsesLongRunningQueue(): void
    {
        Queue::fake();

        $capitalCityCancellationQueueId = 11;
        $characterId = 33;
        $dataForCancellation = [
            'unit_ids' => [44, 55],
        ];

        $capitalCityQueue = CapitalCityUnitQueue::factory()->create([
            'character_id' => $characterId,
            'kingdom_id' => 1,
            'requested_kingdom' => 1,
            'unit_request_data' => [],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);
        $capitalCityQueueId = $capitalCityQueue->id;

        $job = new CapitalCityUnitRequestCancellationMovement(
            $capitalCityCancellationQueueId,
            $capitalCityQueueId,
            $characterId,
            $dataForCancellation,
        );

        $job->handle(
            $this->createMock(CapitalCityUnitManagement::class),
            $this->createMock(UnitService::class),
        );

        Queue::assertPushed(CapitalCityUnitRequestCancellationMovement::class, function (CapitalCityUnitRequestCancellationMovement $job) use (
            $capitalCityCancellationQueueId,
            $capitalCityQueueId,
            $characterId,
            $dataForCancellation,
        ) {
            $capitalCityCancellationQueueIdProperty = new ReflectionProperty($job, 'capitalCityCancellationQueueId');
            $capitalCityQueueIdProperty = new ReflectionProperty($job, 'capitalCityQueueId');
            $characterIdProperty = new ReflectionProperty($job, 'characterId');
            $dataForCancellationProperty = new ReflectionProperty($job, 'dataForCancellation');

            $capitalCityCancellationQueueIdProperty->setAccessible(true);
            $capitalCityQueueIdProperty->setAccessible(true);
            $characterIdProperty->setAccessible(true);
            $dataForCancellationProperty->setAccessible(true);

            return $capitalCityCancellationQueueIdProperty->getValue($job) === $capitalCityCancellationQueueId &&
                $capitalCityQueueIdProperty->getValue($job) === $capitalCityQueueId &&
                $characterIdProperty->getValue($job) === $characterId &&
                $dataForCancellationProperty->getValue($job) === $dataForCancellation &&
                $job->connection === 'long_running' &&
                $job->queue === 'default_long';
        });
    }
}
