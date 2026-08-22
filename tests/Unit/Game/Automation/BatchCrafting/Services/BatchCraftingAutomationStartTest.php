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
use App\Game\Automation\BatchCrafting\Services\Setup\BatchCraftingSetupResolver;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Values\AutomationType;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Values\SkillTypeValue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateEvent as CreateGameEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateScheduledEvent;

class BatchCraftingAutomationStartTest extends TestCase
{
    use CreateCharacterAutomation, CreateGameEvent, CreateGameMap, CreateGameSkill, CreateGlobalEventGoal, CreateItem, CreateScheduledEvent, RefreshDatabase;

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
            'craft_mode', 'specific_crafting_type', 'specific_item_id', 'craft_amount', 'output_destination',
            'craft_specific_count', 'scheduled_for', 'processing_started_at', 'gold_spent_total', 'gold_gained_total',
            'gold_dust_spent_total', 'shards_spent_total', 'copper_coins_spent_total', 'disenchanted_count', 'used_count', 'chart_points',
        ], array_keys($batchCrafting->progress));
        $this->assertSame(0, $batchCrafting->progress['craft_specific_count']);
        $this->assertNull($batchCrafting->progress['processing_started_at']);
        $this->assertSame(0, $batchCrafting->progress['gold_spent_total']);
        $this->assertSame(0, $batchCrafting->progress['gold_gained_total']);
        $this->assertSame([], $batchCrafting->progress['chart_points']);
        $this->assertNull($batchCrafting->selected_items);
        $this->assertNull($batchCrafting->selected_oils);
    }

    public function test_start_schedules_exactly_one_job_one_minute_out_matching_the_persisted_scheduled_for(): void
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
        $scheduledFor = Carbon::parse($batchCrafting->progress['scheduled_for']);
        $this->assertEqualsWithDelta(now()->addMinute()->timestamp, $scheduledFor->timestamp, 5);
        Queue::assertPushed(BatchCraftingJob::class, 1);
        Queue::assertPushed(BatchCraftingJob::class, function (BatchCraftingJob $job) use ($result, $scheduledFor) {
            return $job->batchCraftingId === $result['batch_crafting_id']
                && $job->queue === BatchCraftingJob::QUEUE
                && $job->connection === BatchCraftingJob::CONNECTION
                && ! is_null($job->delay)
                && $job->delay->timestamp === $scheduledFor->timestamp;
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

    public function test_start_broadcasts_a_scheduled_status_snapshot_with_no_chart_point(): void
    {
        Event::fake([BatchCraftingStatusUpdated::class]);
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'output_destination' => 'crafted_items_set'],
        ];

        $this->service->start($this->character, $validated);

        Event::assertDispatched(function (BatchCraftingStatusUpdated $event) {
            $payload = $event->broadcastWith();

            return $payload['status']['is_scheduled'] === true
                && $payload['status']['batch']['chart_points'] === []
                && $payload['chart_point'] === null;
        });
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

    public function test_preview_returns_the_craft_set_preview_payload_for_set_mode(): void
    {
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->assignSkill($armourCrafting, 10, false)
            ->assignSkill($ringCrafting, 10, false)
            ->assignSkill($spellCrafting, 10, false)
            ->getCharacter();
        $this->character->update(['gold' => 100000, 'inventory_max' => 30]);
        $setPositions = [
            'body' => $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'leggings' => $this->createItem(['name' => 'Set leggings', 'type' => 'leggings', 'crafting_type' => 'armour', 'default_position' => 'leggings', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'sleeves' => $this->createItem(['name' => 'Set sleeves', 'type' => 'sleeves', 'crafting_type' => 'armour', 'default_position' => 'sleeves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'gloves' => $this->createItem(['name' => 'Set gloves', 'type' => 'gloves', 'crafting_type' => 'armour', 'default_position' => 'gloves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'feet' => $this->createItem(['name' => 'Set feet', 'type' => 'feet', 'crafting_type' => 'armour', 'default_position' => 'feet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'helmet' => $this->createItem(['name' => 'Set helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_0' => $this->createItem(['name' => 'Set Ring 0', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_1' => $this->createItem(['name' => 'Set Ring 1', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-damage' => $this->createItem(['name' => 'Set Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'default_position' => 'spell-damage', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-healing' => $this->createItem(['name' => 'Set Healing Spell', 'type' => 'spell-healing', 'crafting_type' => 'spell', 'default_position' => 'spell-healing', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
        ];
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'craft_set', 'set_positions' => $setPositions, 'output_destination' => null],
        ];

        $result = $this->service->preview($this->character, $validated);

        $this->assertSame(200, $result['status']);
        $this->assertSame(10, $result['included_position_count']);
        $this->assertEmpty($result['blockers']);
    }

    public function test_preview_returns_an_error_when_no_preview_is_available_for_the_mode(): void
    {
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_enchant_mode' => 'experience'],
        ];

        $result = $this->service->preview($this->character, $validated);

        $this->assertSame(422, $result['status']);
    }

    public function test_start_creates_a_running_craft_set_batch_with_a_resolved_queue(): void
    {
        Queue::fake();
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->assignSkill($armourCrafting, 10, false)
            ->assignSkill($ringCrafting, 10, false)
            ->assignSkill($spellCrafting, 10, false)
            ->getCharacter();
        $this->character->update(['gold' => 100000, 'inventory_max' => 30]);
        $setPositions = [
            'body' => $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'leggings' => $this->createItem(['name' => 'Set leggings', 'type' => 'leggings', 'crafting_type' => 'armour', 'default_position' => 'leggings', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'sleeves' => $this->createItem(['name' => 'Set sleeves', 'type' => 'sleeves', 'crafting_type' => 'armour', 'default_position' => 'sleeves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'gloves' => $this->createItem(['name' => 'Set gloves', 'type' => 'gloves', 'crafting_type' => 'armour', 'default_position' => 'gloves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'feet' => $this->createItem(['name' => 'Set feet', 'type' => 'feet', 'crafting_type' => 'armour', 'default_position' => 'feet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'helmet' => $this->createItem(['name' => 'Set helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_0' => $this->createItem(['name' => 'Set Ring 0', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_1' => $this->createItem(['name' => 'Set Ring 1', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-damage' => $this->createItem(['name' => 'Set Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'default_position' => 'spell-damage', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-healing' => $this->createItem(['name' => 'Set Healing Spell', 'type' => 'spell-healing', 'crafting_type' => 'spell', 'default_position' => 'spell-healing', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
        ];
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'craft_set', 'set_positions' => $setPositions, 'output_destination' => null],
        ];

        $result = $this->service->start($this->character, $validated);

        $this->assertSame(200, $result['status']);
        $batchCrafting = BatchCrafting::find($result['batch_crafting_id']);
        $this->assertCount(10, $batchCrafting->progress['set_queue']);
        $this->assertSame(0, $batchCrafting->progress['set_index']);
    }

    public function test_start_creates_a_running_experience_batch_with_empty_kept_best_bookkeeping_for_a_keep_best_disposition(): void
    {
        Queue::fake();
        $this->createItem(['name' => 'Experience Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_mode' => 'experience'],
        ];

        $result = $this->service->start($this->character, $validated);

        $this->assertSame(200, $result['status']);
        $batchCrafting = BatchCrafting::find($result['batch_crafting_id']);
        $this->assertSame([], $batchCrafting->progress['kept_best']);
        $this->assertSame(0, $batchCrafting->progress['cycle_position']);
    }

    public function test_start_creates_a_running_event_batch_with_the_authoritative_persisted_goal_id(): void
    {
        Queue::fake();
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);
        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);
        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->assignSkill($this->weaponCrafting, 10, false)->getCharacter();
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'event'],
        ];

        $result = $this->service->start($this->character, $validated);

        $this->assertSame(200, $result['status']);
        $batchCrafting = BatchCrafting::find($result['batch_crafting_id']);
        $this->assertSame($goal->id, $batchCrafting->progress['event_goal_id']);
        $this->assertSame(0, $batchCrafting->progress['event_cycle_position']);
        $this->assertNull($batchCrafting->progress['current_item_id']);
    }

    public function test_start_returns_a_blocker_for_event_when_no_eligible_craft_event_exists(): void
    {
        $validated = [
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'event'],
        ];

        $result = $this->service->start($this->character, $validated);

        $this->assertSame(422, $result['status']);
        $this->assertSame(0, BatchCrafting::where('character_id', $this->character->id)->count());
    }

    public function test_setup_resolver_throws_clearly_when_no_setup_service_is_registered_for_the_type(): void
    {
        $resolver = new BatchCraftingSetupResolver([]);

        $this->expectException(InvalidArgumentException::class);

        $resolver->resolve(BatchCraftingType::CRAFT);
    }
}
