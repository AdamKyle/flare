<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Services\CraftAndEnchantSetPlanService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CraftAndEnchantSetPlanServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character;

    private ?CraftAndEnchantSetPlanService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->assignSkill($armourCrafting, 10, false)
            ->assignSkill($ringCrafting, 10, false)
            ->assignSkill($spellCrafting, 10, false)
            ->assignSkill($weaponCrafting, 10, false)
            ->getCharacter();

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $this->character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => 10]);
        $this->character = $this->character->refresh();

        $this->service = resolve(CraftAndEnchantSetPlanService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->service = null;
    }

    public function test_resolve_plan_returns_a_complete_queue_with_enchantments_when_every_position_is_valid(): void
    {
        $positions = [
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

        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 10]);

        $enchantments = [
            'body' => ['prefix_id' => $prefix->id, 'suffix_id' => null],
            'leggings' => ['prefix_id' => $prefix->id, 'suffix_id' => null],
            'sleeves' => ['prefix_id' => $prefix->id, 'suffix_id' => null],
            'gloves' => ['prefix_id' => $prefix->id, 'suffix_id' => null],
            'feet' => ['prefix_id' => $prefix->id, 'suffix_id' => null],
            'helmet' => ['prefix_id' => $prefix->id, 'suffix_id' => null],
            'ring_0' => ['prefix_id' => $prefix->id, 'suffix_id' => null],
            'ring_1' => ['prefix_id' => $prefix->id, 'suffix_id' => null],
            'spell-damage' => ['prefix_id' => $prefix->id, 'suffix_id' => null],
            'spell-healing' => ['prefix_id' => $prefix->id, 'suffix_id' => null],
        ];

        $result = $this->service->resolvePlan($this->character, $positions, $enchantments);

        $this->assertEmpty($result['blockers']);
        $this->assertCount(10, $result['queue']);
        $this->assertSame($prefix->id, $result['queue'][0]['prefix_id']);
        $this->assertNull($result['queue'][0]['suffix_id']);
    }

    public function test_resolve_plan_reports_a_blocker_when_an_included_position_has_no_enchantment(): void
    {
        $positions = ['body' => $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id];

        $result = $this->service->resolvePlan($this->character, $positions, []);

        $this->assertContains('The body position requires at least one Prefix or Suffix.', $result['blockers']);
    }

    public function test_resolve_plan_reports_a_blocker_for_an_invalid_affix_id(): void
    {
        $positions = ['body' => $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id];

        $result = $this->service->resolvePlan($this->character, $positions, ['body' => ['prefix_id' => 999999, 'suffix_id' => null]]);

        $this->assertContains('The selected enchantment for the body position is not currently valid.', $result['blockers']);
    }

    public function test_resolve_plan_reports_a_blocker_when_intelligence_is_too_low(): void
    {
        $this->character->update(['int' => 1]);
        $positions = ['body' => $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id];
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 100000, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 10]);

        $result = $this->service->resolvePlan($this->character->refresh(), $positions, ['body' => ['prefix_id' => $prefix->id, 'suffix_id' => null]]);

        $this->assertContains('The selected enchantment for the body position is not currently valid.', $result['blockers']);
    }

    public function test_resolve_plan_accepts_a_suffix_only_enchantment(): void
    {
        $positions = ['body' => $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id];
        $suffix = $this->createItemAffix(['type' => 'suffix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 10]);

        $result = $this->service->resolvePlan($this->character, $positions, ['body' => ['prefix_id' => null, 'suffix_id' => $suffix->id]]);

        $bodyEntry = collect($result['queue'])->firstWhere('position', 'body');
        $this->assertNotNull($bodyEntry);
        $this->assertSame($suffix->id, $bodyEntry['suffix_id']);
        $this->assertNull($bodyEntry['prefix_id']);
    }

    public function test_resolve_plan_reports_a_blocker_when_the_selected_items_type_does_not_match_the_position(): void
    {
        $ringItem = $this->createItem(['name' => 'Mismatched Ring', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $positions = ['body' => $ringItem->id];

        $result = $this->service->resolvePlan($this->character, $positions, []);

        $this->assertContains('The selected item for the body position is no longer craftable.', $result['blockers']);
    }

    public function test_resolve_plan_reports_a_blocker_for_an_invalid_suffix_id(): void
    {
        $positions = ['body' => $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id];
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 10]);

        $result = $this->service->resolvePlan($this->character, $positions, ['body' => ['prefix_id' => $prefix->id, 'suffix_id' => 999999]]);

        $this->assertContains('The selected enchantment for the body position is not currently valid.', $result['blockers']);
    }

    public function test_resolve_plan_reports_a_blocker_when_the_enchanting_skill_level_is_too_low(): void
    {
        $positions = ['body' => $this->createItem(['name' => 'Set body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id];
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 100, 'skill_level_trivial' => 200, 'cost' => 10]);

        $result = $this->service->resolvePlan($this->character, $positions, ['body' => ['prefix_id' => $prefix->id, 'suffix_id' => null]]);

        $this->assertContains('The selected enchantment for the body position is not currently valid.', $result['blockers']);
    }

    public function test_resolve_plan_resolves_valid_hand_items_into_the_queue(): void
    {
        $positions = [
            'left_hand' => $this->createItem(['name' => 'Set Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'sword', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'right_hand' => $this->createItem(['name' => 'Set Shield', 'type' => 'shield', 'crafting_type' => 'armour', 'default_position' => 'shield', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
        ];
        $prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 10]);

        $enchantments = [
            'left_hand' => ['prefix_id' => $prefix->id, 'suffix_id' => null],
            'right_hand' => ['prefix_id' => $prefix->id, 'suffix_id' => null],
        ];

        $result = $this->service->resolvePlan($this->character, $positions, $enchantments);

        $leftHandEntry = collect($result['queue'])->firstWhere('position', 'left_hand');
        $rightHandEntry = collect($result['queue'])->firstWhere('position', 'right_hand');
        $this->assertNotNull($leftHandEntry);
        $this->assertNotNull($rightHandEntry);
        $this->assertSame('weapon', $leftHandEntry['crafting_type']);
        $this->assertSame('armour', $rightHandEntry['crafting_type']);
    }

    public function test_resolve_plan_reports_a_blocker_for_an_invalid_hand_item(): void
    {
        $bodyItem = $this->createItem(['name' => 'Not A Hand Item', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $positions = ['left_hand' => $bodyItem->id];

        $result = $this->service->resolvePlan($this->character, $positions, []);

        $this->assertContains('The selected item for the left_hand position is not a valid hand item.', $result['blockers']);
    }

    public function test_resolve_plan_reports_a_blocker_for_an_invalid_hand_combination(): void
    {
        $positions = [
            'left_hand' => $this->createItem(['name' => 'Set Bow One', 'type' => 'bow', 'crafting_type' => 'weapon', 'default_position' => 'bow', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
            'right_hand' => $this->createItem(['name' => 'Set Bow Two', 'type' => 'bow', 'crafting_type' => 'weapon', 'default_position' => 'bow', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50])->id,
        ];

        $result = $this->service->resolvePlan($this->character, $positions, []);

        $this->assertContains('The selected hand items are not a valid combination.', $result['blockers']);
    }
}
