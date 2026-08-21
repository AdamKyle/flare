<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Game\Automation\BatchCrafting\Services\CraftExperienceTargetService;
use App\Game\Skills\Values\CraftingSkillGroup;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class CraftExperienceTargetServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    private ?CraftExperienceTargetService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(CraftExperienceTargetService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_cycle_size_is_the_authoritative_twenty_three_target_sequence(): void
    {
        $result = $this->service->cycleSize();

        $this->assertSame(23, $result);
    }

    public function test_skill_groups_returns_the_four_crafting_disciplines_in_order(): void
    {
        $result = $this->service->skillGroups();

        $this->assertSame([
            CraftingSkillGroup::WEAPON,
            CraftingSkillGroup::ARMOUR,
            CraftingSkillGroup::RING,
            CraftingSkillGroup::SPELL,
        ], $result);
    }

    public function test_has_meaningful_target_is_false_without_any_crafting_skill(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $skills = $this->service->resolveCraftingSkills($character);

        $result = $this->service->hasMeaningfulTarget($skills);

        $this->assertFalse($result);
    }

    public function test_has_meaningful_target_is_true_with_a_meaningful_craftable_item(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();

        $this->createItem(['name' => 'Target Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $skills = $this->service->resolveCraftingSkills($character);

        $result = $this->service->hasMeaningfulTarget($skills);

        $this->assertTrue($result);
    }

    public function test_resolve_next_target_skips_a_maxed_skill_and_returns_the_next_actionable_target(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->assignSkill($weaponCrafting, 5, false)
            ->assignSkill($armourCrafting, 10, false);
        $character = $factory->getCharacter();

        $this->createItem(['name' => 'Target Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $skills = $this->service->resolveCraftingSkills($character);

        $result = $this->service->resolveNextTarget($skills, 0);

        $this->assertNotNull($result);
        $this->assertSame(CraftingSkillGroup::ARMOUR, $result->target->skillGroup);
    }

    public function test_resolve_next_target_returns_null_when_no_actionable_target_exists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $skills = $this->service->resolveCraftingSkills($character);

        $result = $this->service->resolveNextTarget($skills, 0);

        $this->assertNull($result);
    }

    public function test_resolve_next_target_matches_a_weapon_target_through_item_type(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();

        $item = $this->createItem(['name' => 'Target Sword', 'type' => 'sword', 'crafting_type' => 'weapon', 'default_position' => 'none', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $skills = $this->service->resolveCraftingSkills($character);

        $result = $this->service->resolveNextTarget($skills, 0);

        $this->assertNotNull($result);
        $this->assertSame($item->id, $result->item->id);
        $this->assertSame('sword', $result->target->craftingType);
    }

    public function test_resolve_next_target_matches_a_weapon_target_through_default_position(): void
    {
        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();

        $item = $this->createItem(['name' => 'Target Hammer', 'type' => 'weapon', 'crafting_type' => 'weapon', 'default_position' => 'hammer', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $skills = $this->service->resolveCraftingSkills($character);

        $result = $this->service->resolveNextTarget($skills, 0);

        $this->assertNotNull($result);
        $this->assertSame($item->id, $result->item->id);
        $this->assertSame('hammer', $result->target->craftingType);
    }

    public function test_resolve_next_target_matches_an_armour_target_through_item_type(): void
    {
        $skill = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();

        $item = $this->createItem(['name' => 'Target Helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $skills = $this->service->resolveCraftingSkills($character);

        $result = $this->service->resolveNextTarget($skills, 0);

        $this->assertNotNull($result);
        $this->assertSame($item->id, $result->item->id);
        $this->assertSame('helmet', $result->target->itemType);
    }

    public function test_resolve_next_target_matches_a_ring_target(): void
    {
        $skill = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();

        $item = $this->createItem(['name' => 'Target Ring', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $skills = $this->service->resolveCraftingSkills($character);

        $result = $this->service->resolveNextTarget($skills, 0);

        $this->assertNotNull($result);
        $this->assertSame($item->id, $result->item->id);
        $this->assertSame(CraftingSkillGroup::RING, $result->target->skillGroup);
    }

    public function test_resolve_next_target_matches_a_damage_spell_target(): void
    {
        $skill = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();

        $item = $this->createItem(['name' => 'Target Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $skills = $this->service->resolveCraftingSkills($character);

        $result = $this->service->resolveNextTarget($skills, 0);

        $this->assertNotNull($result);
        $this->assertSame($item->id, $result->item->id);
        $this->assertSame('spell-damage', $result->target->itemType);
    }

    public function test_resolve_next_target_matches_a_healing_spell_target(): void
    {
        $skill = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($skill, 10, false)->getCharacter();

        $item = $this->createItem(['name' => 'Target Healing Spell', 'type' => 'spell-healing', 'crafting_type' => 'spell', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $skills = $this->service->resolveCraftingSkills($character);

        $result = $this->service->resolveNextTarget($skills, 0);

        $this->assertNotNull($result);
        $this->assertSame($item->id, $result->item->id);
        $this->assertSame('spell-healing', $result->target->itemType);
    }

    public function test_all_skills_maxed_is_true_when_every_crafting_skill_is_maxed(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        foreach (['Weapon Crafting', 'Armour Crafting', 'Ring Crafting', 'Spell Crafting'] as $name) {
            $skill = $this->createGameSkill(['name' => $name, 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);

            $factory = $factory->assignSkill($skill, 5, false);
        }

        $skills = $this->service->resolveCraftingSkills($factory->getCharacter());

        $result = $this->service->allSkillsMaxed($skills);

        $this->assertTrue($result);
    }

    public function test_all_skills_maxed_is_false_when_a_skill_is_missing(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        foreach (['Weapon Crafting', 'Armour Crafting', 'Ring Crafting'] as $name) {
            $skill = $this->createGameSkill(['name' => $name, 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);

            $factory = $factory->assignSkill($skill, 5, false);
        }

        $skills = $this->service->resolveCraftingSkills($factory->getCharacter());

        $result = $this->service->allSkillsMaxed($skills);

        $this->assertFalse($result);
    }

    public function test_all_skills_maxed_is_false_when_a_skill_can_still_gain_levels(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        foreach (['Weapon Crafting', 'Armour Crafting', 'Ring Crafting'] as $name) {
            $skill = $this->createGameSkill(['name' => $name, 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);

            $factory = $factory->assignSkill($skill, 5, false);
        }

        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $factory = $factory->assignSkill($spellCrafting, 10, false);

        $skills = $this->service->resolveCraftingSkills($factory->getCharacter());

        $result = $this->service->allSkillsMaxed($skills);

        $this->assertFalse($result);
    }
}
