<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateInventory;
use Tests\Traits\CreateInventorySlot;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class InventorySlotTest extends TestCase
{
    use RefreshDatabase, 
        CreateUser, 
        CreateCharacter, 
        CreateInventory, 
        CreateInventorySlot, 
        CreateItem,
        CreateRace,
        CreateClass;

    public function testModelRelationships() {
        $inventorySlot = $this->createInventorySlot([
            'inventory_id' => $this->createInventory([
                'character_id' => $this->createCharacter([
                    'user_id'       => $this->createUser()->id,
                    'game_race_id'  => $this->createRace()->id,
                    'game_class_id' => $this->createClass()->id,
                ])->id
            ])->id,
            'item_id' => $this->createItem()->id,
        ]);

        
        $this->assertNotNull($inventorySlot->item);
    }
}
