<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySet;
use App\Game\Automation\BatchCrafting\Services\CraftSetPreviewService;
use App\Game\Character\Values\CharacterClass;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;

class CraftSetPreviewServiceTest extends TestCase
{
    use CreateClass, CreateGameSkill, CreateInventorySets, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?CraftSetPreviewService $service;

    private array $requiredPositions;

    private ?GameSkill $armourCrafting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->assignSkill($this->armourCrafting, 10, false)
            ->assignSkill($weaponCrafting, 10, false)
            ->assignSkill($ringCrafting, 10, false)
            ->assignSkill($spellCrafting, 10, false)
            ->getCharacter();
        $this->character->update(['gold' => 100000, 'inventory_max' => 30]);
        $this->service = resolve(CraftSetPreviewService::class);

        $this->requiredPositions = [
            'body' => $this->createItem(['name' => 'Preview body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'leggings' => $this->createItem(['name' => 'Preview leggings', 'type' => 'leggings', 'crafting_type' => 'armour', 'default_position' => 'leggings', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'sleeves' => $this->createItem(['name' => 'Preview sleeves', 'type' => 'sleeves', 'crafting_type' => 'armour', 'default_position' => 'sleeves', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'gloves' => $this->createItem(['name' => 'Preview gloves', 'type' => 'gloves', 'crafting_type' => 'armour', 'default_position' => 'gloves', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'feet' => $this->createItem(['name' => 'Preview feet', 'type' => 'feet', 'crafting_type' => 'armour', 'default_position' => 'feet', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'helmet' => $this->createItem(['name' => 'Preview helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_0' => $this->createItem(['name' => 'Preview Ring 0', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'ring_1' => $this->createItem(['name' => 'Preview Ring 1', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-damage' => $this->createItem(['name' => 'Preview Damage', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'default_position' => 'spell-damage', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'spell-healing' => $this->createItem(['name' => 'Preview Healing', 'type' => 'spell-healing', 'crafting_type' => 'spell', 'default_position' => 'spell-healing', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->service = null;
        $this->armourCrafting = null;
    }

    public function test_build_reports_the_total_cost_and_included_position_count(): void
    {
        $progress = ['set_positions' => $this->requiredPositions, 'output_destination' => null];

        $result = $this->service->build($this->character, $progress, 'destroy');

        $this->assertSame(10, $result['included_position_count']);
        $this->assertSame(100, $result['total_cost']);
        $this->assertTrue($result['can_afford']);
        $this->assertEmpty($result['blockers']);
    }

    public function test_build_flags_insufficient_gold(): void
    {
        $this->character->update(['gold' => 0]);
        $progress = ['set_positions' => $this->requiredPositions, 'output_destination' => null];

        $result = $this->service->build($this->character, $progress, 'destroy');

        $this->assertFalse($result['can_afford']);
        $this->assertNotEmpty($result['blockers']);
    }

    public function test_build_reports_crafted_items_set_destination_capacity(): void
    {
        $progress = ['set_positions' => $this->requiredPositions, 'output_destination' => 'crafted_items_set'];

        $result = $this->service->build($this->character, $progress, 'keep');

        $this->assertNotNull($result['destination_capacity']);
        $this->assertTrue($result['can_fit']);
    }

    public function test_build_flags_a_selected_inventory_set_that_is_equipped(): void
    {
        $set = $this->createInventorySet(['character_id' => $this->character->id, 'is_equipped' => true]);
        $progress = ['set_positions' => $this->requiredPositions, 'output_destination' => 'inventory_set', 'output_set_id' => $set->id];

        $result = $this->service->build($this->character, $progress, 'keep');

        $this->assertNotEmpty($result['blockers']);
    }

    public function test_build_flags_the_special_batch_crafting_set_as_an_invalid_normal_target(): void
    {
        $set = $this->createInventorySet(['character_id' => $this->character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'is_equipped' => false]);
        $progress = ['set_positions' => $this->requiredPositions, 'output_destination' => 'inventory_set', 'output_set_id' => $set->id];

        $result = $this->service->build($this->character, $progress, 'keep');

        $this->assertNotEmpty($result['blockers']);
    }

    public function test_build_reports_inventory_destination_capacity(): void
    {
        $this->character->update(['inventory_max' => 50]);
        $progress = ['set_positions' => $this->requiredPositions, 'output_destination' => 'inventory'];

        $result = $this->service->build($this->character, $progress, 'keep');

        $this->assertNotNull($result['destination_capacity']);
        $this->assertSame(50, $result['destination_capacity']['max']);
    }

    public function test_build_reports_a_valid_inventory_set_destination_capacity(): void
    {
        $set = $this->createInventorySet(['character_id' => $this->character->id, 'is_equipped' => false, 'max_slots' => 20]);
        $progress = ['set_positions' => $this->requiredPositions, 'output_destination' => 'inventory_set', 'output_set_id' => $set->id];

        $result = $this->service->build($this->character, $progress, 'keep');

        $this->assertNotNull($result['destination_capacity']);
        $this->assertSame(20, $result['destination_capacity']['max']);
        $this->assertTrue($result['can_fit']);
        $this->assertEmpty($result['blockers']);
    }

    public function test_build_flags_a_missing_destination_set_id(): void
    {
        $progress = ['set_positions' => $this->requiredPositions, 'output_destination' => 'inventory_set'];

        $result = $this->service->build($this->character, $progress, 'keep');

        $this->assertNotEmpty($result['blockers']);
        $this->assertNull($result['destination_capacity']);
    }

    public function test_build_no_hands_only_requires_capacity_for_ten_and_fits_with_exactly_ten_remaining(): void
    {
        $set = $this->createInventorySet(['character_id' => $this->character->id, 'is_equipped' => false, 'max_slots' => 10]);
        $progress = ['set_positions' => $this->requiredPositions, 'output_destination' => 'inventory_set', 'output_set_id' => $set->id];

        $result = $this->service->build($this->character, $progress, 'keep');

        $this->assertSame(10, $result['included_position_count']);
        $this->assertTrue($result['can_fit']);
        $this->assertEmpty($result['blockers']);
    }

    public function test_build_no_hands_cannot_fit_with_only_nine_remaining_slots(): void
    {
        $set = $this->createInventorySet(['character_id' => $this->character->id, 'is_equipped' => false, 'max_slots' => 9]);
        $progress = ['set_positions' => $this->requiredPositions, 'output_destination' => 'inventory_set', 'output_set_id' => $set->id];

        $result = $this->service->build($this->character, $progress, 'keep');

        $this->assertFalse($result['can_fit']);
        $this->assertNotEmpty($result['blockers']);
    }

    public function test_build_one_hand_requires_capacity_for_eleven(): void
    {
        $leftHand = $this->createItem(['name' => 'Preview Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $set = $this->createInventorySet(['character_id' => $this->character->id, 'is_equipped' => false, 'max_slots' => 11]);
        $progress = ['set_positions' => [...$this->requiredPositions, 'left_hand' => $leftHand->id], 'output_destination' => 'inventory_set', 'output_set_id' => $set->id];

        $result = $this->service->build($this->character, $progress, 'keep');

        $this->assertSame(11, $result['included_position_count']);
        $this->assertTrue($result['can_fit']);
    }

    public function test_build_both_hands_require_capacity_for_twelve(): void
    {
        $leftHand = $this->createItem(['name' => 'Preview Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $rightHand = $this->createItem(['name' => 'Preview Shield', 'type' => 'shield', 'crafting_type' => 'armour', 'default_position' => 'shield', 'can_craft' => true, 'cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $set = $this->createInventorySet(['character_id' => $this->character->id, 'is_equipped' => false, 'max_slots' => 12]);
        $progress = ['set_positions' => [...$this->requiredPositions, 'left_hand' => $leftHand->id, 'right_hand' => $rightHand->id], 'output_destination' => 'inventory_set', 'output_set_id' => $set->id];

        $result = $this->service->build($this->character, $progress, 'keep');

        $this->assertSame(12, $result['included_position_count']);
        $this->assertTrue($result['can_fit']);
        $this->assertEmpty($result['blockers']);
    }

    public function test_build_total_cost_reflects_the_characters_own_crafting_cost_reduction(): void
    {
        $merchantCharacter = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::MERCHANT->value]))
            ->assignSkill($this->armourCrafting, 10, false)
            ->givePlayerLocation()
            ->getCharacter();
        $merchantCharacter->update(['gold' => 100000, 'inventory_max' => 30]);
        $bodyItem = $this->createItem(['name' => 'Discounted Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 100, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $result = $this->service->build($merchantCharacter, ['set_positions' => ['body' => $bodyItem->id], 'output_destination' => null], 'destroy');

        $this->assertSame(70, $result['total_cost']);
    }
}
