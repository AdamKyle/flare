<?php

namespace Tests\Unit\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ClassRanksWeaponMasteriesBuilder;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use App\Game\Core\Items\Values\ItemType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ClassRanksWeaponMasteriesBuilderTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?CharacterStatBuilder $characterStatBuilder;

    private ?ClassRanksWeaponMasteriesBuilder $classRanksWeaponMasteriesBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterStatBuilder = resolve(CharacterStatBuilder::class);
        $this->classRanksWeaponMasteriesBuilder = resolve(ClassRanksWeaponMasteriesBuilder::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->characterStatBuilder = null;
        $this->classRanksWeaponMasteriesBuilder = null;
    }

    public function test_determine_bonus_for_weapon_returns_zero_without_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->classRanksWeaponMasteriesBuilder->initialize($character, $character->skills, $equipped);

        $result = $this->classRanksWeaponMasteriesBuilder->determineBonusForWeapon();

        $this->assertSame(0.0, $result);
    }

    public function test_determine_bonus_for_weapon_uses_mastery_level_for_specific_position(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $item = $this->createItem(['type' => ItemType::SWORD->value]);
        $factory->inventoryManagement()->giveItem($item, true, 'left-hand');
        $character = $factory->getCharacter();

        $classRank = $character->classRanks->first();
        $weaponMastery = $classRank->weaponMasteries->where('weapon_type', ItemType::SWORD->value)->first();
        $weaponMastery->update([
            'weapon_type' => WeaponMasteryValue::getNumericValueForStringType(ItemType::SWORD->value),
            'level' => 50,
        ]);

        $equipped = $this->characterStatBuilder->fetchEquipped($character->refresh());
        $this->classRanksWeaponMasteriesBuilder->initialize($character, $character->skills, $equipped);

        $result = $this->classRanksWeaponMasteriesBuilder->determineBonusForWeapon('left-hand');

        $this->assertSame(0.5, $result);
    }

    public function test_determine_bonus_for_weapon_combines_left_and_right_hand_for_both_position(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $leftItem = $this->createItem(['type' => ItemType::SWORD->value]);
        $rightItem = $this->createItem(['type' => ItemType::SWORD->value]);
        $factory->inventoryManagement()
            ->giveItem($leftItem, true, 'left-hand')
            ->giveItem($rightItem, true, 'right-hand');
        $character = $factory->getCharacter();

        $classRank = $character->classRanks->first();
        $weaponMastery = $classRank->weaponMasteries->where('weapon_type', ItemType::SWORD->value)->first();
        $weaponMastery->update([
            'weapon_type' => WeaponMasteryValue::getNumericValueForStringType(ItemType::SWORD->value),
            'level' => 20,
        ]);

        $equipped = $this->characterStatBuilder->fetchEquipped($character->refresh());
        $this->classRanksWeaponMasteriesBuilder->initialize($character, $character->skills, $equipped);

        $result = $this->classRanksWeaponMasteriesBuilder->determineBonusForWeapon('both');

        $this->assertSame(0.4, round($result, 2));
    }

    public function test_determine_bonus_for_weapon_returns_zero_when_item_type_is_not_a_valid_mastery_type(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $item = $this->createItem(['type' => 'shield']);
        $factory->inventoryManagement()->giveItem($item, true, 'left-hand');
        $character = $factory->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->classRanksWeaponMasteriesBuilder->initialize($character, $character->skills, $equipped);

        $result = $this->classRanksWeaponMasteriesBuilder->determineBonusForWeapon('left-hand');

        $this->assertSame(0.0, $result);
    }

    public function test_determine_bonus_for_weapon_returns_zero_when_no_matching_class_rank(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $item = $this->createItem(['type' => ItemType::SWORD->value]);
        $factory->inventoryManagement()->giveItem($item, true, 'left-hand');
        $character = $factory->getCharacter();

        $character->classRanks()->delete();

        $equipped = $this->characterStatBuilder->fetchEquipped($character->refresh());
        $this->classRanksWeaponMasteriesBuilder->initialize($character, $character->skills, $equipped);

        $result = $this->classRanksWeaponMasteriesBuilder->determineBonusForWeapon('left-hand');

        $this->assertSame(0.0, $result);
    }

    public function test_fetch_class_mastery_break_down_returns_empty_array_without_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->classRanksWeaponMasteriesBuilder->initialize($character, $character->skills, $equipped);

        $result = $this->classRanksWeaponMasteriesBuilder->fetchClassMasteryBreakDownForPosition(ItemType::SWORD->value, 'left-hand');

        $this->assertSame([], $result);
    }

    public function test_fetch_class_mastery_break_down_returns_empty_array_when_no_matching_slot(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $item = $this->createItem(['type' => ItemType::SWORD->value]);
        $factory->inventoryManagement()->giveItem($item, true, 'left-hand');
        $character = $factory->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->classRanksWeaponMasteriesBuilder->initialize($character, $character->skills, $equipped);

        $result = $this->classRanksWeaponMasteriesBuilder->fetchClassMasteryBreakDownForPosition(ItemType::BOW->value, 'left-hand');

        $this->assertSame([], $result);
    }

    public function test_fetch_class_mastery_break_down_returns_mastery_details_for_matching_slot(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $item = $this->createItem(['type' => ItemType::SWORD->value]);
        $factory->inventoryManagement()->giveItem($item, true, 'left-hand');
        $character = $factory->getCharacter();

        $classRank = $character->classRanks->first();
        $weaponMastery = $classRank->weaponMasteries->where('weapon_type', ItemType::SWORD->value)->first();
        $weaponMastery->update(['level' => 30]);

        $equipped = $this->characterStatBuilder->fetchEquipped($character->refresh());
        $this->classRanksWeaponMasteriesBuilder->initialize($character, $character->skills, $equipped);

        $result = $this->classRanksWeaponMasteriesBuilder->fetchClassMasteryBreakDownForPosition(ItemType::SWORD->value, 'left-hand');

        $this->assertSame('left-hand', $result['position']);
        $this->assertSame(ItemType::SWORD->value, $result['name']);
        $this->assertSame(0.3, $result['amount']);
    }

    public function test_determine_bonus_for_spell_damage_returns_zero_without_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->classRanksWeaponMasteriesBuilder->initialize($character, $character->skills, $equipped);

        $result = $this->classRanksWeaponMasteriesBuilder->determineBonusForSpellDamage();

        $this->assertSame(0.0, $result);
    }

    public function test_determine_bonus_for_spell_damage_uses_specific_position(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $item = $this->createItem(['type' => ItemType::SPELL_DAMAGE->value]);
        $factory->inventoryManagement()->giveItem($item, true, 'spell-one');
        $character = $factory->getCharacter();

        $classRank = $character->classRanks->first();
        $weaponMastery = $classRank->weaponMasteries->where('weapon_type', ItemType::SPELL_DAMAGE->value)->first();
        $weaponMastery->update([
            'weapon_type' => WeaponMasteryValue::getNumericValueForStringType(ItemType::SPELL_DAMAGE->value),
            'level' => 40,
        ]);

        $equipped = $this->characterStatBuilder->fetchEquipped($character->refresh());
        $this->classRanksWeaponMasteriesBuilder->initialize($character, $character->skills, $equipped);

        $result = $this->classRanksWeaponMasteriesBuilder->determineBonusForSpellDamage('spell-one');

        $this->assertSame(0.4, $result);
    }

    public function test_determine_bonus_for_spell_damage_avoids_double_counting_the_same_spell_in_both_slots(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $item = $this->createItem(['type' => ItemType::SPELL_DAMAGE->value]);
        $factory->inventoryManagement()->giveItemMultipleTimes($item, 2, true, 'spell-one');
        $character = $factory->getCharacter();

        $classRank = $character->classRanks->first();
        $weaponMastery = $classRank->weaponMasteries->where('weapon_type', ItemType::SPELL_DAMAGE->value)->first();
        $weaponMastery->update([
            'weapon_type' => WeaponMasteryValue::getNumericValueForStringType(ItemType::SPELL_DAMAGE->value),
            'level' => 40,
        ]);

        $slots = $character->inventory->slots;
        $slots->last()->update(['position' => 'spell-two']);

        $equipped = $this->characterStatBuilder->fetchEquipped($character->refresh());
        $this->classRanksWeaponMasteriesBuilder->initialize($character, $character->skills, $equipped);

        $result = $this->classRanksWeaponMasteriesBuilder->determineBonusForSpellDamage('both');

        $this->assertSame(0.4, $result);
    }

    public function test_determine_bonus_for_spell_damage_sums_different_spells_in_both_slots(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $item = $this->createItem(['type' => ItemType::SPELL_DAMAGE->value]);
        $factory->inventoryManagement()->giveItem($item, true, 'spell-one');
        $character = $factory->getCharacter();

        $classRank = $character->classRanks->first();
        $weaponMastery = $classRank->weaponMasteries->where('weapon_type', ItemType::SPELL_DAMAGE->value)->first();
        $weaponMastery->update([
            'weapon_type' => WeaponMasteryValue::getNumericValueForStringType(ItemType::SPELL_DAMAGE->value),
            'level' => 20,
        ]);

        $equipped = $this->characterStatBuilder->fetchEquipped($character->refresh());
        $this->classRanksWeaponMasteriesBuilder->initialize($character, $character->skills, $equipped);

        $result = $this->classRanksWeaponMasteriesBuilder->determineBonusForSpellDamage('both');

        $this->assertSame(0.2, $result);
    }

    public function test_determine_bonus_for_spell_healing_returns_zero_without_equipped_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->classRanksWeaponMasteriesBuilder->initialize($character, $character->skills, $equipped);

        $result = $this->classRanksWeaponMasteriesBuilder->determineBonusForSpellHealing();

        $this->assertSame(0.0, $result);
    }

    public function test_determine_bonus_for_spell_healing_uses_specific_position(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $item = $this->createItem(['type' => ItemType::SPELL_HEALING->value]);
        $factory->inventoryManagement()->giveItem($item, true, 'spell-one');
        $character = $factory->getCharacter();

        $classRank = $character->classRanks->first();
        $weaponMastery = $classRank->weaponMasteries->where('weapon_type', ItemType::SPELL_HEALING->value)->first();
        $weaponMastery->update([
            'weapon_type' => WeaponMasteryValue::getNumericValueForStringType(ItemType::SPELL_HEALING->value),
            'level' => 40,
        ]);

        $equipped = $this->characterStatBuilder->fetchEquipped($character->refresh());
        $this->classRanksWeaponMasteriesBuilder->initialize($character, $character->skills, $equipped);

        $result = $this->classRanksWeaponMasteriesBuilder->determineBonusForSpellHealing('spell-one');

        $this->assertSame(0.4, $result);
    }

    public function test_determine_bonus_for_spell_healing_avoids_double_counting_the_same_spell_in_both_slots(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $item = $this->createItem(['type' => ItemType::SPELL_HEALING->value]);
        $factory->inventoryManagement()->giveItemMultipleTimes($item, 2, true, 'spell-one');
        $character = $factory->getCharacter();

        $classRank = $character->classRanks->first();
        $weaponMastery = $classRank->weaponMasteries->where('weapon_type', ItemType::SPELL_HEALING->value)->first();
        $weaponMastery->update([
            'weapon_type' => WeaponMasteryValue::getNumericValueForStringType(ItemType::SPELL_HEALING->value),
            'level' => 40,
        ]);

        $slots = $character->inventory->slots;
        $slots->last()->update(['position' => 'spell-two']);

        $equipped = $this->characterStatBuilder->fetchEquipped($character->refresh());
        $this->classRanksWeaponMasteriesBuilder->initialize($character, $character->skills, $equipped);

        $result = $this->classRanksWeaponMasteriesBuilder->determineBonusForSpellHealing('both');

        $this->assertSame(0.4, $result);
    }
}
