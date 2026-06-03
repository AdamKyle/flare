<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Game\Kingdoms\Jobs\CapitalCityUnitRequestCancellationMovement;
use App\Game\Kingdoms\Service\CapitalCityUnitManagement;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use ReflectionProperty;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CapitalCityUnitRequestCancellationMovementTest extends TestCase
{
    use RefreshDatabase;

    public function testMissingUnitQueueRejectionMarksCancellationAndUnitRequestRejected(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom([
            'max_stone' => 2000,
            'max_wood' => 2000,
            'max_clay' => 2000,
            'max_iron' => 2000,
            'current_stone' => 2000,
            'current_wood' => 2000,
            'current_clay' => 2000,
            'current_iron' => 2000,
            'current_population' => 2000,
            'max_population' => 2000,
            'x_position' => 16,
            'y_position' => 16,
            'current_morale' => .50,
            'max_morale' => 1.0,
            'last_walked' => now()->subWeeks(6),
        ])->assignUnits(['name' => 'Spearmen'], 1);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $unit = $kingdom->units()->first()->gameUnit;
        $kingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [[
                'name' => $unit->name,
                'amount' => 1,
                'secondary_status' => CapitalCityQueueStatus::RECRUITING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
        $capitalCityQueue = $kingdomManagement->getCapitalCityUnitQueue();
        $kingdomManagement->assignCapitalCityUnitCancellation([
            'unit_id' => $unit->id,
            'kingdom_id' => $kingdom->id,
            'character_id' => $character->id,
            'capital_city_unit_queue_id' => $capitalCityQueue->id,
            'status' => CapitalCityQueueStatus::REQUESTING,
            'travel_time_completed_at' => now(),
            'request_kingdom_id' => $kingdom->id,
        ]);
        $cancellation = $kingdomManagement->getCapitalCityUnitCancellation();

        $capitalCityUnitManagement = $this->getMockBuilder(CapitalCityUnitManagement::class)
            ->disableOriginalConstructor()
            ->addMethods(['possiblyCreateKingdomLog'])
            ->getMock();

        $capitalCityUnitManagement->expects($this->once())
            ->method('possiblyCreateKingdomLog');

        (new CapitalCityUnitRequestCancellationMovement(
            $cancellation->id,
            $capitalCityQueue->id,
            $character->id,
            ['unit_ids' => [$unit->id]],
        ))->handle(
            $capitalCityUnitManagement,
            $this->createMock(UnitService::class),
        );

        $this->assertSame(CapitalCityQueueStatus::CANCELLATION_REJECTED, $cancellation->refresh()->status);
        $this->assertSame(
            CapitalCityQueueStatus::CANCELLATION_REJECTED,
            $capitalCityQueue->refresh()->unit_request_data[0]['secondary_status'],
        );
    }

    public function testDelayedRedispatchPassesAllConstructorArgumentsAndUsesLongRunningQueue(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom();
        $characterId = $kingdomManagement->getCharacter()->id;
        $dataForCancellation = [
            'unit_ids' => [44, 55],
        ];

        $kingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $characterId,
            'kingdom_id' => $kingdomManagement->getKingdom()->id,
            'requested_kingdom' => $kingdomManagement->getKingdom()->id,
            'unit_request_data' => [],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);
        $capitalCityQueue = $kingdomManagement->getCapitalCityUnitQueue();
        $capitalCityQueueId = $capitalCityQueue->id;
        $capitalCityCancellationQueueId = 11;

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
