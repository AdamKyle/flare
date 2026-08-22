<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Status;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\EnchantingBatchMode;
use App\Game\Automation\BatchCrafting\Services\Status\EnchantEventStatusSection;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateScheduledEvent;

class EnchantEventStatusSectionTest extends TestCase
{
    use CreateBatchCrafting, CreateEvent, CreateGlobalEventGoal, CreateScheduledEvent, RefreshDatabase;

    private ?EnchantEventStatusSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->section = resolve(EnchantEventStatusSection::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->section = null;
    }

    public function test_supports_only_enchant_event(): void
    {
        $this->assertTrue($this->section->supports(BatchCraftingType::ENCHANT, 'event'));
        $this->assertFalse($this->section->supports(BatchCraftingType::CRAFT_AND_ENCHANT, 'event'));
    }

    public function test_build_reports_null_goal_facts_when_no_goal_was_ever_persisted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'enchant_mode' => EnchantingBatchMode::EVENT->value,
                'event_goal_id' => null,
                'event_enchant_phase' => 'enchant_event_inventory',
                'current_item_id' => null,
                'current_item_name' => null,
                'current_prefix_name' => null,
                'current_suffix_name' => null,
                'enchanting_xp_gained' => 0,
                'crafting_xp_gained' => 0,
                'fallback_cycle_position' => 0,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertNull($result['event_progress']['goal_id']);
        $this->assertNull($result['event_progress']['max_enchants']);
    }

    public function test_build_preserves_final_goal_facts_by_persisted_goal_id(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
            'ends_at' => now()->addHour(),
        ]);
        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_enchants' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'enchant_mode' => EnchantingBatchMode::EVENT->value,
                'event_goal_id' => $goal->id,
                'event_enchant_phase' => 'craft_fallback_set',
                'current_item_id' => null,
                'current_item_name' => 'Fallback Item',
                'current_prefix_name' => null,
                'current_suffix_name' => null,
                'enchanting_xp_gained' => 40,
                'crafting_xp_gained' => 20,
                'fallback_cycle_position' => 0,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertSame($goal->id, $result['event_progress']['goal_id']);
        $this->assertSame(100, $result['event_progress']['max_enchants']);
        $this->assertSame('craft_fallback_set', $result['event_progress']['phase']);
        $this->assertSame(40, $result['event_progress']['enchanting_xp_gained']);
        $this->assertSame(20, $result['event_progress']['crafting_xp_gained']);
        $this->assertSame('Fallback Item', $result['current_item_name']);
        $this->assertSame(23, $result['event_progress']['actions_per_minute']);
    }
}
