<?php

namespace Tests\Unit\Game\Character\Builders\CharacterInformation;

use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HolyBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ReductionsBuilder;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

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

    public function testGetCharacter() {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $characterStatBuilderCharacter = $this->characterStatBuilder->setCharacter($character)->character();

        $this->assertEquals($character->name, $characterStatBuilderCharacter->name);
    }

    public function testCharacterHasNoEquippedItem() {
        $character = $this->character->getCharacter();

        $notEmpty = $this->characterStatBuilder->setCharacter($character)->fetchInventory()->isEmpty();

        $this->assertTrue($notEmpty);
    }

    public function testClassBonusWithNoSkill() {
        $character = $this->character->getCharacter();

        $value = $this->characterStatBuilder->setCharacter($character)->classBonus();

        $this->assertEquals(0, $value);
    }

    public function testClassBonusWithSkill() {
        $character = $this->character->getCharacter();

        $classGameSkill = $this->createGameSkill([
            'name'          => 'Class Skill',
            'game_class_id' => $character->game_class_id,
            'class_bonus'   => 0.01
        ]);

        $character->skills()->create([
            'character_id'        => $character->id,
            'game_skill_id'       => $classGameSkill->id,
            'currently_training'  => false,
            'is_locked'           => false,
            'level'               => 10,
            'xp'                  => 100,
            'xp_max'              => 1000,
            'xp_towards'          => 0,
            'skill_type'          => SkillTypeValue::EFFECTS_CLASS,
            'is_hidden'           => false,
        ]);

        $character = $character->refresh();

        $value = $this->characterStatBuilder->setCharacter($character)->classBonus();

        $this->assertEquals(0.1, $value);
    }

    public function testClassBonusWithSkillDoesNotGoAboveOneHundredPercent() {
        $character = $this->character->getCharacter();

        $classGameSkill = $this->createGameSkill([
            'name'          => 'Class Skill',
            'game_class_id' => $character->game_class_id,
            'class_bonus'   => 0.20
        ]);

        $character->skills()->create([
            'character_id'        => $character->id,
            'game_skill_id'       => $classGameSkill->id,
            'currently_training'  => false,
            'is_locked'           => false,
            'level'               => 10,
            'xp'                  => 100,
            'xp_max'              => 1000,
            'xp_towards'          => 0,
            'skill_type'          => SkillTypeValue::EFFECTS_CLASS,
            'is_hidden'           => false,
        ]);

        $character = $character->refresh();

        $value = $this->characterStatBuilder->setCharacter($character)->classBonus();

        $this->assertEquals(1.0, $value);
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

    public function testModdedStatShouldBeTheSameWhenIgnoreReductionsIsTrue() {
        $character = $this->character->getCharacter();

        $str       = $character->str;
        $moddedStr = $this->characterStatBuilder->setCharacter($character, true)->statMod('str');

        $this->assertEquals($str, $moddedStr);
    }

    public function testModdedStatShouldBeHalfWhenCharacterHasPurgatoryAccessAndIsOnTheIcePlane() {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::PURGATORY,
        ]))->getCharacter();

        $map = $this->createGameMap([
            'name'                       => MapNameValue::ICE_PLANE,
            'path'                       => '...',
            'default'                    => false,
            'kingdom_color'              => '#fff',
            'xp_bonus'                   => 0,
            'skill_training_bonus'       => 0,
            'drop_chance_bonus'          => 0,
            'enemy_stat_bonus'           => 0,
            'character_attack_reduction' => 0.50,
            'required_location_id'       => null
        ]);

        $character->map()->update([
            'game_map_id' => $map->id,
        ]);

        $character = $character->refresh();

        $str       = $character->str;
        $moddedStr = $this->characterStatBuilder->setCharacter($character)->statMod('str');

        $this->assertLessThan($str, $moddedStr);
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
            'last_for_minutes' => 10,
            'amount_used'      => 1,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id'      => $boonAffectsStrStat->id,
            'started'      => now(),
            'complete'     => now(),
            'last_for_minutes' => 10,
            'amount_used'      => 1,
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
            'last_for_minutes' => 10,
            'amount_used'      => 1,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id'      => $boonAffectsStrStat->id,
            'started'      => now(),
            'complete'     => now(),
            'last_for_minutes' => 10,
            'amount_used'      => 1,
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

        $this->assertEquals(101, $damage);
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

    public function testSpellDamageForCasterWithNoInventory() {

        $character = $this->character->getCharacter();

        $class = $this->createClass([
            'name' => 'Heretic',
        ]);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage('spell-damage');

        $this->assertGreaterThan(0, $damage);
    }

    public function testGetNoDamageForInvalidType() {

        $character = $this->character->equipStartingEquipment()->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildDamage('apples');

        $this->assertEquals(0, $damage);
    }

    public function testPositionalWeaponDamage() {
        $item = $this->createItem(['name' => 'sample', 'type' => 'weapon']);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('left-hand', 'sample')->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->positionalWeaponDamage('left-hand');

        $this->assertGreaterThan(0, $damage);
    }

    public function testPositionalWeaponDamageWithEmptyInventory() {

        $character = $this->character->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->positionalWeaponDamage('left-hand');

        $this->assertGreaterThan(0, $damage);
    }

    public function testPositionalSpellDamage() {
        $item = $this->createItem(['name' => 'sample', 'type' => 'spell-damage', 'base_damage' => 100]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-two', 'sample')->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->positionalSpellDamage('spell-two');

        $this->assertGreaterThan(0, $damage);
    }

    public function testPositionalSpellDamageWithEmptyInventory() {

        $character = $this->character->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->positionalSpellDamage('spell-one');

        $this->assertEquals(0, $damage);
    }

    public function testPositionalSpellDamageWithEmptyInventoryAsCaster() {

        $character = $this->character->getCharacter();

        $class = $this->createClass([
            'name' => 'Heretic'
        ]);

        $character->update([
            'game_class_id' => $class->id
        ]);

        $character = $character->refresh();

        $damage = $this->characterStatBuilder->setCharacter($character)->positionalSpellDamage('spell-one');

        $this->assertGreaterThan(0, $damage);
    }

    public function testGetPositionalHealing() {
        $item = $this->createItem(['name' => 'sample', 'type' => 'spell-healing', 'base_healing' => 100]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'sample')->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->positionalHealing('spell-one');

        $this->assertGreaterThan(0, $damage);
    }

    public function testGetPositionalHealingWhenClassIsCleric() {
        $cleric = $this->createClass(['name' => CharacterClassValue::CLERIC]);

        $item = $this->createItem(['name' => 'sample', 'type' => 'spell-healing', 'base_healing' => 100]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'sample')->getCharacter();

        $character->update(['game_class_id' => $cleric->id]);

        $character = $character->refresh();

        $damage = $this->characterStatBuilder->setCharacter($character)->positionalHealing('spell-one');

        $this->assertGreaterThan(0, $damage);
    }

    public function testGetPositionalHealingWithEmptyInventory() {

        $character = $this->character->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->positionalHealing('spell-two');

        $this->assertEquals(0, $damage);
    }

    public function testGetPositionalHealingWithEmptyInventoryAsProphet() {

        $character = $this->character->getCharacter();

        $class = $this->createClass([
            'name' => 'Prophet'
        ]);

        $character->update([
            'game_class_id' => $class->id
        ]);

        $character = $character->refresh();

        $damage = $this->characterStatBuilder->setCharacter($character)->positionalHealing('spell-two');

        $this->assertGreaterThan(0, $damage);
    }

    public function testHealingWithOutSkill() {

        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'chr_mod' => 0.15,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'chr_mod' => 0.15,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();

        $class = $this->createClass([
            'name' => 'Vampire',
        ]);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $healing = $this->characterStatBuilder->setCharacter($character)->buildHealing();

        $this->assertGreaterThan(100, $healing);
    }

    public function testHealingWithOutSkillVoided() {

        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'chr_mod' => 0.15,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'chr_mod' => 0.15,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();

        $class = $this->createClass([
            'name' => 'Vampire',
        ]);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $healing = $this->characterStatBuilder->setCharacter($character)->buildHealing(true);

        $this->assertEquals(100, $healing);
    }

    public function testHealingWithSkill() {

        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'chr_mod' => 0.15,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'chr_mod' => 0.15,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $skill = $this->createGameSkill([
            'name'                             => 'Healer Skill',
            'base_healing_mod_bonus_per_level' => 0.1
        ]);

        $character = $this->character->assignSkill($skill, 10)->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();

        $class = $this->createClass([
            'name' => 'Prophet',
        ]);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildHealing();

        $this->assertGreaterThan(100, $damage);
    }

    public function testHealingWithNoEquipment() {

        $character = $this->character->getCharacter();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildHealing();

        $this->assertEquals(0, $damage);
    }

    public function testHealingWithNoEquipmentAsAProphet() {

        $character = $this->character->getCharacter();

        $class = $this->createClass(['name' => 'Prophet']);

        $character->update([
            'game_class_id' => $class->id,
        ]);

        $character = $character->refresh();

        $damage = $this->characterStatBuilder->setCharacter($character)->buildHealing();

        $this->assertGreaterThan(0, $damage);
    }

    public function testDevouringWithOnlyQuestItem() {
        $item = $this->createItem([
            'name'            => 'weapon',
            'type'            => 'quest',
            'devouring_light' => 0.20
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $devouring = $this->characterStatBuilder->setCharacter($character)->buildDevouring('devouring_light');

        $this->assertEquals(.20, $devouring);
    }

    public function testDevouringWithOnlyQuestItemInPurgatory() {
        $item = $this->createItem([
            'name'            => 'weapon',
            'type'            => 'quest',
            'devouring_light' => 0.65
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $map = $this->createGameMap(['name' => 'Purgatory']);

        $character->map()->update(['game_map_id' => $map->id]);

        $character = $character->refresh(0);

        $devouring = $this->characterStatBuilder->setCharacter($character)->buildDevouring('devouring_light');

        $this->assertEquals(.20, $devouring);
    }

    public function testDevouringWithAffixes() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'chr_mod' => 0.15,
            'devouring_light' => 1.10,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'chr_mod' => 0.15,
            'devouring_light' => 0.10,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();

        $devouring = $this->characterStatBuilder->setCharacter($character)->buildDevouring('devouring_light');

        $this->assertEquals(1.0, $devouring);
    }

    public function testDevouringWithAffixesInPurgatory() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'chr_mod' => 0.15,
            'devouring_light' => 1.10,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'    => 'Sample',
            'chr_mod' => 0.15,
            'devouring_light' => 0.10,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $map = $this->createGameMap(['name' => 'Purgatory']);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();

        $character->map()->update(['game_map_id' => $map->id]);

        $character = $character->refresh();

        $devouring = $this->characterStatBuilder->setCharacter($character)->buildDevouring('devouring_light');

        $this->assertEquals(.65, $devouring);
    }

    public function testResurrectionChanceWithNoItems() {
        $character = $this->character->getCharacter();

        $resChance = $this->characterStatBuilder->setCharacter($character)->buildResurrectionChance();

        $this->assertEquals(0, $resChance);
    }

    public function testResurrectionChanceWithItems() {
        $item = $this->createItem([
            'name'                => 'weapon',
            'type'                => 'spell-healing',
            'base_healing'        => 100,
            'resurrection_chance' => 1.0,
        ]);

        $character = $this->character->inventoryManagement()
            ->giveItem($item)
            ->equipItem('spell-one', 'weapon')
            ->getCharacter();

        $resChance = $this->characterStatBuilder->setCharacter($character)->buildResurrectionChance();

        $this->assertEquals(1.0, $resChance);
    }

    public function testResurrectionChanceWithItemsAsProphet() {
        $item = $this->createItem([
            'name'                => 'weapon',
            'type'                => 'spell-healing',
            'base_healing'        => 100,
            'resurrection_chance' => 1.0,
        ]);

        $character = $this->character->inventoryManagement()
            ->giveItem($item)
            ->equipItem('spell-one', 'weapon')
            ->getCharacter();

        $class = $this->createClass(['name' => 'Prophet']);

        $character->update([
            'game_class_id' => $class->id
        ]);

        $character = $character->refresh();

        $resChance = $this->characterStatBuilder->setCharacter($character)->buildResurrectionChance();

        $this->assertEquals(1.05, $resChance);
    }

    public function testResurrectionChanceWithItemsAsProphetInPurgatory() {
        $item = $this->createItem([
            'name'                => 'weapon',
            'type'                => 'spell-healing',
            'base_healing'        => 100,
            'resurrection_chance' => 1.0,
        ]);

        $character = $this->character->inventoryManagement()
            ->giveItem($item)
            ->equipItem('spell-one', 'weapon')
            ->getCharacter();

        $class = $this->createClass(['name' => 'Prophet']);

        $character->update([
            'game_class_id' => $class->id
        ]);

        $character = $character->refresh();

        $map = $this->createGameMap(['name' => 'Purgatory']);

        $character->map()->update(['game_map_id' => $map->id]);

        $character = $character->refresh();

        $resChance = $this->characterStatBuilder->setCharacter($character)->buildResurrectionChance();

        $this->assertEquals(.65, $resChance);
    }

    public function testResurrectionChanceWithItemsInPurgatory() {
        $item = $this->createItem([
            'name'                => 'weapon',
            'type'                => 'spell-healing',
            'base_healing'        => 100,
            'resurrection_chance' => 1.0,
        ]);

        $character = $this->character->inventoryManagement()
            ->giveItem($item)
            ->equipItem('spell-one', 'weapon')
            ->getCharacter();

        $map = $this->createGameMap(['name' => 'Purgatory']);

        $character->map()->update(['game_map_id' => $map->id]);

        $character = $character->refresh();

        $resChance = $this->characterStatBuilder->setCharacter($character)->buildResurrectionChance();

        $this->assertEquals(.45, $resChance);
    }

    public function testBuildAffixStackingDamage() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'             => 'Sample',
            'chr_mod'          => 0.15,
            'damage_amount'    => 1.0,
            'damage_can_stack' => true,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'             => 'Sample',
            'chr_mod'          => 0.15,
            'damage_amount'    => 1.5,
            'damage_can_stack' => true,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'spell-one')->getCharacter();
        $damage   = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('affix-stacking-damage');

        $this->assertEquals(2.5, $damage);
    }

    public function testBuildAffixStackingDamageVoided() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'             => 'Sample',
            'chr_mod'          => 0.15,
            'damage_amount'           => 1.0,
            'damage_can_stack' => true,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'             => 'Sample',
            'chr_mod'          => 0.15,
            'damage_amount'           => 1.5,
            'damage_can_stack' => true,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();
        $damage   = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('affix-stacking-damage', true);

        $this->assertEquals(0, $damage);
    }

    public function testBuildAffixNonStackingDamage() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'             => 'Sample',
            'chr_mod'          => 0.15,
            'damage_amount'    => 1.0,
            'damage_can_stack' => false,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'             => 'Sample',
            'chr_mod'          => 0.15,
            'damage_amount'    => 1.5,
            'damage_can_stack' => false,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();
        $damage   = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('affix-non-stacking');

        $this->assertEquals(1.5, $damage);
    }

    public function testBuildAffixNonStackingDamageNoEnchantments() {
        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();
        $damage   = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('affix-non-stacking');

        $this->assertEquals(0, $damage);
    }

    public function testBuildAffixNonStackingDamageVoided() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'             => 'Sample',
            'chr_mod'          => 0.15,
            'damage_amount'           => 1.0,
            'damage_can_stack' => false,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'             => 'Sample',
            'chr_mod'          => 0.15,
            'damage_amount'           => 1.5,
            'damage_can_stack' => false,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();
        $damage   = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('affix-non-stacking', true);

        $this->assertEquals(0, $damage);
    }

    public function testBuildAffixLifeStealingNonStacking() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.0,
            'steal_life_amount'   => 1.0,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.50,
            'steal_life_amount'   => .10,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();
        $amount    = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('life-stealing');

        $this->assertEquals(.99, $amount);
    }

    public function testBuildAffixLifeStealingNonStackingWithNoEnchantments() {
        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();
        $amount    = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('life-stealing');

        $this->assertEquals(0, $amount);
    }

    public function testBuildAffixLifeStealingVoided() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.0,
            'steal_life_amount'   => 1.0,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.50,
            'steal_life_amount'   => .10,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();
        $amount    = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('life-stealing', true);

        $this->assertEquals(0, $amount);
    }

    public function testBuildAffixLifeStealingVampire() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.0,
            'steal_life_amount'   => 1.0,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.50,
            'steal_life_amount'   => .10,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();

        $class     = $this->createClass(['name' => 'Vampire']);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $amount    = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('life-stealing');

        $this->assertEquals(.99, $amount);
    }

    public function testBuildAffixLifeStealingVampireInPurgatory() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.0,
            'steal_life_amount'   => 1.0,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'       => 1.50,
            'steal_life_amount'   => .10,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();

        $class     = $this->createClass(['name' => 'Vampire']);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $map       = $this->createGameMap(['name' => 'Purgatory']);

        $character->map()->update(['game_map_id' => $map->id]);

        $character = $character->refresh();

        $amount    = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('life-stealing');

        $this->assertEquals((.99 - (.99 * .20)), $amount);
    }

    public function testBuildAffixLifeStealingVampireInHell() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.0,
            'steal_life_amount'   => 1.0,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'       => 1.50,
            'steal_life_amount'   => .10,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();

        $class     = $this->createClass(['name' => 'Vampire']);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $map       = $this->createGameMap(['name' => MapNameValue::HELL]);

        $character->map()->update(['game_map_id' => $map->id]);

        $character = $character->refresh();

        $amount    = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('life-stealing');

        $this->assertEquals((.99 - (.99 * .10)), $amount);
    }

    public function testBuildAffixLifeStealingVampireInTwistedMemories() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.0,
            'steal_life_amount'   => 1.0,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'       => 1.50,
            'steal_life_amount'   => .10,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();

        $class     = $this->createClass(['name' => 'Vampire']);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $map       = $this->createGameMap(['name' => MapNameValue::TWISTED_MEMORIES]);

        $character->map()->update(['game_map_id' => $map->id]);

        $character = $character->refresh();

        $amount    = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('life-stealing');

        $this->assertEquals((.99 - (.99 * .25)), $amount);
    }

    public function testBuildAffixLifeStealingVampireInEventMapIcePlaneWithAccessToPurgatory() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'       => 1.0,
            'steal_life_amount'   => 1.0,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'       => 1.50,
            'steal_life_amount'   => .10,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $questItem = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::PURGATORY,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->giveItem($questItem)->getCharacter();

        $class     = $this->createClass(['name' => 'Vampire']);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $map       = $this->createGameMap(['name' => MapNameValue::ICE_PLANE]);

        $character->map()->update(['game_map_id' => $map->id]);

        $character = $character->refresh();

        $amount    = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('life-stealing');

        $this->assertEquals((.99 - (.99 * .20)), $amount);
    }

    public function testBuildAffixLifeStealingVampireInEventMapDelusionalMemoriesWithAccessToPurgatory() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.0,
            'steal_life_amount'   => 1.0,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'       => 1.50,
            'steal_life_amount'   => .10,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $questItem = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::PURGATORY,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->giveItem($questItem)->getCharacter();

        $class     = $this->createClass(['name' => 'Vampire']);

        $character->update(['game_class_id' => $class->id]);

        $character = $character->refresh();

        $map       = $this->createGameMap(['name' => MapNameValue::DELUSIONAL_MEMORIES]);

        $character->map()->update(['game_map_id' => $map->id]);

        $character = $character->refresh();

        $amount    = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('life-stealing');

        $this->assertEquals((.99 - (.99 * .30)), $amount);
    }

    public function testBuildInvalidAffixDamage() {

        $character = $this->character->getCharacter();

        $amount    = $this->characterStatBuilder->setCharacter($character)->buildAffixDamage('apples');

        $this->assertEquals(0, $amount);
    }

    public function testEntrancingChangeWithNoInventory() {
        $character = $this->character->getCharacter();

        $amount    = $this->characterStatBuilder->setCharacter($character)->buildEntrancingChance();

        $this->assertEquals(0, $amount);
    }

    public function testEntrancingChanceWithInventory() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.0,
            'entranced_chance'    => 1.0,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.50,
            'entranced_chance'    => .10,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();
        $amount    = $this->characterStatBuilder->setCharacter($character)->buildEntrancingChance();

        $this->assertEquals(1, $amount);
    }

    public function testGetStatReducingAffix() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.0,
            'reduces_enemy_stats' => true,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'                => 'Sample II',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.50,
            'reduces_enemy_stats' => true,
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();
        $affix    = $this->characterStatBuilder->setCharacter($character)->getStatReducingPrefix();

        $this->assertEquals('Sample', $affix->name);
    }

    public function testGetNoStatReducingAffixForNoInventory() {
        $character = $this->character->getCharacter();

        $affix     = $this->characterStatBuilder->setCharacter($character)->getStatReducingPrefix();

        $this->assertNull($affix);
    }

    public function testGetNoStatReducingAffixForNoSuchAffix() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.0,
            'type'                => 'prefix',
            'reduces_enemy_stats' => false,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'                => 'Sample II',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.50,
            'reduces_enemy_stats' => false,
            'type'                => 'suffix',
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();
        $affix    = $this->characterStatBuilder->setCharacter($character)->getStatReducingPrefix();

        $this->assertNull($affix);
    }

    public function testGetStatReducingAffixForSuffixes() {
        $itemPrefixAffix = $this->createItemAffix([
            'name'                => 'Sample',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.0,
            'type'                => 'prefix',
            'reduces_enemy_stats' => true,
        ]);

        $itemSuffixAffix = $this->createItemAffix([
            'name'                => 'Sample II',
            'chr_mod'             => 0.15,
            'damage_amount'              => 1.50,
            'reduces_enemy_stats' => true,
            'type'                => 'suffix',
        ]);

        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'spell-healing',
            'item_suffix_id' => $itemSuffixAffix->id,
            'item_prefix_id' => $itemPrefixAffix->id,
            'base_healing'   => 100
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('spell-one', 'weapon')->getCharacter();
        $affixes   = $this->characterStatBuilder->setCharacter($character)->getStatReducingSuffixes();

        $this->assertNotEmpty($affixes);
    }

    public function testGetNoStatReducingAffixForSuffixes() {

        $character = $this->character->inventoryManagement()->getCharacter();
        $affixes   = $this->characterStatBuilder->setCharacter($character)->getStatReducingSuffixes();

        $this->assertEmpty($affixes);
    }

    public function testGetAmbushChance() {
        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'trinket',
            'ambush_chance'  => 0.15
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('trinket-one', 'weapon')->getCharacter();
        $chance    = $this->characterStatBuilder->setCharacter($character)->buildAmbush();

        $this->assertEquals(0.15, $chance);
    }

    public function testGetAmbushResistance() {
        $item = $this->createItem([
            'name'               => 'weapon',
            'type'               => 'trinket',
            'ambush_resistance'  => 0.15
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('trinket-one', 'weapon')->getCharacter();
        $chance    = $this->characterStatBuilder->setCharacter($character)->buildAmbush('resistance');

        $this->assertEquals(0.15, $chance);
    }

    public function testGetNoAmbushInfoForNoInventory() {
        $character = $this->character->inventoryManagement()->getCharacter();
        $amount    = $this->characterStatBuilder->setCharacter($character)->buildAmbush();

        $this->assertEquals(0, $amount);
    }

    public function testGetCounterChance() {
        $item = $this->createItem([
            'name'           => 'weapon',
            'type'           => 'trinket',
            'counter_chance' => 0.15
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('trinket-one', 'weapon')->getCharacter();
        $chance    = $this->characterStatBuilder->setCharacter($character)->buildCounter();

        $this->assertEquals(0.15, $chance);
    }

    public function testGetCounterResistance() {
        $item = $this->createItem([
            'name'               => 'weapon',
            'type'               => 'trinket',
            'counter_resistance' => 0.15
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->equipItem('trinket-one', 'weapon')->getCharacter();
        $chance    = $this->characterStatBuilder->setCharacter($character)->buildCounter('resistance');

        $this->assertEquals(0.15, $chance);
    }

    public function testGetNoCounterInfoForNoInventory() {
        $character = $this->character->getCharacter();
        $amount    = $this->characterStatBuilder->setCharacter($character)->buildCounter();

        $this->assertEquals(0, $amount);
    }

    public function testGetFightTimeOutModifier() {
        $gameSkill = $this->createGameSkill([
            'fight_time_out_mod_bonus_per_level' => 0.01,
        ]);

        $character    = $this->character->assignSkill($gameSkill, 25)->getCharacter();
        $fightTimeOut = $this->characterStatBuilder->setCharacter($character)->buildTimeOutModifier('fight_time_out');

        $this->assertEquals(0.25, $fightTimeOut);
    }

    public function testGetMovementOutModifier() {
        $gameSkill = $this->createGameSkill([
            'move_time_out_mod_bonus_per_level'  => 0.01,
        ]);

        $character       = $this->character->assignSkill($gameSkill, 25)->getCharacter();
        $movementTimeOut = $this->characterStatBuilder->setCharacter($character)->buildTimeOutModifier('move_time_out');

        $this->assertEquals(0.25, $movementTimeOut);
    }

    public function testWeaponDamageForAlcoholicShouldBeLowerThenFighter() {
        $alcoholic = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name'        => CharacterClassValue::ALCOHOLIC,
            'damage_stat' => 'str'
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01
            ]),
            5
        )->givePlayerLocation()
            ->equipStartingEquipment()
            ->getCharacter();

        $fighter = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name'        => CharacterClassValue::FIGHTER,
            'damage_stat' => 'str'
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01
            ]),
            5
        )->givePlayerLocation()
            ->equipStartingEquipment()
            ->getCharacter();

        $fighterDamage   = $this->characterStatBuilder->setCharacter($fighter)->buildDamage('weapon');
        $alcoholicDamage = $this->characterStatBuilder->setCharacter($alcoholic)->buildDamage('weapon');

        $this->assertGreaterThan($alcoholicDamage, $fighterDamage);
    }

    public function testWeaponDamageForAlcoholicShouldBeHigherThenFighterWhenNoWeaponsEquipped() {
        $alcoholic = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name'        => CharacterClassValue::ALCOHOLIC,
            'damage_stat' => 'str'
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01
            ]),
            5
        )->givePlayerLocation()
            ->levelCharacterUp(25)
            ->getCharacter();

        $fighter = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name'        => CharacterClassValue::FIGHTER,
            'damage_stat' => 'str'
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01
            ]),
            5
        )->givePlayerLocation()
            ->levelCharacterUp(25)
            ->getCharacter();

        $fighterDamage   = $this->characterStatBuilder->setCharacter($fighter)->buildDamage('weapon');
        $alcoholicDamage = $this->characterStatBuilder->setCharacter($alcoholic)->buildDamage('weapon');

        $this->assertGreaterThan($fighterDamage, $alcoholicDamage);
    }

    public function testSpellDamageShouldBeHalfDamageForAlcoHolics() {
        $alcoholic = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name'        => CharacterClassValue::ALCOHOLIC,
            'damage_stat' => 'str'
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01
            ]),
            5
        )->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem(
                $this->createItem(['type' => 'spell-damage']),
                true,
                'spell-one'
            )
            ->giveItem(
                $this->createItem(['type' => 'spell-damage']),
                true,
                'spell-two'
            )
            ->getCharacter();

        $fighter = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name'        => CharacterClassValue::FIGHTER,
            'damage_stat' => 'str'
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01
            ]),
            5
        )->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem(
                $this->createItem(['type' => 'spell-damage']),
                true,
                'spell-one'
            )
            ->giveItem(
                $this->createItem(['type' => 'spell-damage']),
                true,
                'spell-two'
            )
            ->getCharacter();

        $fighterDamage   = $this->characterStatBuilder->setCharacter($fighter)->buildDamage('spell-damage');
        $alcoholicDamage = $this->characterStatBuilder->setCharacter($alcoholic)->buildDamage('spell-damage');

        $this->assertGreaterThan($alcoholicDamage, $fighterDamage);
    }

    public function testSpellHealingShouldBeHalfDamageForAlcoHolics() {
        $alcoholic = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name'        => CharacterClassValue::ALCOHOLIC,
            'damage_stat' => 'str'
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01
            ]),
            5
        )->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem(
                $this->createItem(['type' => 'spell-healing', 'base_healing' => 10]),
                true,
                'spell-one'
            )
            ->giveItem(
                $this->createItem(['type' => 'spell-healing', 'base_healing' => 10]),
                true,
                'spell-two'
            )
            ->getCharacter();

        $fighter = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name'        => CharacterClassValue::FIGHTER,
            'damage_stat' => 'str'
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01
            ]),
            5
        )->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem(
                $this->createItem(['type' => 'spell-healing', 'base_healing' => 10]),
                true,
                'spell-one'
            )
            ->giveItem(
                $this->createItem(['type' => 'spell-healing', 'base_healing' => 10]),
                true,
                'spell-two'
            )
            ->getCharacter();

        $fighterDamage   = $this->characterStatBuilder->setCharacter($fighter)->buildHealing();
        $alcoholicDamage = $this->characterStatBuilder->setCharacter($alcoholic)->buildHealing();

        $this->assertGreaterThan($alcoholicDamage, $fighterDamage);
    }

    public function testArcaneAlchemistHealingBonusIsLessThenProphets() {
        $arcaneAlchemist = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name'        => CharacterClassValue::ARCANE_ALCHEMIST,
            'damage_stat' => 'str'
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01
            ]),
            5
        )->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem(
                $this->createItem(['type' => 'spell-healing']),
                true,
                'spell-one'
            )
            ->giveItem(
                $this->createItem(['type' => 'spell-healing']),
                true,
                'spell-two'
            )
            ->getCharacter();

        $prophet = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name'        => CharacterClassValue::PROPHET,
            'damage_stat' => 'str'
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01
            ]),
            5
        )->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem(
                $this->createItem(['type' => 'spell-healing']),
                true,
                'spell-one'
            )
            ->giveItem(
                $this->createItem(['type' => 'spell-healing']),
                true,
                'spell-two'
            )
            ->getCharacter();

        $arcaneHealing  = $this->characterStatBuilder->setCharacter($arcaneAlchemist)->buildHealing();
        $prophetHealing = $this->characterStatBuilder->setCharacter($prophet)->buildHealing();

        $this->assertGreaterThan($arcaneHealing, $prophetHealing);
    }

    public function testRangerHealingBonusIsLessThenProphets() {
        $ranger = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name'        => CharacterClassValue::RANGER,
            'damage_stat' => 'str'
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01
            ]),
            5
        )->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem(
                $this->createItem(['type' => 'spell-healing']),
                true,
                'spell-one'
            )
            ->giveItem(
                $this->createItem(['type' => 'spell-healing']),
                true,
                'spell-two'
            )
            ->getCharacter();

        $prophet = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name'        => CharacterClassValue::PROPHET,
            'damage_stat' => 'str'
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01
            ]),
            5
        )->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem(
                $this->createItem(['type' => 'spell-healing']),
                true,
                'spell-one'
            )
            ->giveItem(
                $this->createItem(['type' => 'spell-healing']),
                true,
                'spell-two'
            )
            ->getCharacter();

        $rangerHealing  = $this->characterStatBuilder->setCharacter($ranger)->buildHealing();
        $prophetHealing = $this->characterStatBuilder->setCharacter($prophet)->buildHealing();

        $this->assertGreaterThan($rangerHealing, $prophetHealing);
    }

    public function testNoResistanceReductionForCharacterWhenCharacterIsVoided() {
        $character = $this->character->getCharacter();

        $resistance = $this->characterStatBuilder->setCharacter($character)->buildResistanceReductionChance(true);

        $this->assertEquals(0, $resistance);
    }

    public function testCharacterHasResistanceReductionOnPrefixBasedItems() {
        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'item_prefix_id' => $this->createItemAffix([
                    'resistance_reduction' => 0.10
                ])->id,
                'type' => 'weapon'
            ]),
            true,
            'left_hand'
        )->getCharacter();

        $resistance = $this->characterStatBuilder->setCharacter($character)->buildResistanceReductionChance();

        $this->assertEquals(0.10, $resistance);
    }

    public function testCharacterHasResistanceReductionOnPrefixBasedItemsThatDoesNotGoAboveOneHundredPercent() {
        $character = $this->character->inventoryManagement()->giveItem(
            $this->createItem([
                'item_prefix_id' => $this->createItemAffix([
                    'resistance_reduction' => 1.10
                ])->id,
                'type' => 'weapon'
            ]),
            true,
            'left_hand'
        )->getCharacter();

        $resistance = $this->characterStatBuilder->setCharacter($character)->buildResistanceReductionChance();

        $this->assertEquals(1, $resistance);
    }
}
