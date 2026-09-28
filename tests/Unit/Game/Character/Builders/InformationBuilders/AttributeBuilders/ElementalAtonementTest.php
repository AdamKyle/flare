<?php

namespace Tests\Unit\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ElementalAtonement;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Gems\Values\CharacterGemModifierType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterGemModifier;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemSocket;

class ElementalAtonementTest extends TestCase
{
    use CreateCharacterGemModifier, CreateGem, CreateItem, CreateItemSocket, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterStatBuilder $characterStatBuilder;

    private ?ElementalAtonement $elementalAtonement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->characterStatBuilder = resolve(CharacterStatBuilder::class);
        $this->elementalAtonement = resolve(ElementalAtonement::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->characterStatBuilder = null;
        $this->elementalAtonement = null;
    }

    public function test_calculate_atonement_returns_null_when_nothing_equipped(): void
    {
        $character = $this->character->getCharacter();
        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->elementalAtonement->initialize($character, $character->skills, $equipped);

        $result = $this->elementalAtonement->calculateAtonement();

        $this->assertSame('N/A', $result['highest_element']['name']);
        $this->assertSame(0.0, $result['highest_element']['damage']);
    }

    public function test_calculate_atonement_returns_not_applicable_when_no_gems_attached(): void
    {
        $item = $this->createItem(['type' => 'body', 'socket_count' => 0]);
        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item, true, 'body')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->elementalAtonement->initialize($character, $character->skills, $equipped);

        $result = $this->elementalAtonement->calculateAtonement();

        $this->assertSame('N/A', $result['highest_element']['name']);
        $this->assertSame(0.0, $result['highest_element']['damage']);
    }

    public function test_calculate_atonement_returns_highest_element_when_gem_is_attached(): void
    {
        $item = $this->createItem(['type' => 'body', 'socket_count' => 1]);
        $gem = $this->createGem(['tier' => 4]);
        $this->createCharacterGemModifier(['gem_id' => $gem->id, 'roll_position' => 1, 'modifier_type' => CharacterGemModifierType::FIRE_ATONEMENT, 'amount' => 0.30]);
        $this->createCharacterGemModifier(['gem_id' => $gem->id, 'roll_position' => 2, 'modifier_type' => CharacterGemModifierType::ICE_ATONEMENT, 'amount' => 0.10]);
        $this->createCharacterGemModifier(['gem_id' => $gem->id, 'roll_position' => 3, 'modifier_type' => CharacterGemModifierType::WATER_ATONEMENT, 'amount' => 0.05]);
        $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $gem->id]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item, true, 'body')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->elementalAtonement->initialize($character, $character->skills, $equipped);

        $result = $this->elementalAtonement->calculateAtonement();

        $this->assertSame('Fire', $result['highest_element']['name']);
        $this->assertSame(0.30, $result['highest_element']['damage']);
    }

    public function test_calculate_atonement_caps_average_at_seventy_five_percent(): void
    {
        $itemOne = $this->createItem(['type' => 'body', 'socket_count' => 1]);
        $itemTwo = $this->createItem(['type' => 'helmet', 'socket_count' => 1]);

        $gemOne = $this->createGem(['tier' => 4]);
        $gemTwo = $this->createGem(['tier' => 4]);
        $this->createCharacterGemModifier(['gem_id' => $gemOne->id, 'roll_position' => 1, 'modifier_type' => CharacterGemModifierType::FIRE_ATONEMENT, 'amount' => 0.40]);
        $this->createCharacterGemModifier(['gem_id' => $gemOne->id, 'roll_position' => 2, 'modifier_type' => CharacterGemModifierType::ICE_ATONEMENT, 'amount' => 0.10]);
        $this->createCharacterGemModifier(['gem_id' => $gemOne->id, 'roll_position' => 3, 'modifier_type' => CharacterGemModifierType::WATER_ATONEMENT, 'amount' => 0.05]);
        $this->createCharacterGemModifier(['gem_id' => $gemTwo->id, 'roll_position' => 1, 'modifier_type' => CharacterGemModifierType::FIRE_ATONEMENT, 'amount' => 0.50]);
        $this->createCharacterGemModifier(['gem_id' => $gemTwo->id, 'roll_position' => 2, 'modifier_type' => CharacterGemModifierType::ICE_ATONEMENT, 'amount' => 0.10]);
        $this->createCharacterGemModifier(['gem_id' => $gemTwo->id, 'roll_position' => 3, 'modifier_type' => CharacterGemModifierType::WATER_ATONEMENT, 'amount' => 0.05]);
        $this->createItemSocket(['item_id' => $itemOne->id, 'gem_id' => $gemOne->id]);
        $this->createItemSocket(['item_id' => $itemTwo->id, 'gem_id' => $gemTwo->id]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemOne, true, 'body')
            ->giveItem($itemTwo, true, 'helmet')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->elementalAtonement->initialize($character, $character->skills, $equipped);

        $result = $this->elementalAtonement->calculateAtonement();

        $this->assertSame(0.75, $result['atonements']['Fire']);
    }
}
