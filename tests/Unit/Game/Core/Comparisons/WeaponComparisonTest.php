<?php

namespace Tests\Unit\Game\Core\Comparisons;

use App\Game\Core\Comparison\WeaponComparison;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Core\Events\UpdateShopInventoryBroadcastEvent;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateUser;
use Tests\Setup\CharacterSetup;

class WeaponComparisonTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateUser;

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
        
        $this->character = (new CharacterSetup)->setupCharacter($this->createUser())
                                               ->giveItem($itemForCharacter)
                                               ->equipLeftHand()
                                               ->getCharacter();
    }
    

    public function testFetchComparisonIsEmpty()
    {
        $this->character->inventory->slots->each(function($slot){
            $slot->update([
                'position' => null,
                'equipped' => false,
            ]);
        });

        $weaponComparison = new WeaponComparison();

        $this->assertTrue(empty($weaponComparison->fetchDetails($this->item, $this->character->inventory->slots)));
    }

    public function testWeaponIsBetter() {
        $weaponComparison  = new WeaponComparison();
        $comparisonDetails = $weaponComparison->fetchDetails($this->item, $this->character->inventory->slots);
 
        $this->assertFalse(empty($comparisonDetails));

        $this->assertTrue($comparisonDetails['left-hand']['is_better']);
        $this->assertNotNull($comparisonDetails['left-hand']['replaces_item']);
        $this->assertEquals('left-hand', $comparisonDetails['left-hand']['position']);
        $this->assertTrue($comparisonDetails['left-hand']['damage_adjustment'] > 0);
    }

    public function testWeaponIsBetterWithArtifactsAndAffixes() {
        $weaponComparison  = new WeaponComparison();

        $this->item->artifactProperty()->create([
            'item_id'         => $this->item->id,
            'name'            => 'Sample',
            'base_damage_mod' => 10,
            'description'     => 'Sample',
        ]);

        $this->item->itemAffixes()->create([
            'item_id'         => $this->item->id,
            'name'            => 'Sample',
            'base_damage_mod' => 10,
            'description'     => 'Sample',
            'type'            => 'suffix'
        ]);

        $comparisonDetails = $weaponComparison->fetchDetails($this->item, $this->character->inventory->slots);
 
        $this->assertFalse(empty($comparisonDetails));

        $this->assertTrue($comparisonDetails['left-hand']['is_better']);
        $this->assertNotNull($comparisonDetails['left-hand']['replaces_item']);
        $this->assertEquals('left-hand', $comparisonDetails['left-hand']['position']);
        $this->assertTrue($comparisonDetails['left-hand']['damage_adjustment'] > 0);
    }

    public function testDownGradingAWeapon() {
        $weaponComparison  = new WeaponComparison();

        $this->character->inventory->slots->each(function($slot){
            $slot->update([
                'position' => null,
                'equipped' => false,
            ]);
        });

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $this->item->id,
            'equipped'     => true,
            'position'     => 'left-hand',
        ]);

        $this->character->refresh();

        $downGradedItem = $this->character->inventory->slots->first()->item;

        $comparisonDetails = $weaponComparison->fetchDetails($downGradedItem, $this->character->inventory->slots);

        $this->assertFalse(empty($comparisonDetails));

        $this->assertFalse($comparisonDetails['left-hand']['is_better']);
        $this->assertNull($comparisonDetails['left-hand']['replaces_item']);
        $this->assertEquals('left-hand', $comparisonDetails['left-hand']['position']);
        $this->assertTrue($comparisonDetails['left-hand']['damage_adjustment'] < 0);
    }

    public function testComparingTheSameWeapon() {
        $weaponComparison  = new WeaponComparison();
        $sameItem          = $this->character->inventory->slots->first()->item;
        $comparisonDetails = $weaponComparison->fetchDetails($sameItem, $this->character->inventory->slots);

        $this->assertFalse(empty($comparisonDetails));

        $this->assertFalse($comparisonDetails['left-hand']['is_better']);
        $this->assertNull($comparisonDetails['left-hand']['replaces_item']);
        $this->assertEquals('left-hand', $comparisonDetails['left-hand']['position']);
        $this->assertEquals($comparisonDetails['left-hand']['damage_adjustment'], 0);
    }
}
