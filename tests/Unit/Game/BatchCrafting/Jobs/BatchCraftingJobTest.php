<?php

namespace Tests\Unit\Game\BatchCrafting\Jobs;

use App\Flare\Models\BatchCrafting;
use App\Game\BatchCrafting\Jobs\BatchCraftingJob;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;

class BatchCraftingJobTest extends TestCase
{
    use CreateBatchCrafting, CreateCharacter, CreateUser, RefreshDatabase;

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
