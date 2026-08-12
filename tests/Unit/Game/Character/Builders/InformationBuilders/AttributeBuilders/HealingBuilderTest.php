<?php

namespace Tests\Unit\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HealingBuilder;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\Values\CharacterClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateItem;

class HealingBuilderTest extends TestCase
{
    use CreateClass, CreateItem, RefreshDatabase;

    private ?CharacterStatBuilder $characterStatBuilder;

    private ?HealingBuilder $healingBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterStatBuilder = resolve(CharacterStatBuilder::class);
        $this->healingBuilder = resolve(HealingBuilder::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->characterStatBuilder = null;
        $this->healingBuilder = null;
    }

    public function test_build_healing_returns_zero_with_nothing_equipped(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::PROPHET->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->healingBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.0, $this->healingBuilder->buildHealing());
    }

    public function test_build_healing_voided_excludes_affix_and_mastery_bonuses(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::PROPHET->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-healing', 'base_healing' => 100]), true, 'spell-one')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->healingBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(100.0, $this->healingBuilder->buildHealing(true));
    }

    public function test_build_healing_includes_affix_and_mastery_bonuses(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::PROPHET->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-healing', 'base_healing' => 100]), true, 'spell-one')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->healingBuilder->initialize($character, $character->skills, $equipped);

        $this->assertGreaterThanOrEqual(100.0, $this->healingBuilder->buildHealing(false));
    }

    public function test_build_healing_is_halved_for_alcoholic(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::ALCOHOLIC->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-healing', 'base_healing' => 100]), true, 'spell-one')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->healingBuilder->initialize($character, $character->skills, $equipped);

        $healing = $this->healingBuilder->buildHealing(false);
        $healingVoided = $this->healingBuilder->buildHealing(true);

        $this->assertSame($healingVoided - ($healingVoided * 0.50), $healing);
    }

    public function test_get_healing_builder_returns_base_healing_and_masteries(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::PROPHET->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-healing', 'base_healing' => 100]), true, 'spell-one')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->healingBuilder->initialize($character, $character->skills, $equipped);

        $details = $this->healingBuilder->getHealingBuilder(false);

        $this->assertSame(100, $details['base_healing']);
        $this->assertIsArray($details['masteries']);
        $this->assertArrayHasKey('skill_affecting_healing', $details);
    }

    public function test_get_healing_builder_returns_zero_base_healing_with_nothing_equipped(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::PROPHET->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->healingBuilder->initialize($character, $character->skills, $equipped);

        $details = $this->healingBuilder->getHealingBuilder(false);

        $this->assertSame(0, $details['base_healing']);
    }
}
