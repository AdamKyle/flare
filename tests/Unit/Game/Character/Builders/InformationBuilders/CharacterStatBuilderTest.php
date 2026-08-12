<?php

namespace Tests\Unit\Game\Character\Builders\InformationBuilders;

use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\Values\CharacterClass;
use App\Game\Maps\Values\MapName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateCharacterClassSpecialitiesEquipped;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CharacterStatBuilderTest extends TestCase
{
    use CreateCharacterBoon, CreateCharacterClassSpecialitiesEquipped, CreateClass, CreateGameClassSpecial, CreateGameMap, CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterStatBuilder $characterStatBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterStatBuilder = resolve(CharacterStatBuilder::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->characterStatBuilder = null;
    }

    public function test_fetch_inventory_returns_empty_collection_when_no_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->fetchInventory();

        $this->assertTrue($result->isEmpty());
    }

    public function test_fetch_inventory_returns_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword']), true, 'left-hand')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->fetchInventory();

        $this->assertCount(1, $result);
    }

    public function test_class_bonus_returns_zero_without_a_class_bonus_skill(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $bonus = $this->characterStatBuilder->setCharacter($character)->classBonus();

        $this->assertSame(0.0, $bonus);
    }

    public function test_class_bonus_returns_calculated_bonus_for_class_skill(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $factory->getCharacter();

        $skill = $this->createGameSkill([
            'game_class_id' => $character->game_class_id,
            'class_bonus' => 0.01,
        ]);

        $factory->assignSkill($skill, 5);

        $bonus = $this->characterStatBuilder->setCharacter($factory->getCharacter())->classBonus();

        $this->assertSame(0.05, $bonus);
    }

    public function test_holy_info_returns_holy_builder(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $holyBuilder = $this->characterStatBuilder->setCharacter($character)->holyInfo();

        $this->assertInstanceOf(\App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HolyBuilder::class, $holyBuilder);
    }

    public function test_reduction_info_returns_reductions_builder(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $reductionsBuilder = $this->characterStatBuilder->setCharacter($character)->reductionInfo();

        $this->assertInstanceOf(\App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ReductionsBuilder::class, $reductionsBuilder);
    }

    public function test_can_affixes_be_resisted_is_false_without_quest_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->canAffixesBeResisted();

        $this->assertFalse($result);
    }

    public function test_can_affixes_be_resisted_is_true_with_the_irresistible_quest_item(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'quest', 'effect' => 'affixes-irresistible']))
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->canAffixesBeResisted();

        $this->assertTrue($result);
    }

    public function test_can_affixes_be_resisted_is_false_with_unrelated_quest_item(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'quest', 'effect' => 'purgatory']))
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->canAffixesBeResisted();

        $this->assertFalse($result);
    }

    public function test_stat_mod_applies_base_stat_mod_for_non_damage_stat(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['dur' => 100, 'base_stat_mod' => 0.10])
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->statMod('dur');

        $this->assertSame(110.0, $result);
    }

    public function test_stat_mod_applies_class_special_and_base_damage_stat_mod_for_damage_stat(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100, 'base_damage_stat_mod' => 0.10])
            ->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'base_damage_stat_increase' => 0.05,
        ]);

        $this->createCharacterClassRankSpecial([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 1,
            'equipped' => true,
        ]);

        $result = $this->characterStatBuilder->setCharacter($character->refresh())->statMod('str');

        $this->assertSame(115.0, $result);
    }

    public function test_stat_mod_applies_equipment_prefix_suffix_and_holy_stack_bonus(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix', 'str_mod' => 0.10]);
        $suffix = $this->createItemAffix(['type' => 'suffix', 'str_mod' => 0.05]);
        $item = $this->createItem([
            'type' => 'sword',
            'str_mod' => 0.10,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
        ]);
        $item->appliedHolyStacks()->create([
            'devouring_darkness_bonus' => 0.0,
            'stat_increase_bonus' => 0.05,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['damage_stat' => 'dur']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->inventoryManagement()
            ->giveItem($item->refresh(), true, 'left-hand')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->statMod('str');

        $this->assertSame(130.0, $result);
    }

    public function test_stat_mod_voided_ignores_prefix_and_suffix_bonuses(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix', 'str_mod' => 0.10]);
        $item = $this->createItem([
            'type' => 'sword',
            'str_mod' => 0.10,
            'item_prefix_id' => $prefix->id,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['damage_stat' => 'dur']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->inventoryManagement()
            ->giveItem($item, true, 'left-hand')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->statMod('str', true);

        $this->assertSame(110.0, $result);
    }

    public function test_stat_mod_applies_boon_that_increases_all_stats(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['damage_stat' => 'dur']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $boonItem = $this->createItem(['increase_stat_by' => 0.10]);
        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $boonItem->id,
            'last_for_minutes' => 60,
            'amount_used' => 1,
            'started' => now(),
            'complete' => now()->addHour(),
        ]);

        $result = $this->characterStatBuilder->setCharacter($character)->statMod('str');

        $this->assertSame(110.0, $result);
    }

    public function test_stat_mod_applies_reduction_when_on_a_reduction_map(): void
    {
        $hellMap = $this->createGameMap([
            'name' => MapName::HELL->value,
            'character_attack_reduction' => 0.5,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['damage_stat' => 'dur']))
            ->givePlayerLocation(gameMap: $hellMap)
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->statMod('str');

        $this->assertSame(50.0, $result);
    }

    public function test_stat_mod_ignores_reduction_when_ignore_reductions_is_true(): void
    {
        $hellMap = $this->createGameMap([
            'name' => MapName::HELL->value,
            'character_attack_reduction' => 0.5,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['damage_stat' => 'dur']))
            ->givePlayerLocation(gameMap: $hellMap)
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character, true)->statMod('str');

        $this->assertSame(100.0, $result);
    }

    public function test_get_map_character_reductions_applies_to_purgatory_quest_item_on_ice_plane(): void
    {
        $icePlaneMap = $this->createGameMap([
            'name' => MapName::ICE_PLANE->value,
            'character_attack_reduction' => 0.5,
        ]);

        $factory = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['damage_stat' => 'dur']))
            ->givePlayerLocation(gameMap: $icePlaneMap)
            ->updateCharacter(['str' => 100]);

        $factory->inventoryManagement()->giveItem($this->createItem(['type' => 'quest', 'effect' => 'purgatory']));

        $result = $this->characterStatBuilder->setCharacter($factory->getCharacter())->statMod('str');

        $this->assertSame(50.0, $result);
    }

    public function test_get_map_character_reductions_returns_zero_when_map_is_null(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['damage_stat' => 'dur']))
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->statMod('str');

        $this->assertSame(100.0, $result);
    }

    public function test_build_health_includes_class_special_health_bonus(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['dur' => 100])
            ->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'health_mod' => 0.10,
        ]);

        $this->createCharacterClassRankSpecial([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 1,
            'equipped' => true,
        ]);

        $health = $this->characterStatBuilder->setCharacter($character->refresh())->buildHealth();

        $this->assertSame(110.0, $health);
    }

    public function test_build_elemental_atonement_returns_array_or_null(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildElementalAtonement();

        $this->assertTrue(is_null($result) || is_array($result));
    }

    public function test_get_defence_builder_returns_defence_builder(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $defenceBuilder = $this->characterStatBuilder->setCharacter($character)->getDefenceBuilder();

        $this->assertInstanceOf(\App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DefenceBuilder::class, $defenceBuilder);
    }

    public function test_get_damage_builder_returns_damage_builder(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $damageBuilder = $this->characterStatBuilder->setCharacter($character)->getDamageBuilder();

        $this->assertInstanceOf(\App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DamageBuilder::class, $damageBuilder);
    }

    public function test_get_healing_builder_returns_healing_builder(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $healingBuilder = $this->characterStatBuilder->setCharacter($character)->getHealingBuilder();

        $this->assertInstanceOf(\App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HealingBuilder::class, $healingBuilder);
    }

    public function test_build_defence_includes_item_skill_bonus_when_equipped_items_present(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'shield', 'base_ac' => 10]), true, 'left-hand')
            ->getCharacter();

        $defence = $this->characterStatBuilder->setCharacter($character)->buildDefence();

        $this->assertGreaterThan(0, $defence);
    }

    public function test_build_time_out_modifier_returns_float(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildTimeOutModifier('base_damage');

        $this->assertIsFloat($result);
    }

    public function test_build_damage_without_equipped_items_gives_alcoholic_bonus_for_weapon_type(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::ALCOHOLIC->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage('sword');

        $this->assertSame(125, $damage);
    }

    public function test_build_damage_without_equipped_items_gives_fighter_bonus_for_weapon_type(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage('sword');

        $this->assertSame(105, $damage);
    }

    public function test_build_damage_without_equipped_items_gives_default_bonus_for_other_classes(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::VAMPIRE->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage('sword');

        $this->assertSame(5, $damage);
    }

    public function test_build_damage_without_equipped_items_gives_heretic_bonus_for_spell_damage(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::HERETIC->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage('spell-damage');

        $this->assertGreaterThanOrEqual(5, $damage);
    }

    public function test_build_damage_without_equipped_items_returns_zero_for_unrelated_type(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::VAMPIRE->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage('ring');

        $this->assertSame(0, $damage);
    }

    public function test_build_damage_with_equipped_items_combines_weapon_ring_and_spell_damage(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'base_damage' => 20]), true, 'left-hand')
            ->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage(['sword']);

        $this->assertGreaterThan(0, $damage);
    }

    public function test_build_total_attack_sums_weapon_ring_and_spell_damage(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $total = $this->characterStatBuilder->setCharacter($character)->buildTotalAttack();

        $this->assertGreaterThan(0, $total);
    }

    public function test_positional_weapon_damage_without_equipped_items_uses_half_stat(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->positionalWeaponDamage('left-hand');

        $this->assertGreaterThanOrEqual(5, $damage);
    }

    public function test_positional_weapon_damage_with_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'base_damage' => 20]), true, 'left-hand')
            ->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->positionalWeaponDamage('left-hand');

        $this->assertGreaterThan(0, $damage);
    }

    public function test_positional_spell_damage_without_equipped_items_for_heretic(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::HERETIC->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->positionalSpellDamage('spell-one');

        $this->assertGreaterThanOrEqual(5, $damage);
    }

    public function test_positional_spell_damage_without_equipped_items_for_non_caster_returns_zero(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::VAMPIRE->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->positionalSpellDamage('spell-one');

        $this->assertSame(0, $damage);
    }

    public function test_positional_spell_damage_with_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::HERETIC->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-damage', 'base_damage' => 20]), true, 'spell-one')
            ->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->positionalSpellDamage('spell-one');

        $this->assertGreaterThan(0, $damage);
    }

    public function test_positional_spell_damage_with_equipped_items_for_alcoholic(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::ALCOHOLIC->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-damage', 'base_damage' => 20]), true, 'spell-one')
            ->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->positionalSpellDamage('spell-one');

        $this->assertGreaterThanOrEqual(0, $damage);
    }

    public function test_positional_healing_without_equipped_items_for_prophet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::PROPHET->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $healing = $this->characterStatBuilder->setCharacter($character)->positionalHealing('spell-one');

        $this->assertGreaterThanOrEqual(5, $healing);
    }

    public function test_positional_healing_without_equipped_items_for_non_healer_returns_zero(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::VAMPIRE->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $healing = $this->characterStatBuilder->setCharacter($character)->positionalHealing('spell-one');

        $this->assertSame(0, $healing);
    }

    public function test_positional_healing_with_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::PROPHET->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-healing', 'base_healing' => 20]), true, 'spell-one')
            ->getCharacter();

        $healing = $this->characterStatBuilder->setCharacter($character)->positionalHealing('spell-one');

        $this->assertGreaterThan(0, $healing);
    }

    public function test_positional_healing_with_equipped_items_for_cleric(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::CLERIC->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-healing', 'base_healing' => 20]), true, 'spell-one')
            ->getCharacter();

        $healing = $this->characterStatBuilder->setCharacter($character)->positionalHealing('spell-one');

        $this->assertGreaterThan(0, $healing);
    }

    public function test_positional_healing_with_equipped_items_for_arcane_alchemist(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::ARCANE_ALCHEMIST->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-healing', 'base_healing' => 20]), true, 'spell-one')
            ->getCharacter();

        $healing = $this->characterStatBuilder->setCharacter($character)->positionalHealing('spell-one');

        $this->assertGreaterThan(0, $healing);
    }

    public function test_positional_healing_with_equipped_items_for_ranger(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::RANGER->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-healing', 'base_healing' => 20]), true, 'spell-one')
            ->getCharacter();

        $healing = $this->characterStatBuilder->setCharacter($character)->positionalHealing('spell-one');

        $this->assertGreaterThan(0, $healing);
    }

    public function test_build_healing_without_equipped_items_for_ranger(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::RANGER->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $healing = $this->characterStatBuilder->setCharacter($character)->buildHealing();

        $this->assertGreaterThanOrEqual(5, $healing);
    }

    public function test_build_healing_without_equipped_items_for_non_healer_returns_zero(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::VAMPIRE->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->getCharacter();

        $healing = $this->characterStatBuilder->setCharacter($character)->buildHealing();

        $this->assertSame(0, $healing);
    }

    public function test_build_healing_with_equipped_items_includes_item_skill_bonus(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::PROPHET->value, 'damage_stat' => 'str']))
            ->givePlayerLocation()
            ->updateCharacter(['str' => 100])
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-healing', 'base_healing' => 20]), true, 'spell-one')
            ->getCharacter();

        $healing = $this->characterStatBuilder->setCharacter($character)->buildHealing();

        $this->assertGreaterThan(0, $healing);
    }

    public function test_build_devouring_returns_zero_without_quest_items_or_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildDevouring('devouring_darkness');

        $this->assertSame(0.0, $result);
    }

    public function test_build_devouring_reduces_by_purgatory_when_on_purgatory_map_with_no_equipped_items(): void
    {
        $purgatoryMap = $this->createGameMap([
            'name' => MapName::PURGATORY->value,
        ]);

        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(gameMap: $purgatoryMap);
        $factory->inventoryManagement()->giveItem($this->createItem([
            'type' => 'quest',
            'devouring_darkness' => 0.60,
        ]));

        $result = $this->characterStatBuilder->setCharacter($factory->getCharacter())->buildDevouring('devouring_darkness');

        $this->assertSame(0.15, round($result, 2));
    }

    public function test_build_devouring_combines_quest_devouring_with_equipped_items_present_and_caps_at_one(): void
    {
        $item = $this->createItem(['type' => 'sword']);

        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $factory->inventoryManagement()
            ->giveItem($item, true, 'left-hand')
            ->giveItem($this->createItem(['type' => 'quest', 'devouring_darkness' => 0.60]))
            ->giveItem($this->createItem(['type' => 'quest', 'devouring_darkness' => 0.60]));

        $result = $this->characterStatBuilder->setCharacter($factory->getCharacter())->buildDevouring('devouring_darkness');

        $this->assertSame(1.0, $result);
    }

    public function test_build_devouring_reduces_by_purgatory_with_equipped_items_present(): void
    {
        $purgatoryMap = $this->createGameMap(['name' => MapName::PURGATORY->value]);

        $item = $this->createItem(['type' => 'sword']);

        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(gameMap: $purgatoryMap);
        $factory->inventoryManagement()
            ->giveItem($item, true, 'left-hand')
            ->giveItem($this->createItem(['type' => 'quest', 'devouring_darkness' => 0.60]));

        $result = $this->characterStatBuilder->setCharacter($factory->getCharacter())->buildDevouring('devouring_darkness');

        $this->assertSame(0.15, $result);
    }

    public function test_build_resurrection_chance_returns_zero_without_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildResurrectionChance();

        $this->assertSame(0.0, $result);
    }

    public function test_build_resurrection_chance_adds_prophet_bonus(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::PROPHET->value]))
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-healing', 'resurrection_chance' => 0.10]), true, 'spell-one')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildResurrectionChance();

        $this->assertSame(0.15, round($result, 2));
    }

    public function test_build_resurrection_chance_caps_prophet_at_065_in_purgatory(): void
    {
        $purgatoryMap = $this->createGameMap([
            'name' => MapName::PURGATORY->value,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::PROPHET->value]))
            ->givePlayerLocation(gameMap: $purgatoryMap)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-healing', 'resurrection_chance' => 0.90]), true, 'spell-one')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildResurrectionChance();

        $this->assertSame(0.65, $result);
    }

    public function test_build_resurrection_chance_caps_non_prophet_at_045_in_purgatory(): void
    {
        $purgatoryMap = $this->createGameMap([
            'name' => MapName::PURGATORY->value,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation(gameMap: $purgatoryMap)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-healing', 'resurrection_chance' => 0.90]), true, 'spell-one')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildResurrectionChance();

        $this->assertSame(0.45, $result);
    }

    public function test_build_resurrection_chance_caps_vampire_at_095(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::VAMPIRE->value]))
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-healing', 'resurrection_chance' => 0.99]), true, 'spell-one')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildResurrectionChance();

        $this->assertSame(0.95, $result);
    }

    public function test_build_resurrection_chance_caps_other_classes_at_075(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-healing', 'resurrection_chance' => 0.99]), true, 'spell-one')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildResurrectionChance();

        $this->assertSame(0.75, $result);
    }

    public function test_build_resurrection_chance_caps_prophet_at_one_when_over(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::PROPHET->value]))
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem(['type' => 'spell-healing', 'resurrection_chance' => 0.60]), 2, true, 'spell-one')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildResurrectionChance();

        $this->assertSame(1.0, $result);
    }

    public function test_build_affix_damage_returns_stacking_damage(): void
    {
        $suffix = $this->createItemAffix(['damage_can_stack' => true, 'damage_amount' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'item_suffix_id' => $suffix->id]), true, 'left-hand')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('affix-stacking-damage');

        $this->assertSame(5.0, $result);
    }

    public function test_build_affix_damage_returns_non_stacking_damage(): void
    {
        $suffix = $this->createItemAffix(['damage_can_stack' => false, 'damage_amount' => 7]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'item_suffix_id' => $suffix->id]), true, 'left-hand')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('affix-non-stacking');

        $this->assertSame(7.0, $result);
    }

    public function test_build_affix_damage_returns_life_stealing_damage(): void
    {
        $suffix = $this->createItemAffix(['steal_life_amount' => 0.20]);
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'item_suffix_id' => $suffix->id]), true, 'left-hand')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('life-stealing');

        $this->assertSame(0.2, $result);
    }

    public function test_build_affix_damage_returns_zero_for_unknown_type(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('unknown-type');

        $this->assertSame(0, $result);
    }

    public function test_build_entrancing_chance_returns_zero_when_voided(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildEntrancingChance(true);

        $this->assertSame(0.0, $result);
    }

    public function test_build_entrancing_chance_returns_zero_without_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildEntrancingChance();

        $this->assertSame(0.0, $result);
    }

    public function test_build_entrancing_chance_caps_at_one(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix', 'entranced_chance' => 0.60]);
        $suffix = $this->createItemAffix(['type' => 'suffix', 'entranced_chance' => 0.60]);
        $item = $this->createItem(['type' => 'sword', 'item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'left-hand')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildEntrancingChance();

        $this->assertSame(1.0, $result);
    }

    public function test_build_resistance_reduction_chance_returns_zero_when_voided(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildResistanceReductionChance(true);

        $this->assertSame(0.0, $result);
    }

    public function test_build_resistance_reduction_chance_caps_at_one(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix', 'resistance_reduction' => 1.50]);
        $item = $this->createItem(['type' => 'sword', 'item_prefix_id' => $prefix->id]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'left-hand')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildResistanceReductionChance();

        $this->assertSame(1.0, $result);
    }

    public function test_get_stat_reducing_prefix_returns_null_without_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->getStatReducingPrefix();

        $this->assertNull($result);
    }

    public function test_get_stat_reducing_prefix_returns_matching_prefix(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix', 'reduces_enemy_stats' => true]);
        $item = $this->createItem(['type' => 'sword', 'item_prefix_id' => $prefix->id]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'left-hand')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->getStatReducingPrefix();

        $this->assertSame($prefix->id, $result->id);
    }

    public function test_get_stat_reducing_prefix_returns_null_when_no_prefix_reduces_stats(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix', 'reduces_enemy_stats' => false]);
        $item = $this->createItem(['type' => 'sword', 'item_prefix_id' => $prefix->id]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'left-hand')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->getStatReducingPrefix();

        $this->assertNull($result);
    }

    public function test_get_stat_reducing_suffixes_returns_empty_array_without_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->getStatReducingSuffixes();

        $this->assertSame([], $result);
    }

    public function test_get_stat_reducing_suffixes_returns_matching_suffixes(): void
    {
        $suffix = $this->createItemAffix(['type' => 'suffix', 'reduces_enemy_stats' => true]);
        $item = $this->createItem(['type' => 'sword', 'item_suffix_id' => $suffix->id]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'left-hand')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->getStatReducingSuffixes();

        $this->assertCount(1, $result);
        $this->assertSame($suffix->id, $result[0]->id);
    }

    public function test_build_ambush_returns_zero_without_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildAmbush();

        $this->assertSame(0.0, $result);
    }

    public function test_build_ambush_chance_caps_at_095(): void
    {
        $item = $this->createItem(['type' => 'trinket', 'ambush_chance' => 1.5]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'trinket-one')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildAmbush('chance');

        $this->assertSame(0.95, $result);
    }

    public function test_build_ambush_resistance_caps_at_095(): void
    {
        $item = $this->createItem(['type' => 'trinket', 'ambush_resistance' => 1.5]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'trinket-one')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildAmbush('resistance');

        $this->assertSame(0.95, $result);
    }

    public function test_build_counter_returns_zero_without_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildCounter();

        $this->assertSame(0.0, $result);
    }

    public function test_build_counter_chance_caps_at_095(): void
    {
        $item = $this->createItem(['type' => 'trinket', 'counter_chance' => 1.5]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'trinket-one')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildCounter('chance');

        $this->assertSame(0.95, $result);
    }

    public function test_build_counter_resistance_caps_at_095(): void
    {
        $item = $this->createItem(['type' => 'trinket', 'counter_resistance' => 1.5]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'trinket-one')
            ->getCharacter();

        $result = $this->characterStatBuilder->setCharacter($character)->buildCounter('resistance');

        $this->assertSame(0.95, $result);
    }
}
