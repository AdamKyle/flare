<?php

namespace Tests\Unit\Game\Core\Comparisons;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\ItemAffix;
use App\Game\Core\Comparison\ItemComparison;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateUser;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateItemAffix;

class ItemComparisonTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateUser, CreateItemAffix;

    private $character;

    private $item;

    public function setUp(): void {
        parent::setUp();

        $this->item = $this->createItem([
            'name' => 'Test',
            'type' => 'weapon',
            'base_damage' => 10,
            'cost' => 8,
        ]);

        $itemForCharacter = $this->createItem([
            'name' => 'Rusty Dagger',
            'base_damage' => 1,
            'cost' => 10,
            'type' => 'weapon',
        ]);

        $this->createItemAffix([
            'name'                 => 'Sample',
            'base_damage_mod'      => '0.10',
            'type'                 => 'suffix',
            'description'          => 'Sample',
            'base_healing_mod'     => '0.10',
            'str_mod'              => '0.10',
            'dur_mod'              => '0.10',
            'dex_mod'              => '0.10',
            'chr_mod'              => '0.10',
            'int_mod'              => '0.10',
            'skill_name'           => null,
            'skill_training_bonus' => null,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                               ->inventoryManagement()
                                               ->giveItem($itemForCharacter)
                                               ->equipLeftHand($itemForCharacter->name)
                                               ->getCharacterFactory();
    }


    public function testFetchComparisonIsEmpty()
    {

        $character = $this->character->inventoryManagement()->unequipAll()->getCharacter();

        $itemComparison = new ItemComparison();

        $this->assertTrue(empty($itemComparison->fetchDetails($this->item, $character->inventory->slots, $character)));
    }

    public function testWeaponIsBetter() {
        $itemComparison  = new ItemComparison();
        $character = $this->character->getCharacter();
        $comparisonDetails = $itemComparison->fetchDetails($this->item, $character->inventory->slots, $character);

        $this->assertFalse(empty($comparisonDetails));

        $this->assertTrue($comparisonDetails['left-hand']['is_better']);
        $this->assertNotNull($comparisonDetails['left-hand']['replaces_item']);
        $this->assertEquals('left-hand', $comparisonDetails['left-hand']['position']);
        $this->assertTrue($comparisonDetails['left-hand']['damage_adjustment'] > 0);
    }

    public function testWeaponIsBetterWithArtifactsAndAffixes() {
        $itemComparison  = new ItemComparison();

        $this->item->update([
            'item_suffix_id' => ItemAffix::first()->id,
        ]);

        $character = $this->character->getCharacter();

        $comparisonDetails = $itemComparison->fetchDetails($this->item, $character->inventory->slots, $character);

        $this->assertFalse(empty($comparisonDetails));

        $this->assertTrue($comparisonDetails['left-hand']['is_better']);
        $this->assertNotNull($comparisonDetails['left-hand']['replaces_item']);
        $this->assertEquals('left-hand', $comparisonDetails['left-hand']['position']);
        $this->assertTrue($comparisonDetails['left-hand']['damage_adjustment'] > 0);
    }

    public function testDownGradingAWeapon() {
        $itemComparison  = new ItemComparison();

        $character = $this->character
                          ->inventoryManagement()
                          ->unequipAll()
                          ->giveItem($this->item)
                          ->getCharacterFactory();


        $character = $character->inventoryManagement()->equipItem( 'left-hand', $this->item->name)->getCharacter();

        $downGradedItem = $character->inventory->slots->first()->item;

        $comparisonDetails = $itemComparison->fetchDetails($downGradedItem, $character->inventory->slots, $character);

        $this->assertFalse(empty($comparisonDetails));

        $this->assertFalse($comparisonDetails['left-hand']['is_better']);
        $this->assertNull($comparisonDetails['left-hand']['replaces_item']);
        $this->assertEquals('left-hand', $comparisonDetails['left-hand']['position']);
        $this->assertTrue($comparisonDetails['left-hand']['damage_adjustment'] < 0);
    }

    public function testComparingTheSameWeapon() {
        $itemComparison    = new ItemComparison();
        $character         = $this->character->getCharacter();
        $sameItem          = $character->inventory->slots->first()->item;
        $comparisonDetails = $itemComparison->fetchDetails($sameItem, $character->inventory->slots, $character);

        $this->assertFalse(empty($comparisonDetails));

        $this->assertFalse($comparisonDetails['left-hand']['is_better']);
        $this->assertNull($comparisonDetails['left-hand']['replaces_item']);
        $this->assertEquals('left-hand', $comparisonDetails['left-hand']['position']);
        $this->assertEquals($comparisonDetails['left-hand']['damage_adjustment'], 0);
    }

    public function testComparingTwoSetsOfArmourWhereOneIsBetter() {
        $itemComparison = new ItemComparison();

        $itemForComparison = $this->createItem([
            'name' => 'better gloves',
            'type' => 'gloves',
            'base_ac' => 10,
            'cost' => 8,
            'default_position' => 'hands',
        ]);

        $itemToEquip = $this->createItem([
            'name' => 'simple gloves',
            'type' => 'gloves',
            'base_ac' => 1,
            'cost' => 8,
            'default_position' => 'hands',
        ]);

        $character = $this->character->InventoryManagement()
                                     ->giveitem($itemToEquip)
                                     ->equipItem('hands', $itemToEquip->name)
                                     ->getCharacter();

        $inventory = $character->inventory->slots()->where('equipped', true)->where('position', $itemToEquip->default_position)->get();

        $comparison = $itemComparison->fetchDetails($itemForComparison, $inventory, $character);

        $this->assertTrue(isset($comparison['hands']));
        $this->assertNotNull($comparison['hands']['replaces_item']);
    }

    public function testComparingTwoSetsOfArmourWhereOneIsWorse() {
        $itemComparison = new ItemComparison();

        $itemToEquip = $this->createItem([
            'name' => 'better gloves',
            'type' => 'gloves',
            'base_ac' => 10,
            'cost' => 8,
            'default_position' => 'hands',
        ]);

        $itemForComparison = $this->createItem([
            'name' => 'simple gloves',
            'type' => 'gloves',
            'base_ac' => 1,
            'cost' => 8,
            'default_position' => 'hands',
        ]);

        $character = $this->character->InventoryManagement()
                                     ->giveitem($itemToEquip)
                                     ->equipItem('hands', $itemToEquip->name)
                                     ->getCharacter();

        $inventory = $character->inventory->slots()->where('equipped', true)->where('position', $itemToEquip->default_position)->get();

        $comparison = $itemComparison->fetchDetails($itemForComparison, $inventory, $character);

        $this->assertTrue(isset($comparison['hands']));
        $this->assertNull($comparison['hands']['replaces_item']);
    }

    public function testComparingTwoSetsOfHealingSpellsWhereOneIsWorse() {
        $itemComparison = new ItemComparison();

        $itemToEquip = $this->createItem([
            'name' => 'better spell',
            'type' => 'spell-heal',
            'base_healing' => 10,
            'cost' => 8,
        ]);

        $itemForComparison = $this->createItem([
            'name' => 'simple spell',
            'type' => 'spell-heal',
            'base_healing' => 1,
            'cost' => 8,
        ]);

        $character = $this->character->InventoryManagement()
                                     ->giveitem($itemToEquip)
                                     ->equipItem('spell_one', $itemToEquip->name)
                                     ->getCharacter();

        $inventory = $character->inventory->slots()->where('equipped', true)->where('position', 'spell_one')->get();

        $comparison = $itemComparison->fetchDetails($itemForComparison, $inventory, $character);

        $this->assertTrue(isset($comparison['spell_one']));
        $this->assertNull($comparison['spell_one']['replaces_item']);
    }

    public function testComparingTwoSetsOfHealingSpellsWhereOneIsBetter() {
        $itemComparison = new ItemComparison();

        $itemToEquip = $this->createItem([
            'name' => 'better spell',
            'type' => 'spell-heal',
            'base_healing' => 1,
            'cost' => 8,
        ]);

        $itemForComparison = $this->createItem([
            'name' => 'simple spell',
            'type' => 'spell-heal',
            'base_healing' => 10,
            'cost' => 8,
        ]);

        $character = $this->character->InventoryManagement()
                                     ->giveitem($itemToEquip)
                                     ->equipItem('spell_one', $itemToEquip->name)
                                     ->getCharacter();

        $inventory = $character->inventory->slots()->where('equipped', true)->where('position', 'spell_one')->get();

        $comparison = $itemComparison->fetchDetails($itemForComparison, $inventory, $character);

        $this->assertTrue(isset($comparison['spell_one']));
        $this->assertNotNull($comparison['spell_one']['replaces_item']);
    }

    public function testComparingTwoSetsItemsWhereOneIsWorse() {
        $itemComparison = new ItemComparison();

        $itemToEquip = $this->createItem([
            'name' => 'better spell',
            'type' => 'spell-heal',
            'base_healing' => 10,
            'cost' => 8,
            'chr_mod' => 0.10,
        ]);

        $itemForComparison = $this->createItem([
            'name' => 'simple spell',
            'type' => 'spell-heal',
            'base_healing' => 1,
            'cost' => 8,
            'chr_mod' => 0.01,
        ]);

        $character = $this->character->InventoryManagement()
                                     ->giveitem($itemToEquip)
                                     ->equipItem('spell_one', $itemToEquip->name)
                                     ->getCharacter();

        $inventory = $character->inventory->slots()->where('equipped', true)->where('position', 'spell_one')->get();

        $comparison = $itemComparison->fetchDetails($itemForComparison, $inventory, $character);

        $this->assertTrue(isset($comparison['spell_one']));
        $this->assertNull($comparison['spell_one']['replaces_item']);
    }

    public function testComparingArmourWhenCharacterDoesntHaveArmour() {
        $itemComparison = new ItemComparison();

        $itemForComparison = $this->createItem([
            'name' => 'simple gloves',
            'type' => 'gloves',
            'base_ac' => 1,
            'cost' => 8,
            'default_position' => 'hands',
        ]);

        $character = $this->character->getCharacter();

        $inventory = $character->inventory->slots()->where('equipped', true)->where('position', $itemForComparison->default_position)->get();

        $comparison = $itemComparison->fetchDetails($itemForComparison, $inventory, $character);

        $this->assertTrue(empty($comparison));
    }
}
