<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Game\Automation\BatchCrafting\Services\BatchCraftingCapabilityService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class BatchCraftingCapabilityServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    private ?BatchCraftingCapabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(BatchCraftingCapabilityService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_build_aggregates_the_craft_capability_facts(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        foreach (['Weapon Crafting', 'Armour Crafting', 'Ring Crafting'] as $name) {
            $skill = $this->createGameSkill(['name' => $name, 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);

            $factory = $factory->assignSkill($skill, 5, false);
        }

        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $factory = $factory->assignSkill($spellCrafting, 10, false);

        $this->createItem(['name' => 'Meaningful Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'can_craft' => true, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $character = $factory->getCharacter();

        $result = $this->service->build($character);

        $this->assertTrue($result['can_craft_for_experience']);
        $this->assertFalse($result['can_craft_for_event']);
        $this->assertNull($result['event_goal']);
        $this->assertCount(4, $result['crafting_skills']);
    }

    public function test_build_reports_false_defaults_for_workflows_not_yet_available(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->build($character);

        $this->assertFalse($result['can_craft_and_enchant']);
        $this->assertFalse($result['can_craft_and_enchant_for_experience']);
        $this->assertFalse($result['can_enchant_for_event']);
        $this->assertTrue($result['can_alchemy']);
        $this->assertFalse($result['can_alchemy_for_experience']);
        $this->assertFalse($result['can_holy_oils']);
        $this->assertFalse($result['can_trinketry']);
        $this->assertNull($result['enchant_event_goal']);
        $this->assertNotNull($result['alchemy_skill']);
        $this->assertSame('Alchemy', $result['alchemy_skill']['skill_name']);
        $this->assertNull($result['trinketry_skill']);
    }

    public function test_build_reports_craft_and_enchant_capability_facts(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $factory = $factory->assignSkill($weaponCrafting, 5, false);

        $enchanting = $this->createGameSkill(['name' => 'Enchanting', 'type' => SkillTypeValue::ENCHANTING->value, 'max_level' => 5]);
        $factory = $factory->assignSkill($enchanting, 1, false);

        $character = $factory->getCharacter();

        $result = $this->service->build($character);

        $this->assertTrue($result['can_craft_and_enchant']);
        $this->assertNotNull($result['enchanting_skill']);
        $this->assertSame('Enchanting', $result['enchanting_skill']['skill_name']);
    }
}
