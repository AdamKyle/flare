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

class HolyBuilderTest extends TestCase
{
    use CreateClass, CreateGameMap, CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterStatBuilder $characterStatBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->characterStatBuilder = resolve(CharacterStatBuilder::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->characterStatBuilder = null;
    }

    public function test_holy_bonus_with_no_inventory()
    {
        $character = $this->character->getCharacter();

        $holyBonus = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchHolyBonus();

        $this->assertEquals(0, $holyBonus);
    }

    public function test_holy_bonus_with_inventory()
    {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id' => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $holyBonus = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchHolyBonus();

        $this->assertGreaterThan(0, $holyBonus);
    }

    public function test_holy_bonus_with_inventory_for_two_handed_class()
    {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id' => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $class = $this->createClass(['name' => 'Ranger']);

        $character->update([
            'game_class_id' => $class->id,
        ]);

        $character = $character->refresh();

        $holyBonus = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchHolyBonus();

        $this->assertGreaterThan(0, $holyBonus);
    }

    public function test_get_total_stack_applied_with_no_inventory()
    {
        $character = $this->character->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->getTotalAppliedStacks();

        $this->assertEquals(0, $stacks);
    }

    public function test_get_total_stacks_applied_with_inventory()
    {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id' => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->getTotalAppliedStacks();

        $this->assertEquals(1, $stacks);
    }

    public function test_get_devouring_resistance_for_no_inventory()
    {
        $character = $this->character->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchDevouringResistanceBonus();

        $this->assertEquals(0, $stacks);
    }

    public function test_get_devouring_resistance_for_inventory()
    {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id' => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchDevouringResistanceBonus();

        $this->assertEquals(0.10, $stacks);
    }

    public function test_get_stat_increase_for_no_inventory()
    {
        $character = $this->character->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchStatIncrease();

        $this->assertEquals(0, $stacks);
    }

    public function test_get_stat_increase_for_inventory()
    {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id' => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchStatIncrease();

        $this->assertEquals(0.10, $stacks);
    }

    public function test_fetch_attack_bonus_no_inventory()
    {
        $character = $this->character->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchAttackBonus();

        $this->assertEquals(0, $stacks);
    }

    public function test_fetch_attack_bonus_with_inventory()
    {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id' => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchAttackBonus();

        $this->assertGreaterThan(0, $stacks);
    }

    public function test_fetch_defence_bonus_no_inventory()
    {
        $character = $this->character->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchDefenceBonus();

        $this->assertEquals(0, $stacks);
    }

    public function test_fetch_defence_bonus_with_inventory()
    {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id' => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchDefenceBonus();

        $this->assertGreaterThan(0, $stacks);
    }

    public function test_fetch_healing_bonus_no_inventory()
    {
        $character = $this->character->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchHealingBonus();

        $this->assertEquals(0, $stacks);
    }

    public function test_fetch_healing_bonus_with_inventory()
    {
        $item = $this->createItem([
            'name' => 'Weapon',
            'type' => 'weapon',
            'base_damage' => 100,
        ]);

        $item->appliedHolyStacks()->create([
            'item_id' => 0.10,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $stacks = $this->characterStatBuilder->setCharacter($character)->holyInfo()->fetchHealingBonus();

        $this->assertGreaterThan(0, $stacks);
    }
}
