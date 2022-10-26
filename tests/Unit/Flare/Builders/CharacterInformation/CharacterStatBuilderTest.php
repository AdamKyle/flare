<?php

namespace Tests\Unit\Flare\Builders\CharacterInformation;

use App\Flare\Builders\CharacterInformation\AttributeBuilders\HolyBuilder;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\ReductionsBuilder;
use App\Flare\Values\ItemEffectsValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateSkill;

class CharacterStatBuilderTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateItemAffix, CreateGameMap, CreateClass, CreateGameSkill;

    private ?CharacterFactory $character;

    private ?CharacterStatBuilder $characterStatBuilder;

    public function setUp(): void {
        parent::setUp();

        $this->character            = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $this->characterStatBuilder = resolve(CharacterStatBuilder::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character            = null;
        $this->characterStatBuilder = null;
    }

    public function testCharacterHasEquippeditems() {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $notEmpty = $this->characterStatBuilder->setCharacter($character)->fetchInventory()->isNotempty();

        $this->assertTrue($notEmpty);
    }

    public function testCharacterHasNoEquippedItem() {
        $character = $this->character->getCharacter();

        $notEmpty = $this->characterStatBuilder->setCharacter($character)->fetchInventory()->isEmpty();

        $this->assertTrue($notEmpty);
    }

    public function testClassBonusForEquippedItems() {
        $itemAffix = $this->createItemAffix([
            'name' => 'Sample',
            'class_bonus' => 1.0
        ]);

        $item = $this->createItem([
            'name'           => 'Powerful item',
            'item_suffix_id' => $itemAffix->id,
            'type'           => 'weapon',
        ]);

        $character = $this->character->inventoryManagement()
                                     ->giveItem($item)
                                     ->equipItem('left_hand', 'Powerful item')
                                     ->getCharacter();

        $value = $this->characterStatBuilder->setCharacter($character)->classBonus();

        $this->assertEquals(1.0, $value);
    }

    public function testClassBonusForEquippedItemsCannotBeHigherThenOneHundredPercent() {
        $itemAffix = $this->createItemAffix([
            'name' => 'Sample',
            'class_bonus' => 2.0
        ]);

        $item = $this->createItem([
            'name'           => 'Powerful item',
            'item_suffix_id' => $itemAffix->id,
            'type'           => 'weapon',
        ]);

        $character = $this->character->inventoryManagement()
            ->giveItem($item)
            ->equipItem('left_hand', 'Powerful item')
            ->getCharacter();

        $value = $this->characterStatBuilder->setCharacter($character)->classBonus();

        $this->assertEquals(1.0, $value);
    }

    public function testClassBonusWithNoItemsEquipped() {
        $character = $this->character->getCharacter();

        $value = $this->characterStatBuilder->setCharacter($character)->classBonus();

        $this->assertEquals(0, $value);
    }

    public function testGetHolyInfo() {
        $character = $this->character->getCharacter();

        $value = $this->characterStatBuilder->setCharacter($character)->holyInfo();

        $this->assertInstanceOf(HolyBuilder::class, $value);
    }

    public function testGetReductionInfo() {
        $character = $this->character->getCharacter();

        $value = $this->characterStatBuilder->setCharacter($character)->reductionInfo();

        $this->assertInstanceOf(ReductionsBuilder::class, $value);
    }

    public function testAffixesCantBeResisted() {
        $character = $this->character->getCharacter();

        $canBeResisted = $this->characterStatBuilder->setCharacter($character)->canAffixesBeResisted();

        $this->assertFalse($canBeResisted);
    }

    public function testAffixesCannotBeResisted() {

        $item = $this->createItem([
            'type'   => 'quest',
            'effect' => ItemEffectsValue::AFFIXES_IRRESISTIBLE,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $canBeResisted = $this->characterStatBuilder->setCharacter($character)->canAffixesBeResisted();

        $this->assertTrue($canBeResisted);
    }

    public function testModdedStatShouldBeTheSame() {
        $character = $this->character->getCharacter();

        $str       = $character->str;
        $moddedStr = $this->characterStatBuilder->setCharacter($character)->statMod('str');

        $this->assertEquals($str, $moddedStr);
    }

    public function testModdedStatShouldBeHigher() {

        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $item = $this->createItem([
            'name' => 'weapon',
            'type' => 'weapon',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('left-hand', 'weapon')->getCharacter();

        $str       = $character->str;
        $moddedStr = $this->characterStatBuilder->setCharacter($character)->statMod('str');

        $this->assertEquals(($str + $str * 0.30), $moddedStr);
    }

    public function testModdedStatShouldBeHigherEvenVoided() {

        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $item = $this->createItem([
            'name' => 'weapon',
            'type' => 'weapon',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'str_mod'        => 0.15
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('left-hand', 'weapon')->getCharacter();

        $str       = $character->str;
        $moddedStr = $this->characterStatBuilder->setCharacter($character)->statMod('str', true);

        $this->assertEquals(($str + $str * 0.15), $moddedStr);
    }

    public function testModdedStatIsStillHigherThenRegularStatWhenOnStatReducingPlane() {

        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $item = $this->createItem([
            'name' => 'weapon',
            'type' => 'weapon',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
        ]);

        $map = $this->createGameMap([
            'name'                       => 'Hell',
            'path'                       => '...',
            'default'                    => false,
            'kingdom_color'              => '#fff',
            'xp_bonus'                   => 0,
            'skill_training_bonus'       => 0,
            'drop_chance_bonus'          => 0,
            'enemy_stat_bonus'           => 0,
            'character_attack_reduction' => 0.05,
            'required_location_id'       => null
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('left-hand', 'weapon')->getCharacter();

        $character->map()->update([
            'game_map_id' => $map->id,
        ]);

        $character = $character->refresh();

        $str       = $character->str;
        $moddedStr = $this->characterStatBuilder->setCharacter($character)->statMod('str');

        $this->assertGreaterThan($str, $moddedStr);
    }

    public function testModdedStatShouldBeHigherWithBoonsAndEquipment() {

        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $item = $this->createItem([
            'name' => 'weapon',
            'type' => 'weapon',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('left-hand', 'weapon')->getCharacter();

        $str       = $character->str;

        $boonAffectsAllStats = $this->createItem([
            'name'          => 'boon 1',
            'stat_increase' => 0.15
        ]);

        $boonAffectsStrStat = $this->createItem([
            'name'          => 'boon 2',
            'str_mod'       => 0.15
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id'      => $boonAffectsAllStats->id,
            'started'      => now(),
            'complete'     => now(),
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id'      => $boonAffectsStrStat->id,
            'started'      => now(),
            'complete'     => now(),
        ]);

        $moddedStr = $this->characterStatBuilder->setCharacter($character)->statMod('str');

        $this->assertGreaterThan($str, $moddedStr);
    }

    public function testModdedStatShouldBeHigherWithOnlyBoons() {

        $character = $this->character->getCharacter();

        $str       = $character->str;

        $boonAffectsAllStats = $this->createItem([
            'name'          => 'boon 1',
            'stat_increase' => 0.15
        ]);

        $boonAffectsStrStat = $this->createItem([
            'name'          => 'boon 2',
            'str_mod'       => 0.15
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id'      => $boonAffectsAllStats->id,
            'started'      => now(),
            'complete'     => now(),
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id'      => $boonAffectsStrStat->id,
            'started'      => now(),
            'complete'     => now(),
        ]);

        $moddedStr = $this->characterStatBuilder->setCharacter($character)->statMod('str');

        $this->assertGreaterThan($str, $moddedStr);
    }

    public function testWeaponDamageWithOutSkill() {

        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'weapon',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_damage'    => 100,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('left-hand', 'weapon')->getCharacter();

        $class = $this->createClass([
            'name' => 'Vampire',
        ]);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage('weapon');

        $this->assertGreaterThan(100, $damage);
    }

    public function testWeaponDamageWithOutSkillVoided() {

        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'weapon',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_damage'    => 100,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('left-hand', 'weapon')->getCharacter();

        $class = $this->createClass([
            'name' => 'Heretic',
        ]);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage('weapon', true);

        $this->assertGreaterThan(100, $damage);
    }

    public function testWeaponDamageWithSkill() {

        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $item = $this->createItem([
            'name' => 'weapon',
            'type' => 'weapon',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_damage'    => 100
        ]);

        $skill = $this->createGameSkill([
            'name'                            => 'Fighter Skill',
            'base_damage_mod_bonus_per_level' => 0.1
        ]);

        $character = $this->character->assignSkill($skill, 10)->inventoryManagement()->giveItem($item)->equipItem('left-hand', 'weapon')->getCharacter();

        $class = $this->createClass([
            'name' => 'Fighter',
        ]);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage('weapon');

        $this->assertGreaterThan(100, $damage);
    }

    public function testBuildRingDamage() {

        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $item = $this->createItem([
            'name' => 'ring',
            'type' => 'ring',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_damage'    => 1000
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('ring-one', 'ring')->getCharacter();


        $character = $character->refresh();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage('ring');

        $this->assertEquals(1000, $damage);
    }

    public function testSpellDamageWithOutSkill() {

        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-damage',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_damage'    => 100,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-once', 'weapon')->getCharacter();

        $class = $this->createClass([
            'name' => 'Vampire',
        ]);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage('spell-damage');

        $this->assertGreaterThan(100, $damage);
    }

    public function testSpellDamageWithOutSkillVoided() {

        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-damage',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_damage'    => 100,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();

        $class = $this->createClass([
            'name' => 'Fighter',
        ]);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage('spell-damage', true);

        $this->assertEquals(100, $damage);
    }

    public function testSpellDamageWithSkill() {

        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'str_mod' => 0.15,
        ]);

        $item = $this->createItem([
            'name' => 'weapon',
            'type' => 'spell-damage',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_damage'    => 100
        ]);

        $skill = $this->createGameSkill([
            'name'                            => 'Heretic Skill',
            'base_damage_mod_bonus_per_level' => 0.1
        ]);

        $character = $this->character->assignSkill($skill, 10)->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();

        $class = $this->createClass([
            'name' => 'Heretic',
        ]);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage('spell-damage');

        $this->assertGreaterThan(100, $damage);
    }

    public function testGetNoDamageForInvalidType() {

        $character = $this->character->equipStartingEquipment()->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage('apples');

        $this->assertEquals(0, $damage);
    }
}
