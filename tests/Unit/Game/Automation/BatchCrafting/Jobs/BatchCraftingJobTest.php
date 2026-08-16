<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Events\BatchCraftingStatusUpdated;
use App\Game\Automation\BatchCrafting\Jobs\BatchCraftingJob;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class BatchCraftingJobTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, CreateItem, RefreshDatabase;

    private ?GameSkill $weaponCrafting;

    private ?Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($this->weaponCrafting, 10, false)->getCharacter();
        $this->character->update(['gold' => 1000, 'inventory_max' => 30]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->weaponCrafting = null;
        $this->character = null;
    }

    public function test_handle_does_nothing_when_the_batch_crafting_row_no_longer_exists(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);

        BatchCraftingJob::dispatch(999999);

        Event::assertNotDispatched(BatchCraftingStatusUpdated::class);
    }

    public function test_handle_does_nothing_when_batch_crafting_is_already_completed(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'completed_at' => now(),
            'ended_reason' => BatchCraftingEndReason::CANCELLED->value,
            'progress' => [],
        ]);

        BatchCraftingJob::dispatch($batchCrafting->id);

        Event::assertNotDispatched(BatchCraftingStatusUpdated::class);
    }

    public function test_handle_processes_exactly_one_operation_per_queued_job(): void
    {
        $item = $this->createItem(['name' => 'Job Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 3, 'craft_specific_count' => 0],
        ]);

        BatchCraftingJob::dispatch($batchCrafting->id);

        $batchCrafting->refresh();
        $this->assertFalse($batchCrafting->isRunning());
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $batchCrafting->ended_reason);
        $this->assertSame(3, $batchCrafting->crafted_count);
        $this->assertSame(3, $batchCrafting->progress['craft_specific_count']);
    }

    public function test_handle_completes_the_batch_when_the_requested_amount_is_reached(): void
    {
        $item = $this->createItem(['name' => 'Job Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0],
        ]);

        BatchCraftingJob::dispatch($batchCrafting->id);

        $batchCrafting->refresh();

        $this->assertFalse($batchCrafting->isRunning());
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $batchCrafting->ended_reason);
        $this->assertSame(1, $batchCrafting->progress['craft_specific_count']);
    }

    public function test_handle_broadcasts_status_updated_for_the_batchs_owning_user(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);

        $item = $this->createItem(['name' => 'Job Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0],
        ]);

        BatchCraftingJob::dispatch($batchCrafting->id);

        Event::assertDispatched(function (BatchCraftingStatusUpdated $event) {
            return $event->broadcastWith()['user_id'] === $this->character->user_id;
        });
    }

    public function test_job_uses_the_long_running_connection_and_batch_crafting_queue(): void
    {
        $job = new BatchCraftingJob(1);

        $this->assertSame('long_running', $job->connection);
        $this->assertSame('batch_crafting', $job->queue);
    }
}
