<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\Character\CharacterFactory;

class CharacterSheetControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateUser;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6',
        ]);

        $this->character = (new CharacterFactory)
                                ->createBaseCharacter()
                                ->equipStartingEquipment();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanSeeCharacterSheet() {
        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $this->actingAs($user)
                    ->visitRoute('game.character.sheet')
                    ->see('Character Name')
                    ->see($character->name);
    }
}
