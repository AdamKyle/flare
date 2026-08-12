<?php

namespace Tests\Unit\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HolyBuilder;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\Values\CharacterClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateHolyStack;
use Tests\Traits\CreateItem;

class HolyBuilderTest extends TestCase
{
    use CreateClass, CreateHolyStack, CreateItem, RefreshDatabase;

    private ?CharacterStatBuilder $characterStatBuilder;

    private ?HolyBuilder $holyBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterStatBuilder = resolve(CharacterStatBuilder::class);
        $this->holyBuilder = resolve(HolyBuilder::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->characterStatBuilder = null;
        $this->holyBuilder = null;
    }

    public function test_fetch_holy_bonus_returns_zero_with_nothing_equipped(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->holyBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.0, $this->holyBuilder->fetchHolyBonus());
    }

    public function test_fetch_holy_bonus_divides_stacks_by_total_for_non_special_class(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $this->createHolyStacks(120, ['item_id' => $item->id]);
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'body')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->holyBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.5, $this->holyBuilder->fetchHolyBonus());
    }

    public function test_fetch_total_stacks_for_character_is_lower_for_ranger(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::RANGER->value]))
            ->givePlayerLocation()
            ->getCharacter();

        $this->holyBuilder->initialize($character, $character->skills, null);

        $this->assertSame(220, $this->holyBuilder->fetchTotalStacksForCharacter());
    }

    public function test_fetch_total_stacks_for_character_is_higher_for_default_class(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->getCharacter();

        $this->holyBuilder->initialize($character, $character->skills, null);

        $this->assertSame(240, $this->holyBuilder->fetchTotalStacksForCharacter());
    }

    public function test_fetch_devouring_resistance_bonus_returns_zero_with_nothing_equipped(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->holyBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.0, $this->holyBuilder->fetchDevouringResistanceBonus());
    }

    public function test_fetch_devouring_resistance_bonus_sums_holy_stack_bonuses(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $this->createHolyStack(['item_id' => $item->id, 'devouring_darkness_bonus' => 0.3, 'stat_increase_bonus' => 0.1]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'body')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->holyBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.3, $this->holyBuilder->fetchDevouringResistanceBonus());
    }

    public function test_fetch_devouring_resistance_bonus_excludes_trinkets_and_caps_at_one(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $trinket = $this->createItem(['type' => 'trinket']);
        $this->createHolyStack(['item_id' => $item->id, 'devouring_darkness_bonus' => 1.5, 'stat_increase_bonus' => 0.1]);
        $this->createHolyStack(['item_id' => $trinket->id, 'devouring_darkness_bonus' => 5.0, 'stat_increase_bonus' => 0.1]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'body')
            ->giveItem($trinket, true, 'trinket')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->holyBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(1.0, $this->holyBuilder->fetchDevouringResistanceBonus());
    }

    public function test_fetch_stat_increase_returns_zero_with_nothing_equipped(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->holyBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.0, $this->holyBuilder->fetchStatIncrease());
    }

    public function test_fetch_stat_increase_sums_stat_increase_bonuses(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $this->createHolyStack(['item_id' => $item->id, 'devouring_darkness_bonus' => 0.1, 'stat_increase_bonus' => 0.25]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'body')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->holyBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.25, $this->holyBuilder->fetchStatIncrease());
    }

    public function test_fetch_attack_bonus_is_capped_at_ninety_percent(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $this->createHolyStacks(240, ['item_id' => $item->id]);
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'body')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->holyBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.90, $this->holyBuilder->fetchAttackBonus());
    }

    public function test_fetch_defence_bonus_is_capped_at_seventy_five_percent(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $this->createHolyStacks(240, ['item_id' => $item->id]);
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'body')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->holyBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.75, $this->holyBuilder->fetchDefenceBonus());
    }

    public function test_fetch_healing_bonus_is_capped_at_one_hundred_percent(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $this->createHolyStacks(150, ['item_id' => $item->id]);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'body')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->holyBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(1.0, $this->holyBuilder->fetchHealingBonus());
    }
}
