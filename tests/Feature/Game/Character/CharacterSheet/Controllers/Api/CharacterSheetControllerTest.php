<?php

namespace Tests\Feature\Game\Character\CharacterSheet\Controllers\Api;

use App\Game\Character\CharacterInventory\Values\ItemType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class CharacterSheetControllerTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function testCharacterSheetReturnsHealForWhenHealingSpellIsEquipped()
    {
        $item = $this->createItem([
            'name' => 'sample',
            'type' => ItemType::SPELL_HEALING->value,
            'base_healing' => 100,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item, true, 'spell-one')
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character-sheet/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertGreaterThan(0, $jsonData['sheet']['heal_for']);
    }
}
