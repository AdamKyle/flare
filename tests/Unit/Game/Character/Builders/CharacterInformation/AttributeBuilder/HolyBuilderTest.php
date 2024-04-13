<?php

namespace Tests\Unit\Game\Character\Builders\CharacterInformation\AttributeBuilder;

use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class HolyBuilderTest extends TestCase {

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

        $this->character = null;
        $this->characterStatBuilder = null;
    }

    public function testHolyBonusWithNoInventory() {
        $character = $this->character->getCharacter();

        $holyBonus = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchHolyBonus();

        $this->assertEquals(0, $holyBonus);
    }

    public function testHolyBonusWithInventory() {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id'                  => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus'      => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $holyBonus = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchHolyBonus();

        $this->assertGreaterThan(0, $holyBonus);
    }

    public function testHolyBonusWithInventoryForTwoHandedClass() {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id'                  => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus'      => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $class = $this->createClass(['name' => 'Ranger']);

        $character->update([
            'game_class_id' => $class->id
        ]);

        $character = $character->refresh();

        $holyBonus = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchHolyBonus();

        $this->assertGreaterThan(0, $holyBonus);
    }

    public function testGetTotalStackAppliedWithNoInventory() {
        $character = $this->character->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->getTotalAppliedStacks();

        $this->assertEquals(0, $stacks);
    }

    public function testGetTotalStacksAppliedWithInventory() {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id'                  => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus'      => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->getTotalAppliedStacks();

        $this->assertEquals(1, $stacks);
    }

    public function testGetDevouringResistanceForNoInventory() {
        $character = $this->character->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchDevouringResistanceBonus();

        $this->assertEquals(0, $stacks);
    }

    public function testGetDevouringResistanceForInventory() {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id'                  => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus'      => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchDevouringResistanceBonus();

        $this->assertEquals(0.10, $stacks);
    }

    public function testGetStatIncreaseForNoInventory() {
        $character = $this->character->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchStatIncrease();

        $this->assertEquals(0, $stacks);
    }

    public function testGetStatIncreaseForInventory() {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id'                  => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus'      => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchStatIncrease();

        $this->assertEquals(0.10, $stacks);
    }

    public function testFetchAttackBonusNoInventory() {
        $character = $this->character->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchAttackBonus();

        $this->assertEquals(0, $stacks);
    }

    public function testFetchAttackBonusWithInventory() {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id'                  => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus'      => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchAttackBonus();

        $this->assertGreaterThan(0, $stacks);
    }

    public function testFetchDefenceBonusNoInventory() {
        $character = $this->character->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchDefenceBonus();

        $this->assertEquals(0, $stacks);
    }

    public function testFetchDefenceBonusWithInventory() {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id'                  => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus'      => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchDefenceBonus();

        $this->assertGreaterThan(0, $stacks);
    }

    public function testFetchHealingBonusNoInventory() {
        $character = $this->character->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchHealingBonus();

        $this->assertEquals(0, $stacks);
    }

    public function testFetchHealingBonusWithInventory() {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id'                  => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus'      => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchHealingBonus();

        $this->assertGreaterThan(0, $stacks);
    }
}
