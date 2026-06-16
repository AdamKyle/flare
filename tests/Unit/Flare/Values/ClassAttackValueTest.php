<?php

namespace Tests\Unit\Flare\Values;

use App\Flare\Values\ClassAttackValue;
use App\Game\Character\CharacterInventory\Values\ArmourType;
use App\Game\Character\CharacterInventory\Values\ItemType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateItem;

class ClassAttackValueTest extends TestCase
{
    use CreateItemAffix;
    use CreateItem;
    use RefreshDatabase;

    public function testBuildAttackDataContainsLegacyAndDisplayOnlyFieldsForEquippedInventoryClassItem(): void
    {
        $characterFactory = new CharacterFactory;
        $characterFactory
            ->createBaseCharacter([
                'name' => 'Human',
            ], [
                'name' => 'Fighter',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation();
        $prefix = $this->createItemAffix([
            'name' => 'Sharp',
            'type' => 'prefix',
            'randomly_generated' => false,
        ]);
        $suffix = $this->createItemAffix([
            'name' => 'Flame',
            'type' => 'suffix',
            'randomly_generated' => false,
        ]);
        $sword = $this->createItem([
            'name' => 'Iron Sword',
            'type' => ItemType::SWORD->value,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);
        $character = $characterFactory
            ->inventoryManagement()
            ->giveItem($sword, true, 'right-hand')
            ->getCharacter();

        $attackData = (new ClassAttackValue($character))->buildAttackData();

        $this->assertEquals(0.05, $attackData['chance']);
        $this->assertEquals(ClassAttackValue::FIGHTERS_DOUBLE_DAMAGE, $attackData['type']);
        $this->assertEquals(ItemType::SWORD->value, $attackData['only']);
        $this->assertTrue($attackData['has_item']);
        $this->assertEquals(1, $attackData['amount']);
        $this->assertEquals($character->game_class_id, $attackData['class_id']);
        $this->assertEquals([ItemType::SWORD->value], $attackData['class_weapons']);
        $this->assertEquals('Attack', $attackData['attack_type']);
        $this->assertEquals([[
            'item_id' => $sword->id,
            'item_name' => '*Sharp* Iron Sword *Flame*',
            'type' => ItemType::SWORD->value,
            'attached_affixes_count' => 2,
            'is_unique' => false,
            'is_mythic' => false,
            'is_cosmic' => false,
            'has_holy_stacks_applied' => 0,
        ]], $attackData['equipped_class_items']);
    }

    public function testBuildAttackDataContainsDamageSpellAttackTypeAndActiveSetClassItemsForHeretic(): void
    {
        $characterFactory = new CharacterFactory;
        $characterFactory
            ->createBaseCharacter([
                'name' => 'Human',
            ], [
                'name' => 'Heretic',
                'damage_stat' => 'int',
                'to_hit_stat' => 'focus',
            ], assignPassiveSkills: false)
            ->givePlayerLocation();
        $wand = $this->createItem([
            'name' => 'Ash Wand',
            'type' => ItemType::WAND->value,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);
        $character = $characterFactory
            ->inventorySetManagement()
            ->createInventorySets()
            ->putItemInSet($wand, 0, 'right-hand', true)
            ->getCharacter();

        $attackData = (new ClassAttackValue($character))->buildAttackData();

        $this->assertEquals(ClassAttackValue::HERETICS_DOUBLE_CAST, $attackData['type']);
        $this->assertEquals(ItemType::WAND->value, $attackData['only']);
        $this->assertTrue($attackData['has_item']);
        $this->assertEquals(1, $attackData['amount']);
        $this->assertEquals([
            ItemType::WAND->value,
            ItemType::STAVE->value,
            ItemType::SPELL_DAMAGE->value,
        ], $attackData['class_weapons']);
        $this->assertEquals('Cast', $attackData['attack_type']);
        $this->assertEquals([[
            'item_id' => $wand->id,
            'item_name' => 'Ash Wand',
            'type' => ItemType::WAND->value,
            'attached_affixes_count' => 0,
            'is_unique' => false,
            'is_mythic' => false,
            'is_cosmic' => false,
            'has_holy_stacks_applied' => 0,
        ]], $attackData['equipped_class_items']);
    }

    public function testBuildAttackDataContainsHealingSpellAttackTypeForProphetWithoutRequiredItem(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([
                'name' => 'Human',
            ], [
                'name' => 'Prophet',
                'damage_stat' => 'chr',
                'to_hit_stat' => 'focus',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        $attackData = (new ClassAttackValue($character))->buildAttackData();

        $this->assertEquals(ClassAttackValue::PROPHET_HEALING, $attackData['type']);
        $this->assertEquals(ItemType::CENSER->value, $attackData['only']);
        $this->assertFalse($attackData['has_item']);
        $this->assertEquals(0, $attackData['amount']);
        $this->assertEquals([
            ItemType::CENSER->value,
            ItemType::SPELL_HEALING->value,
        ], $attackData['class_weapons']);
        $this->assertEquals('Cast', $attackData['attack_type']);
        $this->assertEquals([], $attackData['equipped_class_items']);
    }

    public function testBuccaneerWithGunAndShieldHasItemTrue(): void
    {
        $characterFactory = new CharacterFactory;
        $characterFactory
            ->createBaseCharacter([
                'name' => 'Human',
            ], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation();

        $gun = $this->createItem([
            'name' => 'Old Pistol',
            'type' => ItemType::GUN->value,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);
        $shield = $this->createItem([
            'name' => 'Buckler',
            'type' => ArmourType::SHIELD->value,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);

        $character = $characterFactory
            ->inventoryManagement()
            ->giveItem($gun, true, 'right-hand')
            ->giveItem($shield, true, 'left-hand')
            ->getCharacter();

        $attackData = (new ClassAttackValue($character))->buildAttackData();

        $this->assertEquals(ClassAttackValue::BUCCANEERS_BARRAGE, $attackData['type']);
        $this->assertEquals('Gun and Shield', $attackData['only']);
        $this->assertTrue($attackData['has_item']);
        $this->assertEquals([ItemType::GUN->value], $attackData['class_weapons']);
        $this->assertEquals('Attack', $attackData['attack_type']);
    }

    public function testBuccaneerWithoutShieldHasItemFalse(): void
    {
        $characterFactory = new CharacterFactory;
        $characterFactory
            ->createBaseCharacter([
                'name' => 'Human',
            ], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation();

        $gun = $this->createItem([
            'name' => 'Old Pistol',
            'type' => ItemType::GUN->value,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);

        $character = $characterFactory
            ->inventoryManagement()
            ->giveItem($gun, true, 'right-hand')
            ->getCharacter();

        $attackData = (new ClassAttackValue($character))->buildAttackData();

        $this->assertEquals(ClassAttackValue::BUCCANEERS_BARRAGE, $attackData['type']);
        $this->assertFalse($attackData['has_item']);
    }

    public function testBuccaneerWithoutGunHasItemFalse(): void
    {
        $characterFactory = new CharacterFactory;
        $characterFactory
            ->createBaseCharacter([
                'name' => 'Human',
            ], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation();

        $shield = $this->createItem([
            'name' => 'Buckler',
            'type' => ArmourType::SHIELD->value,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);

        $character = $characterFactory
            ->inventoryManagement()
            ->giveItem($shield, true, 'left-hand')
            ->getCharacter();

        $attackData = (new ClassAttackValue($character))->buildAttackData();

        $this->assertEquals(ClassAttackValue::BUCCANEERS_BARRAGE, $attackData['type']);
        $this->assertFalse($attackData['has_item']);
    }

    public function testBeastmasterWithBowGetsDevilsPiercingShot(): void
    {
        $characterFactory = new CharacterFactory;
        $characterFactory
            ->createBaseCharacter([
                'name' => 'Human',
            ], [
                'name' => 'Beastmaster',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation();

        $bow = $this->createItem([
            'name' => 'Hunter\'s Bow',
            'type' => ItemType::BOW->value,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);

        $character = $characterFactory
            ->inventoryManagement()
            ->giveItem($bow, true, 'right-hand')
            ->getCharacter();

        $attackData = (new ClassAttackValue($character))->buildAttackData();

        $this->assertEquals(ClassAttackValue::DEVILS_PIERCING_SHOT, $attackData['type']);
        $this->assertEquals(ItemType::BOW->value, $attackData['only']);
        $this->assertTrue($attackData['has_item']);
        $this->assertEquals(1, $attackData['amount']);
        $this->assertEquals([ItemType::BOW->value, ItemType::HAMMER->value], $attackData['class_weapons']);
        $this->assertEquals('Attack', $attackData['attack_type']);
    }

    public function testBeastmasterWithHammerGetsBeastStomp(): void
    {
        $characterFactory = new CharacterFactory;
        $characterFactory
            ->createBaseCharacter([
                'name' => 'Human',
            ], [
                'name' => 'Beastmaster',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation();

        $hammer = $this->createItem([
            'name' => 'War Hammer',
            'type' => ItemType::HAMMER->value,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);

        $character = $characterFactory
            ->inventoryManagement()
            ->giveItem($hammer, true, 'right-hand')
            ->getCharacter();

        $attackData = (new ClassAttackValue($character))->buildAttackData();

        $this->assertEquals(ClassAttackValue::BEAST_STOMP, $attackData['type']);
        $this->assertEquals(ItemType::HAMMER->value, $attackData['only']);
        $this->assertTrue($attackData['has_item']);
        $this->assertEquals([ItemType::BOW->value, ItemType::HAMMER->value], $attackData['class_weapons']);
        $this->assertEquals('Attack', $attackData['attack_type']);
    }

    public function testBeastmasterWithNoWeaponHasItemFalse(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter([
                'name' => 'Human',
            ], [
                'name' => 'Beastmaster',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();

        $attackData = (new ClassAttackValue($character))->buildAttackData();

        $this->assertEquals(ClassAttackValue::BEAST_STOMP, $attackData['type']);
        $this->assertFalse($attackData['has_item']);
    }

    public function testBuccaneerWithTwoGunsGetsDualGunBarrage(): void
    {
        $characterFactory = new CharacterFactory;
        $characterFactory
            ->createBaseCharacter([
                'name' => 'Human',
            ], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ], assignPassiveSkills: false)
            ->givePlayerLocation();

        $gun1 = $this->createItem([
            'name' => 'Old Pistol',
            'type' => ItemType::GUN->value,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);
        $gun2 = $this->createItem([
            'name' => 'Navy Revolver',
            'type' => ItemType::GUN->value,
            'is_mythic' => false,
            'is_cosmic' => false,
        ]);

        $character = $characterFactory
            ->inventoryManagement()
            ->giveItem($gun1, true, 'right-hand')
            ->giveItem($gun2, true, 'left-hand')
            ->getCharacter();

        $attackData = (new ClassAttackValue($character))->buildAttackData();

        $this->assertEquals(ClassAttackValue::BUCCANEERS_DUAL_GUN_BARRAGE, $attackData['type']);
        $this->assertEquals('Two Guns', $attackData['only']);
        $this->assertTrue($attackData['has_item']);
        $this->assertEquals(2, $attackData['amount']);
        $this->assertEquals([ItemType::GUN->value], $attackData['class_weapons']);
        $this->assertEquals('Attack', $attackData['attack_type']);
    }
}
