<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\CharacterSetup;

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

        $this->character = (new CharacterSetup())
                                ->setupCharacter($this->createUser())
                                ->giveItem($item)
                                ->equipLeftHand()
                                ->getCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }
    public function testCanSeeCharacterSheet() {
        $this->actingAs($this->character->user)
                    ->visitRoute('game.character.sheet')
                    ->see('Character Name')
                    ->see($this->character->name);
    }
}
