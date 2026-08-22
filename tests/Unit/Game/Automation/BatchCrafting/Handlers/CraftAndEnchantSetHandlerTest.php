<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Handlers\CraftAndEnchantSetHandler;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CraftAndEnchantSetHandlerTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character;

    private ?CraftAndEnchantSetHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($armourCrafting, 10, false)
            ->getCharacter();

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $this->character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => 10]);

        $this->character->update(['gold' => 100000, 'inventory_max' => 30]);
        $this->character = $this->character->refresh();

        $this->handler = resolve(CraftAndEnchantSetHandler::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->handler = null;
    }

    public function test_handle_crafting_phase_success_moves_to_enchanting_phase(): void
    {
        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 0]);

        $queue = [['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name, 'prefix_id' => $prefix->id, 'suffix_id' => null]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_enchant_mode' => 'set', 'set_queue' => $queue, 'set_index' => 0, 'set_phase' => 'crafting', 'current_item_id' => null, 'current_item_name' => null, 'current_position' => null, 'current_prefix_name' => null, 'current_suffix_name' => null],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertNull($result->endReason());
        $this->assertNull($result->actionStatus());
        $progress = $batchCrafting->refresh()->progress;
        $this->assertSame('enchanting', $progress['set_phase']);
        $this->assertSame(0, $progress['set_index']);
        $this->assertNotNull($progress['current_item_id']);
    }

    public function test_handle_enchanting_phase_success_applies_disposition_and_advances_index(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 0]);

        $queue = [['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name, 'prefix_id' => $prefix->id, 'suffix_id' => null]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_enchant_mode' => 'set', 'set_queue' => $queue, 'set_index' => 0, 'set_phase' => 'crafting', 'current_item_id' => null, 'current_item_name' => null, 'current_position' => null, 'current_prefix_name' => null, 'current_suffix_name' => null],
        ]);

        $handler = resolve(CraftAndEnchantSetHandler::class);
        $handler->handle($batchCrafting, $this->character);
        $result = $handler->handle($batchCrafting->refresh(), $this->character);

        $this->assertTrue($result->didCraft());
        $this->assertSame(BatchCraftingEndReason::CRAFT_SET_COMPLETE, $result->endReason());
        $progress = $batchCrafting->refresh()->progress;
        $this->assertSame(1, $progress['set_index']);
        $this->assertSame('crafting', $progress['set_phase']);
        $this->assertNull($progress['current_item_id']);
    }

    public function test_handle_enchanting_phase_failure_resets_to_crafting_phase_same_index(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('getDCCheck')->andReturn(1000);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );

        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 0]);

        $queue = [['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name, 'prefix_id' => $prefix->id, 'suffix_id' => null]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_enchant_mode' => 'set', 'set_queue' => $queue, 'set_index' => 0, 'set_phase' => 'crafting', 'current_item_id' => null, 'current_item_name' => null, 'current_position' => null, 'current_prefix_name' => null, 'current_suffix_name' => null],
        ]);

        $handler = resolve(CraftAndEnchantSetHandler::class);
        $handler->handle($batchCrafting, $this->character);
        $result = $handler->handle($batchCrafting->refresh(), $this->character);

        $this->assertFalse($result->didCraft());
        $this->assertNull($result->endReason());
        $progress = $batchCrafting->refresh()->progress;
        $this->assertSame(0, $progress['set_index']);
        $this->assertSame('crafting', $progress['set_phase']);
        $this->assertNull($progress['current_item_id']);
    }

    public function test_handle_returns_craft_set_complete_when_index_already_past_the_queue(): void
    {
        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $queue = [['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name, 'prefix_id' => null, 'suffix_id' => null]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_enchant_mode' => 'set', 'set_queue' => $queue, 'set_index' => 1, 'set_phase' => 'crafting', 'current_item_id' => null, 'current_item_name' => null, 'current_position' => null, 'current_prefix_name' => null, 'current_suffix_name' => null],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::CRAFT_SET_COMPLETE, $result->endReason());
    }

    public function test_handle_crafting_phase_returns_maxed_or_nothing_left_when_item_gone(): void
    {
        $queue = [['position' => 'body', 'item_id' => 999999, 'crafting_type' => 'armour', 'item_name' => 'Gone', 'prefix_id' => null, 'suffix_id' => null]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_enchant_mode' => 'set', 'set_queue' => $queue, 'set_index' => 0, 'set_phase' => 'crafting', 'current_item_id' => null, 'current_item_name' => null, 'current_position' => null, 'current_prefix_name' => null, 'current_suffix_name' => null],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT, $result->endReason());
    }

    public function test_handle_keep_disposition_places_final_enchanted_item_in_crafted_items_set(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 0]);

        $queue = [['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name, 'prefix_id' => $prefix->id, 'suffix_id' => null]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_enchant_mode' => 'set', 'set_queue' => $queue, 'set_index' => 0, 'set_phase' => 'crafting', 'output_destination' => 'crafted_items_set', 'output_set_id' => null, 'listing_price' => null, 'current_item_id' => null, 'current_item_name' => null, 'current_position' => null, 'current_prefix_name' => null, 'current_suffix_name' => null],
        ]);

        $handler = resolve(CraftAndEnchantSetHandler::class);
        $handler->handle($batchCrafting, $this->character);
        $result = $handler->handle($batchCrafting->refresh(), $this->character);

        $this->assertTrue($result->didCraft());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->count());
    }

    public function test_handle_crafting_phase_translates_a_failed_craft_roll(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('getDCCheck')->andReturn(1000);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );

        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $queue = [['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name, 'prefix_id' => null, 'suffix_id' => null]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_enchant_mode' => 'set', 'set_queue' => $queue, 'set_index' => 0, 'set_phase' => 'crafting', 'current_item_id' => null, 'current_item_name' => null, 'current_position' => null, 'current_prefix_name' => null, 'current_suffix_name' => null],
        ]);

        $handler = resolve(CraftAndEnchantSetHandler::class);
        $result = $handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertFalse($result->didCraft());
        $this->assertNull($result->endReason());
    }

    public function test_handle_enchanting_phase_resets_when_the_pending_item_no_longer_exists(): void
    {
        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 0]);

        $queue = [['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name, 'prefix_id' => $prefix->id, 'suffix_id' => null]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_enchant_mode' => 'set', 'set_queue' => $queue, 'set_index' => 0, 'set_phase' => 'crafting', 'current_item_id' => null, 'current_item_name' => null, 'current_position' => null, 'current_prefix_name' => null, 'current_suffix_name' => null],
        ]);

        $this->handler->handle($batchCrafting, $this->character);
        $progress = $batchCrafting->refresh()->progress;
        Item::find($progress['current_item_id'])->delete();

        $result = $this->handler->handle($batchCrafting->refresh(), $this->character);

        $this->assertFalse($result->didCraft());
        $this->assertNull($result->endReason());
        $progress = $batchCrafting->refresh()->progress;
        $this->assertSame('crafting', $progress['set_phase']);
        $this->assertNull($progress['current_item_id']);
    }

    public function test_handle_enchanting_phase_resets_when_the_requested_affix_no_longer_exists(): void
    {
        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $queue = [['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name, 'prefix_id' => 999999, 'suffix_id' => null]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_enchant_mode' => 'set', 'set_queue' => $queue, 'set_index' => 0, 'set_phase' => 'crafting', 'current_item_id' => null, 'current_item_name' => null, 'current_position' => null, 'current_prefix_name' => null, 'current_suffix_name' => null],
        ]);

        $this->handler->handle($batchCrafting, $this->character);
        $result = $this->handler->handle($batchCrafting->refresh(), $this->character);

        $this->assertFalse($result->didCraft());
        $this->assertNull($result->endReason());
        $progress = $batchCrafting->refresh()->progress;
        $this->assertSame('crafting', $progress['set_phase']);
        $this->assertNull($progress['current_item_id']);
    }

    public function test_handle_keep_ends_batch_crafting_set_full_when_the_set_is_full(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $batchCraftingSet->update(['max_slots' => 0]);

        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 0]);

        $queue = [['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name, 'prefix_id' => $prefix->id, 'suffix_id' => null]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_enchant_mode' => 'set', 'set_queue' => $queue, 'set_index' => 0, 'set_phase' => 'crafting', 'output_destination' => 'crafted_items_set', 'output_set_id' => null, 'listing_price' => null, 'current_item_id' => null, 'current_item_name' => null, 'current_position' => null, 'current_prefix_name' => null, 'current_suffix_name' => null],
        ]);

        $handler = resolve(CraftAndEnchantSetHandler::class);
        $handler->handle($batchCrafting, $this->character);
        $result = $handler->handle($batchCrafting->refresh(), $this->character);

        $this->assertNotNull($result->endReason());
    }

    public function test_handle_completes_a_non_final_position_and_continues_to_the_next(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $legsItem = $this->createItem(['name' => 'Set Legs', 'type' => 'legs', 'crafting_type' => 'armour', 'default_position' => 'legs', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 0]);

        $queue = [
            ['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name, 'prefix_id' => $prefix->id, 'suffix_id' => null],
            ['position' => 'legs', 'item_id' => $legsItem->id, 'crafting_type' => 'armour', 'item_name' => $legsItem->name, 'prefix_id' => $prefix->id, 'suffix_id' => null],
        ];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_enchant_mode' => 'set', 'set_queue' => $queue, 'set_index' => 0, 'set_phase' => 'crafting', 'current_item_id' => null, 'current_item_name' => null, 'current_position' => null, 'current_prefix_name' => null, 'current_suffix_name' => null],
        ]);

        $handler = resolve(CraftAndEnchantSetHandler::class);
        $handler->handle($batchCrafting, $this->character);
        $result = $handler->handle($batchCrafting->refresh(), $this->character);

        $this->assertTrue($result->didCraft());
        $this->assertNull($result->endReason());
        $progress = $batchCrafting->refresh()->progress;
        $this->assertSame(1, $progress['set_index']);
        $this->assertSame('crafting', $progress['set_phase']);
    }
}
