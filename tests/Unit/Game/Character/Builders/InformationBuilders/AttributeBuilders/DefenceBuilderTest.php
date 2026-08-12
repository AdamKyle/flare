<?php

namespace Tests\Unit\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DefenceBuilder;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class DefenceBuilderTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterStatBuilder $characterStatBuilder;

    private ?DefenceBuilder $defenceBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->characterStatBuilder = resolve(CharacterStatBuilder::class);
        $this->defenceBuilder = resolve(DefenceBuilder::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->characterStatBuilder = null;
        $this->defenceBuilder = null;
    }

    public function test_build_defence_returns_base_ac_with_nothing_equipped(): void
    {
        $character = $this->character->getCharacter();
        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->defenceBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame($character->ac, $this->defenceBuilder->buildDefence(0.10));
    }

    public function test_build_defence_ignores_class_bonus_without_a_shield(): void
    {
        $item = $this->createItem(['type' => 'body', 'base_ac' => 20]);
        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item, true, 'body')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->defenceBuilder->initialize($character, $character->skills, $equipped);

        $withBonus = $this->defenceBuilder->buildDefence(0.50);
        $withoutBonus = $this->defenceBuilder->buildDefence(0.0);

        $this->assertSame($withoutBonus, $withBonus);
    }

    public function test_build_defence_includes_class_bonus_with_a_shield(): void
    {
        $item = $this->createItem(['type' => 'shield', 'base_ac' => 20]);
        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item, true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->defenceBuilder->initialize($character, $character->skills, $equipped);

        $withBonus = $this->defenceBuilder->buildDefence(0.50);
        $withoutBonus = $this->defenceBuilder->buildDefence(0.0);

        $this->assertGreaterThan($withoutBonus, $withBonus);
    }

    public function test_build_defence_voided_excludes_affix_bonus(): void
    {
        $item = $this->createItem(['type' => 'body', 'base_ac' => 20]);
        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item, true, 'body')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->defenceBuilder->initialize($character, $character->skills, $equipped);

        $this->assertIsInt($this->defenceBuilder->buildDefence(0.10, true));
    }

    public function test_build_defence_break_down_details_returns_all_keys(): void
    {
        $item = $this->createItem(['type' => 'body', 'base_ac' => 20]);
        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item, true, 'body')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->defenceBuilder->initialize($character, $character->skills, $equipped);

        $details = $this->defenceBuilder->buildDefenceBreakDownDetails();

        $this->assertSame($character->ac, $details['base_ac']);
        $this->assertSame(20, $details['ac_from_items']);
        $this->assertIsArray($details['skill_effecting_ac']);
    }

    public function test_build_defence_break_down_details_with_nothing_equipped(): void
    {
        $character = $this->character->getCharacter();
        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->defenceBuilder->initialize($character, $character->skills, $equipped);

        $details = $this->defenceBuilder->buildDefenceBreakDownDetails();

        $this->assertSame(0, $details['ac_from_items']);
    }
}
