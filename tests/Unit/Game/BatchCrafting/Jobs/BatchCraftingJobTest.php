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

    public function test_finite_batch_processes_repeatedly_in_the_same_job_until_completion(): void
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

        $this->app->instance(BatchCraftingService::class, $service);

        BatchCraftingJob::dispatch($batchCrafting->id);

        $this->assertSame('completed', $batchCrafting->refresh()->status);
    }

    public function test_finite_batch_does_not_store_a_normal_next_attempt_at(): void
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

        $this->app->instance(BatchCraftingService::class, $service);

        BatchCraftingJob::dispatch($batchCrafting->id);

        $this->assertNull($batchCrafting->refresh()->progress['next_attempt_at'] ?? null);
    }

    public function test_finite_batch_stops_when_cancellation_is_observed_between_operations(): void
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

        $this->app->instance(BatchCraftingService::class, $service);

        BatchCraftingJob::dispatch($batchCrafting->id);

        $this->assertSame('cancelled', $batchCrafting->refresh()->status);
    }

    public function test_finite_batch_stops_when_service_changes_it_to_terminal_state(): void
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

        $this->app->instance(BatchCraftingService::class, $service);

        BatchCraftingJob::dispatch($batchCrafting->id);

        $this->assertSame('completed', $batchCrafting->refresh()->status);
    }

    public function test_open_ended_batch_processes_one_tick(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'progress' => ['craft_mode' => 'experience']]);
        $service = Mockery::mock(BatchCraftingService::class);
        $service->shouldReceive('markProcessing')->once();
        $service->shouldReceive('processOneOperation')->once()->andReturn($batchCrafting);
        $service->shouldReceive('isContinuousFiniteMode')->once()->andReturn(false);

        $this->app->instance(BatchCraftingService::class, $service);

        BatchCraftingJob::dispatch($batchCrafting->id);

        $this->assertTrue($batchCrafting->refresh()->isRunning());
    }

    public function test_missing_batch_crafting_row_exits_without_processing(): void
    {
        $service = Mockery::mock(BatchCraftingService::class);
        $service->shouldNotReceive('markProcessing');
        $service->shouldNotReceive('processOneOperation');

        $this->app->instance(BatchCraftingService::class, $service);

        BatchCraftingJob::dispatch(999999);

        $this->assertNull(BatchCrafting::find(999999));
    }

    public function test_previously_completed_batch_exits_without_processing(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'status' => 'completed', 'completed_at' => now()]);
        $service = Mockery::mock(BatchCraftingService::class);
        $service->shouldNotReceive('markProcessing');
        $service->shouldNotReceive('processOneOperation');

        $this->app->instance(BatchCraftingService::class, $service);

        BatchCraftingJob::dispatch($batchCrafting->id);

        $this->assertSame('completed', $batchCrafting->refresh()->status);
    }

    public function test_previously_cancelled_batch_exits_without_processing(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'status' => 'cancelled', 'cancelled_at' => now()]);
        $service = Mockery::mock(BatchCraftingService::class);
        $service->shouldNotReceive('markProcessing');
        $service->shouldNotReceive('processOneOperation');

        $this->app->instance(BatchCraftingService::class, $service);

        BatchCraftingJob::dispatch($batchCrafting->id);

        $this->assertSame('cancelled', $batchCrafting->refresh()->status);
    }

    public function test_stop_at_eight_hours(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id, 'ends_at' => now()->subSecond()]);

        BatchCraftingJob::dispatch($batchCrafting->id);

        $this->assertSame(BatchCraftingEndReason::COMPLETED_DURATION->value, $batchCrafting->refresh()->ended_reason);
    }

    public function test_job_does_not_continue_processing_a_cancelled_batch(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        resolve(BatchCraftingService::class)->cancel($character);

        BatchCraftingJob::dispatch($batchCrafting->id);

        $this->assertSame(BatchCraftingEndReason::CANCELLED->value, $batchCrafting->refresh()->ended_reason);
        $this->assertSame(0, $batchCrafting->refresh()->crafted_count);
    }

    public function test_job_is_configured_for_the_dedicated_batch_crafting_connection_and_queue(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        $job = new BatchCraftingJob($batchCrafting->id);

        $this->assertSame('long_running', $job->connection);
        $this->assertSame('batch_crafting', $job->queue);
    }

    public function test_job_never_targets_the_default_long_queue(): void
    {
        $user = $this->createUser();
        $character = $this->createCharacter(['user_id' => $user->id, 'inventory_max' => 10, 'gold' => 100]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $user->id]);

        $job = new BatchCraftingJob($batchCrafting->id);

        $this->assertNotSame('default_long', $job->queue);
    }
}
