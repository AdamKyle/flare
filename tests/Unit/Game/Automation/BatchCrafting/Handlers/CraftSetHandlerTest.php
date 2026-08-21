<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Handlers\CraftSetHandler;
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
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;

class CraftSetHandlerTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, CreateInventorySets, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?CraftSetHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->assignSkill($armourCrafting, 10, false)
            ->assignSkill($ringCrafting, 10, false)
            ->assignSkill($spellCrafting, 10, false)
            ->getCharacter();

        $this->character->update(['gold' => 100000, 'inventory_max' => 30]);

        $this->handler = resolve(CraftSetHandler::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->handler = null;
    }

    public function test_handle_crafts_the_current_queue_position_and_advances_the_index(): void
    {
        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $ringItem = $this->createItem(['name' => 'Set Ring', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $queue = [
            ['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name],
            ['position' => 'ring_0', 'item_id' => $ringItem->id, 'crafting_type' => 'ring', 'item_name' => $ringItem->name],
        ];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'craft_set', 'set_queue' => $queue, 'set_index' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $progress = $batchCrafting->refresh()->progress;

        $this->assertTrue($result->didCraft());
        $this->assertNull($result->endReason());
        $this->assertSame(1, $progress['set_index']);
    }

    public function test_handle_ends_craft_set_complete_on_the_final_successful_position(): void
    {
        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $queue = [['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'craft_set', 'set_queue' => $queue, 'set_index' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $progress = $batchCrafting->refresh()->progress;

        $this->assertSame(BatchCraftingEndReason::CRAFT_SET_COMPLETE, $result->endReason());
        $this->assertSame(1, $progress['set_index']);
    }

    public function test_handle_returns_craft_set_complete_when_index_already_past_the_queue(): void
    {
        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $queue = [['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'craft_set', 'set_queue' => $queue, 'set_index' => 1],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::CRAFT_SET_COMPLETE, $result->endReason());
    }

    public function test_handle_does_not_advance_the_index_when_the_item_is_no_longer_craftable(): void
    {
        $queue = [['position' => 'body', 'item_id' => 999999, 'crafting_type' => 'armour', 'item_name' => 'Gone']];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'craft_set', 'set_queue' => $queue, 'set_index' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $progress = $batchCrafting->refresh()->progress;

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT, $result->endReason());
        $this->assertSame(0, $progress['set_index']);
    }

    public function test_handle_keep_disposition_to_crafted_items_set_places_item_in_set(): void
    {
        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $queue = [['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'set_queue' => $queue, 'set_index' => 0, 'output_destination' => 'crafted_items_set'],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);

        $this->assertTrue($result->didCraft());
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $queue[0]['item_id'])->count());
    }

    public function test_handle_keep_disposition_to_a_selected_inventory_set_places_item_in_the_set(): void
    {
        $targetSet = $this->createInventorySet(['character_id' => $this->character->id, 'is_equipped' => false]);
        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $queue = [['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'set_queue' => $queue, 'set_index' => 0, 'output_destination' => 'inventory_set', 'output_set_id' => $targetSet->id],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $placedSlotCount = $targetSet->slots()->where('item_id', $queue[0]['item_id'])->count();

        $this->assertTrue($result->didCraft());
        $this->assertSame(1, $placedSlotCount);
    }

    public function test_handle_returns_craft_set_full_when_selected_inventory_set_is_no_longer_valid(): void
    {
        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $queue = [['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'craft_set', 'set_queue' => $queue, 'set_index' => 0, 'output_destination' => 'inventory_set', 'output_set_id' => 999999],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::CRAFT_SET_FULL, $result->endReason());
    }

    public function test_handle_does_not_advance_the_index_when_the_craft_roll_fails(): void
    {
        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $queue = [
            ['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name],
            ['position' => 'leggings', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name],
        ];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'craft_set', 'set_queue' => $queue, 'set_index' => 0],
        ]);

        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1000);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );

        $result = resolve(CraftSetHandler::class)->handle($batchCrafting, $this->character);

        $progress = $batchCrafting->refresh()->progress;

        $this->assertFalse($result->didCraft());
        $this->assertNull($result->endReason());
        $this->assertSame(0, $progress['set_index']);
    }

    public function test_handle_persists_current_item_information(): void
    {
        $bodyItem = $this->createItem(['name' => 'Set Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $queue = [['position' => 'body', 'item_id' => $bodyItem->id, 'crafting_type' => 'armour', 'item_name' => $bodyItem->name]];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'craft_set', 'set_queue' => $queue, 'set_index' => 0],
        ]);

        $this->handler->handle($batchCrafting, $this->character);

        $progress = $batchCrafting->refresh()->progress;

        $this->assertSame($queue[0]['item_id'], $progress['current_item_id']);
        $this->assertSame('Set Body', $progress['current_item_name']);
    }
}
