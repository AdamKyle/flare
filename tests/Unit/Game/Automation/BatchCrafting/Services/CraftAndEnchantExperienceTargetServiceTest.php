<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Services\CraftAndEnchantExperienceTargetService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CraftAndEnchantExperienceTargetServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CraftAndEnchantExperienceTargetService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(CraftAndEnchantExperienceTargetService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_resolve_next_prefers_the_crafting_experience_cycle_target(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();

        $this->createItem(['name' => 'Meaningful Dagger', 'type' => 'dagger', 'default_position' => 'dagger', 'crafting_type' => 'weapon', 'can_craft' => true, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $result = $this->service->resolveNext($character, 0);

        $this->assertNotNull($result);
        $this->assertSame('Meaningful Dagger', $result->item->name);
    }

    public function test_has_meaningful_work_is_true_when_a_crafting_target_exists(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();

        $this->createItem(['name' => 'Meaningful Dagger', 'type' => 'dagger', 'default_position' => 'dagger', 'crafting_type' => 'weapon', 'can_craft' => true, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $this->assertTrue($this->service->hasMeaningfulWork($character));
    }

    public function test_resolve_next_falls_back_to_any_craftable_item_once_crafting_is_maxed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();

        $this->createItem(['name' => 'Fallback Dagger', 'type' => 'dagger', 'default_position' => 'dagger', 'crafting_type' => 'weapon', 'can_craft' => true, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createItemAffix(['type' => 'suffix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $result = $this->service->resolveNext($character, 0);

        $this->assertNotNull($result);
        $this->assertSame('Fallback Dagger', $result->item->name);
    }

    public function test_resolve_next_returns_null_when_neither_side_offers_meaningful_progression(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => $enchantingGameSkill->max_level]);

        $result = $this->service->resolveNext($character, 0);

        $this->assertNull($result);
        $this->assertFalse($this->service->hasMeaningfulWork($character));
    }

    public function test_resolve_next_does_not_fall_back_when_no_meaningful_affix_exists(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();

        $this->createItem(['name' => 'Fallback Dagger', 'type' => 'dagger', 'default_position' => 'dagger', 'crafting_type' => 'weapon', 'can_craft' => true, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $result = $this->service->resolveNext($character, 0);

        $this->assertNull($result);
    }

    public function test_resolve_next_returns_null_from_fallback_when_no_group_has_a_craftable_item(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();

        $this->createItemAffix(['type' => 'suffix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $result = $this->service->resolveNext($character, 0);

        $this->assertNull($result);
    }

    public function test_resolve_next_falls_back_to_a_non_weapon_crafting_group_item(): void
    {
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($armourCrafting, 5, false)->getCharacter();

        $this->createItem(['name' => 'Fallback Body', 'type' => 'body', 'default_position' => 'body', 'crafting_type' => 'armour', 'can_craft' => true, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $this->createItemAffix(['type' => 'suffix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $result = $this->service->resolveNext($character, 0);

        $this->assertNotNull($result);
        $this->assertSame('Fallback Body', $result->item->name);
    }
}
