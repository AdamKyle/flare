<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Services\CraftAndEnchantSetPreviewService;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CraftAndEnchantSetPreviewServiceTest extends TestCase
{
    use CreateGameSkill, CreateInventorySets, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character;

    private ?CraftAndEnchantSetPreviewService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->assignSkill($armourCrafting, 10, false)
            ->getCharacter();

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $this->character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => 10]);

        $this->character->update(['gold' => 10000, 'inventory_max' => 30]);
        $this->character = $this->character->refresh();

        $this->service = resolve(CraftAndEnchantSetPreviewService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->service = null;
    }

    public function test_build_combines_crafting_and_enchanting_costs_per_position(): void
    {
        $bodyId = $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id;
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 30]);

        $progress = [
            'set_positions' => ['body' => $bodyId],
            'enchantments' => ['body' => ['prefix_id' => $prefix->id, 'suffix_id' => null]],
            'output_destination' => 'inventory',
        ];

        $result = $this->service->build($this->character, $progress, BatchCraftingDisposition::KEEP->value);

        $bodyFacts = collect($result['positions'])->firstWhere('position', 'body');
        $this->assertSame(20, $bodyFacts['crafting_cost']);
        $this->assertSame(30, $bodyFacts['enchanting_cost']);
        $this->assertSame(50, $bodyFacts['combined_cost']);
        $this->assertSame($prefix->id, $bodyFacts['prefix']['id']);
    }

    public function test_build_flags_insufficient_gold(): void
    {
        $this->character->update(['gold' => 0]);
        $bodyId = $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id;
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 30]);

        $progress = [
            'set_positions' => ['body' => $bodyId],
            'enchantments' => ['body' => ['prefix_id' => $prefix->id, 'suffix_id' => null]],
            'output_destination' => 'inventory',
        ];

        $result = $this->service->build($this->character->refresh(), $progress, BatchCraftingDisposition::KEEP->value);

        $this->assertContains('You do not have enough Gold to craft and enchant this set.', $result['blockers']);
    }

    public function test_build_returns_destination_capacity_for_keep(): void
    {
        $bodyId = $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id;
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 30]);

        $progress = [
            'set_positions' => ['body' => $bodyId],
            'enchantments' => ['body' => ['prefix_id' => $prefix->id, 'suffix_id' => null]],
            'output_destination' => 'inventory',
        ];

        $result = $this->service->build($this->character, $progress, BatchCraftingDisposition::KEEP->value);

        $this->assertNotNull($result['destination_capacity']);
        $this->assertSame(30, $result['destination_capacity']['max']);
    }

    public function test_build_flags_insufficient_destination_space(): void
    {
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $batchCraftingSet->update(['max_slots' => 0]);

        $bodyId = $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id;
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 30]);

        $progress = [
            'set_positions' => ['body' => $bodyId],
            'enchantments' => ['body' => ['prefix_id' => $prefix->id, 'suffix_id' => null]],
            'output_destination' => 'crafted_items_set',
        ];

        $result = $this->service->build($this->character, $progress, BatchCraftingDisposition::KEEP->value);

        $this->assertContains('The selected destination does not have enough remaining space.', $result['blockers']);
        $this->assertFalse($result['can_fit']);
    }

    public function test_build_creates_the_crafted_items_set_for_a_character_with_no_existing_special_set(): void
    {
        $this->assertNull(resolve(BatchCraftingSetService::class)->findBatchCraftingSet($this->character));

        $bodyId = $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id;
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 30]);

        $progress = [
            'set_positions' => ['body' => $bodyId],
            'enchantments' => ['body' => ['prefix_id' => $prefix->id, 'suffix_id' => null]],
            'output_destination' => 'crafted_items_set',
        ];

        $result = $this->service->build($this->character, $progress, BatchCraftingDisposition::KEEP->value);

        $this->assertNotNull($result['output_set_id']);
        $this->assertNotNull($result['destination_capacity']);
        $this->assertNotNull(resolve(BatchCraftingSetService::class)->findBatchCraftingSet($this->character));
    }

    public function test_build_blocks_an_inventory_set_destination_without_a_selected_set_id(): void
    {
        $bodyId = $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id;
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 30]);

        $progress = [
            'set_positions' => ['body' => $bodyId],
            'enchantments' => ['body' => ['prefix_id' => $prefix->id, 'suffix_id' => null]],
            'output_destination' => 'inventory_set',
        ];

        $result = $this->service->build($this->character, $progress, BatchCraftingDisposition::KEEP->value);

        $this->assertNull($result['destination_capacity']);
        $this->assertContains('The selected destination set must be an empty, unequipped Set you own.', $result['blockers']);
    }

    public function test_build_blocks_a_non_empty_normal_inventory_set(): void
    {
        $occupyingItem = $this->createItem(['name' => 'Already In Set']);
        $set = $this->createInventorySet(['character_id' => $this->character->id, 'is_equipped' => false, 'max_slots' => 15]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $occupyingItem->id]);

        $bodyId = $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id;
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 30]);

        $progress = [
            'set_positions' => ['body' => $bodyId],
            'enchantments' => ['body' => ['prefix_id' => $prefix->id, 'suffix_id' => null]],
            'output_destination' => 'inventory_set',
            'output_set_id' => $set->id,
        ];

        $result = $this->service->build($this->character, $progress, BatchCraftingDisposition::KEEP->value);

        $this->assertNull($result['destination_capacity']);
        $this->assertContains('The selected destination set must be an empty, unequipped Set you own.', $result['blockers']);
    }
}
