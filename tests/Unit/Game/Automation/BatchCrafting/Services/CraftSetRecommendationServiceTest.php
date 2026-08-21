<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Services\CraftSetRecommendationService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class CraftSetRecommendationServiceTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?CraftSetRecommendationService $service;

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

        $this->service = resolve(CraftSetRecommendationService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->service = null;
    }

    public function test_build_recommends_the_highest_craftable_item_for_every_required_position(): void
    {
        $this->createItem(['name' => 'Weak Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $bestBody = $this->createItem(['name' => 'Best Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 5, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Leggings', 'type' => 'leggings', 'crafting_type' => 'armour', 'default_position' => 'leggings', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Sleeves', 'type' => 'sleeves', 'crafting_type' => 'armour', 'default_position' => 'sleeves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Gloves', 'type' => 'gloves', 'crafting_type' => 'armour', 'default_position' => 'gloves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Feet', 'type' => 'feet', 'crafting_type' => 'armour', 'default_position' => 'feet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $bestRing = $this->createItem(['name' => 'Best Ring', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 5, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Weak Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'default_position' => 'spell-damage', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $bestDamageSpell = $this->createItem(['name' => 'Best Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'default_position' => 'spell-damage', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 5, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Weak Healing Spell', 'type' => 'spell-healing', 'crafting_type' => 'spell', 'default_position' => 'spell-healing', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $bestHealingSpell = $this->createItem(['name' => 'Best Healing Spell', 'type' => 'spell-healing', 'crafting_type' => 'spell', 'default_position' => 'spell-healing', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 5, 'skill_level_trivial' => 50]);

        $result = $this->service->build($this->character);

        $this->assertEmpty($result['missing_positions']);
        $this->assertCount(10, $result['positions']);
        $this->assertSame(
            ['body', 'leggings', 'sleeves', 'gloves', 'feet', 'helmet', 'ring_0', 'ring_1', 'spell-damage', 'spell-healing'],
            array_column($result['positions'], 'position')
        );

        $positionsByKey = collect($result['positions'])->keyBy('position');

        $this->assertSame($bestBody->id, $positionsByKey->get('body')['item_id']);
        $this->assertSame($bestRing->id, $positionsByKey->get('ring_0')['item_id']);
        $this->assertSame($bestRing->id, $positionsByKey->get('ring_1')['item_id']);
        $this->assertSame($bestDamageSpell->id, $positionsByKey->get('spell-damage')['item_id']);
        $this->assertSame($bestHealingSpell->id, $positionsByKey->get('spell-healing')['item_id']);
        $this->assertFalse($positionsByKey->has('left_hand'));
        $this->assertFalse($positionsByKey->has('right_hand'));
    }

    public function test_build_reports_a_missing_required_position_when_no_candidate_is_craftable(): void
    {
        $this->createItem(['name' => 'Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Leggings', 'type' => 'leggings', 'crafting_type' => 'armour', 'default_position' => 'leggings', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Sleeves', 'type' => 'sleeves', 'crafting_type' => 'armour', 'default_position' => 'sleeves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Gloves', 'type' => 'gloves', 'crafting_type' => 'armour', 'default_position' => 'gloves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Feet', 'type' => 'feet', 'crafting_type' => 'armour', 'default_position' => 'feet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Ring', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'default_position' => 'spell-damage', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Healing Spell', 'type' => 'spell-healing', 'crafting_type' => 'spell', 'default_position' => 'spell-healing', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $result = $this->service->build($this->character);

        $this->assertSame(['helmet'], $result['missing_positions']);
        $this->assertCount(9, $result['positions']);
        $this->assertFalse(collect($result['positions'])->keyBy('position')->has('helmet'));
    }

    public function test_build_omits_spell_positions_when_the_character_lacks_spell_crafting(): void
    {
        $characterWithoutSpellCrafting = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->assignSkill($this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]), 10, false)
            ->assignSkill($this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]), 10, false)
            ->getCharacter();

        $this->createItem(['name' => 'Body', 'type' => 'body', 'crafting_type' => 'armour', 'default_position' => 'body', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Leggings', 'type' => 'leggings', 'crafting_type' => 'armour', 'default_position' => 'leggings', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Sleeves', 'type' => 'sleeves', 'crafting_type' => 'armour', 'default_position' => 'sleeves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Gloves', 'type' => 'gloves', 'crafting_type' => 'armour', 'default_position' => 'gloves', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Feet', 'type' => 'feet', 'crafting_type' => 'armour', 'default_position' => 'feet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);
        $this->createItem(['name' => 'Ring', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $result = $this->service->build($characterWithoutSpellCrafting);

        $this->assertSame(['spell-damage', 'spell-healing'], $result['missing_positions']);

        $positionsByKey = collect($result['positions'])->keyBy('position');

        $this->assertTrue($positionsByKey->has('body'));
        $this->assertTrue($positionsByKey->has('ring_0'));
        $this->assertFalse($positionsByKey->has('spell-damage'));
        $this->assertFalse($positionsByKey->has('spell-healing'));
    }
}
