<?php

namespace Tests\Unit\Game\BatchCrafting\Jobs;

use App\Flare\Models\BatchCrafting;
use App\Game\BatchCrafting\Jobs\BatchCraftingJob;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;

class BatchCraftingJobTest extends TestCase
{
    use CreateBatchCrafting, CreateCharacter, CreateUser, MockeryPHPUnitIntegration, RefreshDatabase;

    public function testFiniteBatchProcessesRepeatedlyInTheSameJobUntilCompletion(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);
        $service = Mockery::mock(BatchCraftingService::class);
        $service->shouldReceive('markProcessing')->once();
        $service->shouldReceive('processOneOperation')->twice()->andReturnUsing(function (BatchCrafting $batch) {
            if ($batch->crafted_count === 0) {
                $batch->update(['crafted_count' => 1]);

                return $batch->refresh();
            }

            $batch->update(['status' => 'completed', 'completed_at' => now()]);

            return $batch->refresh();
        });
        $service->shouldReceive('isContinuousFiniteMode')->twice()->andReturn(true, false);

        (new BatchCraftingJob($batchCrafting->id))->handle($service);

        $this->assertSame('completed', $batchCrafting->refresh()->status);
    }

    public function testFiniteBatchDoesNotStoreANormalNextAttemptAt(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'progress' => ['craft_mode' => 'specific_item', 'next_attempt_at' => null]]);
        $service = Mockery::mock(BatchCraftingService::class);
        $service->shouldReceive('markProcessing')->once();
        $service->shouldReceive('processOneOperation')->once()->andReturnUsing(function (BatchCrafting $batch) {
            $batch->update(['status' => 'completed', 'completed_at' => now()]);

            return $batch->refresh();
        });
        $service->shouldReceive('isContinuousFiniteMode')->once()->andReturn(false);

        (new BatchCraftingJob($batchCrafting->id))->handle($service);

        $this->assertNull($batchCrafting->refresh()->progress['next_attempt_at'] ?? null);
    }

    public function testFiniteBatchStopsWhenCancellationIsObservedBetweenOperations(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);
        $service = Mockery::mock(BatchCraftingService::class);
        $service->shouldReceive('markProcessing')->once();
        $service->shouldReceive('processOneOperation')->once()->andReturnUsing(function (BatchCrafting $batch) {
            $batch->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            return $batch->refresh();
        });
        $service->shouldReceive('isContinuousFiniteMode')->once()->andReturn(false);

        (new BatchCraftingJob($batchCrafting->id))->handle($service);

        $this->assertSame('cancelled', $batchCrafting->refresh()->status);
    }

    public function testFiniteBatchStopsWhenServiceChangesItToTerminalState(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);
        $service = Mockery::mock(BatchCraftingService::class);
        $service->shouldReceive('markProcessing')->once();
        $service->shouldReceive('processOneOperation')->once()->andReturnUsing(function (BatchCrafting $batch) {
            $batch->update(['status' => 'completed', 'completed_at' => now()]);

            return $batch->refresh();
        });
        $service->shouldReceive('isContinuousFiniteMode')->once()->andReturn(false);

        (new BatchCraftingJob($batchCrafting->id))->handle($service);

        $this->assertSame('completed', $batchCrafting->refresh()->status);
    }

    public function testOpenEndedBatchProcessesOneTick(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'progress' => ['craft_mode' => 'experience']]);
        $service = Mockery::mock(BatchCraftingService::class);
        $service->shouldReceive('markProcessing')->once();
        $service->shouldReceive('processOneOperation')->once()->andReturn($batchCrafting);
        $service->shouldReceive('isContinuousFiniteMode')->once()->andReturn(false);

        (new BatchCraftingJob($batchCrafting->id))->handle($service);

        $this->assertTrue($batchCrafting->refresh()->isRunning());
    }

    public function testMissingBatchCraftingRowExitsWithoutProcessing(): void
    {
        $service = Mockery::mock(BatchCraftingService::class);
        $service->shouldNotReceive('markProcessing');
        $service->shouldNotReceive('processOneOperation');

        (new BatchCraftingJob(999999))->handle($service);

        $this->assertNull(BatchCrafting::find(999999));
    }

    public function testPreviouslyCompletedBatchExitsWithoutProcessing(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'status' => 'completed', 'completed_at' => now()]);
        $service = Mockery::mock(BatchCraftingService::class);
        $service->shouldNotReceive('markProcessing');
        $service->shouldNotReceive('processOneOperation');

        (new BatchCraftingJob($batchCrafting->id))->handle($service);

        $this->assertSame('completed', $batchCrafting->refresh()->status);
    }

    public function testPreviouslyCancelledBatchExitsWithoutProcessing(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'status' => 'cancelled', 'cancelled_at' => now()]);
        $service = Mockery::mock(BatchCraftingService::class);
        $service->shouldNotReceive('markProcessing');
        $service->shouldNotReceive('processOneOperation');

        (new BatchCraftingJob($batchCrafting->id))->handle($service);

        $this->assertSame('cancelled', $batchCrafting->refresh()->status);
    }

    public function testStopAtEightHours(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'ends_at' => now()->subSecond()]);

        (new BatchCraftingJob($batchCrafting->id))->handle(resolve(BatchCraftingService::class));

        $this->assertSame(BatchCraftingEndReason::COMPLETED_DURATION->value, $batchCrafting->refresh()->ended_reason);
    }

    public function testJobDoesNotContinueProcessingACancelledBatch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        resolve(BatchCraftingService::class)->cancel($character);

        (new BatchCraftingJob($batchCrafting->id))->handle(resolve(BatchCraftingService::class));

        $this->assertSame(BatchCraftingEndReason::CANCELLED->value, $batchCrafting->refresh()->ended_reason);
        $this->assertSame(0, $batchCrafting->refresh()->crafted_count);
    }

    public function testJobIsConfiguredForTheDedicatedBatchCraftingConnectionAndQueue(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        $job = new BatchCraftingJob($batchCrafting->id);

        $this->assertSame('long_running', $job->connection);
        $this->assertSame('batch_crafting', $job->queue);
    }

    public function testJobNeverTargetsTheDefaultLongQueue(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        $job = new BatchCraftingJob($batchCrafting->id);

        $this->assertNotSame('default_long', $job->queue);
    }
}
