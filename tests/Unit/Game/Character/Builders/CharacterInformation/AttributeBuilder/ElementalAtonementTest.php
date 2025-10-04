<?php

namespace Tests\Unit\Game\Character\Builders\CharacterInformation\AttributeBuilder;

use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ElementalAtonement;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ElementalAtonementTest extends TestCase
{
    use CreateClass, CreateGameMap, CreateGameSkill, CreateGem, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterStatBuilder $characterStatBuilder;

    private ?ElementalAtonement $elementalAtonement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01,
            ]),
            5
        )->givePlayerLocation();

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

    public function test_character_with_maxed_out_elemental_atonement()
    {
        $item = $this->createItem([
            'type' => 'weapon',
            'socket_count' => 1,
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id' => $this->createGem([
                'primary_atonement_amount' => 0.75,
                'secondary_atonement_amount' => 0.75,
                'tertiary_atonement_amount' => 0.75,
            ])->id,
        ]);

        $item = $item->refresh();

        $character = $this->character->inventoryManagement()->giveItem($item, true)->getCharacter();

        $statBuilder = $this->characterStatBuilder->setCharacter($character);

        $elementalData = $statBuilder->buildElementalAtonement();

        $this->assertNotEmpty($elementalData['highest_element']);
        $this->assertEquals('fire', $elementalData['highest_element']['name']);
        $this->assertEquals(0.75, $elementalData['highest_element']['damage']);
        $this->assertNotEmpty($elementalData['atonements']);
    }

    public function test_character_gets_nothing_back_for_elemental_atonement()
    {

        $character = $this->character->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);

        $this->elementalAtonement->initialize($character, $character->skills, $equipped);

        $value = $this->elementalAtonement->calculateAtonement();

        $this->assertNull($value);
    }
}
