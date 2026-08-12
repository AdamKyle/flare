<?php

namespace Tests\Unit\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ElementalAtonement;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemSocket;

class ElementalAtonementTest extends TestCase
{
    use CreateGem, CreateItem, CreateItemSocket, RefreshDatabase;

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

        $this->assertNull($this->elementalAtonement->calculateAtonement());
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
        $gem = $this->createGem([
            'primary_atonement_type' => GemTypeValue::FIRE,
            'primary_atonement_amount' => 0.30,
            'secondary_atonement_type' => GemTypeValue::ICE,
            'secondary_atonement_amount' => 0.10,
            'tertiary_atonement_type' => GemTypeValue::WATER,
            'tertiary_atonement_amount' => 0.05,
        ]);
        $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $gem->id]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item, true, 'body')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->elementalAtonement->initialize($character, $character->skills, $equipped);

        $result = $this->elementalAtonement->calculateAtonement();

        $this->assertSame('fire', $result['highest_element']['name']);
        $this->assertSame(0.30, $result['highest_element']['damage']);
    }

    public function test_calculate_atonement_caps_average_at_seventy_five_percent(): void
    {
        $itemOne = $this->createItem(['type' => 'ring', 'socket_count' => 1]);
        $itemTwo = $this->createItem(['type' => 'ring', 'socket_count' => 1]);

        $gemOne = $this->createGem([
            'primary_atonement_type' => GemTypeValue::FIRE,
            'primary_atonement_amount' => 0.90,
            'secondary_atonement_type' => GemTypeValue::ICE,
            'secondary_atonement_amount' => 0.10,
            'tertiary_atonement_type' => GemTypeValue::WATER,
            'tertiary_atonement_amount' => 0.05,
        ]);
        $gemTwo = $this->createGem([
            'primary_atonement_type' => GemTypeValue::FIRE,
            'primary_atonement_amount' => 0.90,
            'secondary_atonement_type' => GemTypeValue::ICE,
            'secondary_atonement_amount' => 0.10,
            'tertiary_atonement_type' => GemTypeValue::WATER,
            'tertiary_atonement_amount' => 0.05,
        ]);
        $this->createItemSocket(['item_id' => $itemOne->id, 'gem_id' => $gemOne->id]);
        $this->createItemSocket(['item_id' => $itemTwo->id, 'gem_id' => $gemTwo->id]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemOne, true, 'ring-one')
            ->giveItem($itemTwo, true, 'ring-two')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->elementalAtonement->initialize($character, $character->skills, $equipped);

        $result = $this->elementalAtonement->calculateAtonement();

        $this->assertSame(0.75, $result['atonements']['fire']);
    }
}
