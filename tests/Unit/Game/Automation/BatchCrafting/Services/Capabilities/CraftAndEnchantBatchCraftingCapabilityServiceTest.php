<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Capabilities;

use App\Flare\Models\GameSkill;
use App\Game\Automation\BatchCrafting\Services\Capabilities\CraftAndEnchantBatchCraftingCapabilityService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;

class CraftAndEnchantBatchCraftingCapabilityServiceTest extends TestCase
{
    use CreateGameSkill, RefreshDatabase;

    private ?CraftAndEnchantBatchCraftingCapabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(CraftAndEnchantBatchCraftingCapabilityService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_build_is_false_without_any_crafting_skill(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->build($character);

        $this->assertFalse($result['can_craft_and_enchant']);
        $this->assertFalse($result['can_craft_and_enchant_for_experience']);
        $this->assertNotNull($result['enchanting_skill']);
    }

    public function test_build_is_true_with_a_crafting_skill_and_the_default_enchanting_skill(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();

        $result = $this->service->build($character);

        $this->assertTrue($result['can_craft_and_enchant']);
    }

    public function test_build_for_experience_is_false_when_neither_side_has_meaningful_progression(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 10]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => $enchantingGameSkill->max_level]);

        $result = $this->service->build($character);

        $this->assertTrue($result['can_craft_and_enchant']);
        $this->assertFalse($result['can_craft_and_enchant_for_experience']);
    }

    public function test_build_is_false_and_enchanting_skill_is_null_when_the_character_has_no_enchanting_skill(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $character->skills()->where('game_skill_id', $enchantingGameSkill->id)->delete();

        $result = $this->service->build($character);

        $this->assertFalse($result['can_craft_and_enchant']);
        $this->assertNull($result['enchanting_skill']);
    }

    public function test_enchanting_skill_facts_for_returns_the_characters_enchanting_skill_progress(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->enchantingSkillFactsFor($character);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('skill_name', $result);
        $this->assertArrayHasKey('level', $result);
    }
}
