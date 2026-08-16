<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Services\BatchCraftingAutomationService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class BatchCraftingAutomationStatusTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, CreateItem, RefreshDatabase;

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

    public function test_status_reports_no_batch_and_show_info_true_before_any_activity(): void
    {
        $result = $this->service->status($this->character);

        $this->assertFalse($result['active']);
        $this->assertNull($result['batch']);
        $this->assertFalse($result['is_visible']);
        $this->assertTrue($result['show_info']);
    }

    public function test_status_reports_a_running_batch_as_cancellable_and_not_dismissible(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'craft_specific_count' => 2],
        ]);

        $result = $this->service->status($this->character);

        $this->assertTrue($result['is_running']);
        $this->assertTrue($result['can_cancel']);
        $this->assertFalse($result['can_dismiss']);
        $this->assertSame(2, $result['batch']['completed_amount']);
        $this->assertSame(3, $result['batch']['remaining_amount']);
    }

    public function test_status_reports_a_completed_visible_batch_as_dismissible(): void
    {
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => 1, 'craft_amount' => 5, 'craft_specific_count' => 5],
            'completed_at' => now(),
            'ended_reason' => BatchCraftingEndReason::AMOUNT_REACHED->value,
        ]);

        $result = $this->service->status($this->character);

        $this->assertFalse($result['is_running']);
        $this->assertTrue($result['can_dismiss']);
        $this->assertFalse($result['can_cancel']);
    }

    public function test_status_reports_a_cancelled_visible_batch(): void
    {
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => 1, 'craft_amount' => 5, 'craft_specific_count' => 2],
            'completed_at' => now(),
            'cancelled_at' => now(),
            'ended_reason' => BatchCraftingEndReason::CANCELLED->value,
        ]);

        $result = $this->service->status($this->character);

        $this->assertFalse($result['is_running']);
        $this->assertTrue($result['can_dismiss']);
        $this->assertSame(BatchCraftingEndReason::CANCELLED->value, $result['batch']['ended_reason']);
    }

    public function test_status_does_not_report_a_dismissed_batch(): void
    {
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => 1, 'craft_amount' => 5, 'craft_specific_count' => 5],
            'completed_at' => now(),
            'ended_reason' => BatchCraftingEndReason::AMOUNT_REACHED->value,
            'panel_dismissed_at' => now(),
        ]);

        $result = $this->service->status($this->character);

        $this->assertFalse($result['is_visible']);
        $this->assertNull($result['batch']);
    }

    public function test_status_does_not_report_an_info_row_as_visible(): void
    {
        $this->service->acknowledgeInfo($this->character);

        $result = $this->service->status($this->character);

        $this->assertFalse($result['is_visible']);
        $this->assertNull($result['batch']);
    }

    public function test_status_reports_show_info_true_before_acknowledgement(): void
    {
        $result = $this->service->status($this->character);

        $this->assertTrue($result['show_info']);
    }

    public function test_status_reports_show_info_false_after_acknowledging(): void
    {
        $this->service->acknowledgeInfo($this->character);

        $result = $this->service->status($this->character);

        $this->assertFalse($result['show_info']);
    }

    public function test_status_response_contains_no_backend_label_or_timer_fields(): void
    {
        $item = $this->createItem(['name' => 'Service Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'craft_specific_count' => 2],
        ]);

        $result = $this->service->status($this->character);

        $this->assertArrayNotHasKey('completed', $result);
        foreach (['batch_label', 'human_mode_label', 'output_destination_label', 'elapsed_human', 'remaining_human', 'progress_percent', 'gold_spent_total', 'gold_gained_total', 'stop_reason'] as $forbiddenKey) {
            $this->assertArrayNotHasKey($forbiddenKey, $result['batch']);
        }
    }
}
