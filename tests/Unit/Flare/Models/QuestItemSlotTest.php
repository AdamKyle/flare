<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Item;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateUser;

class QuestItemSlotTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateUser;

    public function testGetItemForQuestSlot()
    {
        $character = (new CharacterFactory)->createBaseCharacter()
                                           ->givePlayerLocation()
                                           ->getCharacter();

        $this->createItem([
            'name' => 'sample',
            'type' => 'quest',
            'cost' => 0,
        ]);

        $questSlot = $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => Item::first()->id,
        ]);

        $this->assertNotNull($questSlot->item);
    }
}
