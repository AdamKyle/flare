<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Admin\Events\BatchCraftingMonitoringUpdated;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Events\BatchCraftingStatusUpdated;
use App\Game\Automation\BatchCrafting\Jobs\BatchCraftingJob;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingAutomationService;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Values\AutomationType;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class BatchCraftingAutomationStartTest extends TestCase
{
    use CreateCharacterAutomation, CreateGameSkill, CreateItem, RefreshDatabase;

    private ?GameSkill $weaponCrafting;

    private ?Character $character;

    private ?BatchCraftingAutomationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($this->weaponCrafting, 10, false)->getCharacter();
        $this->character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->service = resolve(BatchCraftingAutomationService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->weaponCrafting = null;
        $this->character = null;
        $this->service = null;
    }

    public function test_start_creates_a_running_batch_with_started_at_and_ends_at_eight_hours_later(): void
    {
        Queue::fake();
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];

        $result = $this->service->start($this->character, $validated);

        $this->assertSame(200, $result['status']);
        $batchCrafting = BatchCrafting::find($result['batch_crafting_id']);
        $this->assertNotNull($batchCrafting->started_at);
        $this->assertEqualsWithDelta(now()->addHours(8)->timestamp, $batchCrafting->ends_at->timestamp, 5);
        $this->assertSame(BatchCraftingStatus::RUNNING->value, $batchCrafting->status);
    }

    public function test_start_persists_only_phase_one_progress_fields(): void
    {
        Queue::fake();
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];

        $result = $this->service->start($this->character, $validated);

        $batchCrafting = BatchCrafting::find($result['batch_crafting_id']);
        $this->assertEqualsCanonicalizing([
            'craft_mode', 'specific_crafting_type', 'specific_item_id', 'craft_amount', 'output_destination', 'craft_specific_count',
        ], array_keys($batchCrafting->progress));
        $this->assertSame(0, $batchCrafting->progress['craft_specific_count']);
        $this->assertNull($batchCrafting->selected_items);
        $this->assertNull($batchCrafting->selected_oils);
    }

    public function test_start_dispatches_the_first_job_with_a_one_minute_delay_on_the_correct_queue_and_connection(): void
    {
        Queue::fake();
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];

        $result = $this->service->start($this->character, $validated);

        Queue::assertPushed(BatchCraftingJob::class, function (BatchCraftingJob $job) use ($result) {
            return $job->batchCraftingId === $result['batch_crafting_id']
                && $job->queue === BatchCraftingJob::QUEUE
                && $job->connection === BatchCraftingJob::CONNECTION
                && ! is_null($job->delay);
        });
    }

    public function test_start_fires_status_and_monitoring_events(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class, BatchCraftingMonitoringUpdated::class, AutomationLogUpdate::class]);
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];

        $this->service->start($this->character, $validated);

        Event::assertDispatched(BatchCraftingStatusUpdated::class);
        Event::assertDispatched(BatchCraftingMonitoringUpdated::class);
        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function test_start_sends_the_scheduled_server_message(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];

        $this->service->start($this->character, $validated);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'Batch Crafting is scheduled and will begin in one minute.';
        });
    }

    public function test_start_returns_an_error_when_a_batch_is_already_running(): void
    {
        Queue::fake();
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $firstStart = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];
        $this->service->start($this->character, $firstStart);

        $result = $this->service->start($this->character, $firstStart);

        $this->assertSame(422, $result['status']);
    }

    public function test_start_returns_an_error_when_faction_loyalty_automation_is_running(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];

        $result = $this->service->start($this->character, $validated);

        $this->assertSame(422, $result['status']);
    }

    public function test_start_returns_an_error_when_character_is_dead(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->character->update(['is_dead' => true]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];

        $result = $this->service->start($this->character, $validated);

        $this->assertSame(422, $result['status']);
    }

    public function test_start_returns_an_error_when_a_blocker_prevents_starting(): void
    {
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => 999999, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];

        $result = $this->service->start($this->character, $validated);

        $this->assertSame(422, $result['status']);
    }
}
