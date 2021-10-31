<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateInventory;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class InventoryTest extends TestCase
{
    use RefreshDatabase, 
        CreateUser, 
        CreateCharacter, 
        CreateInventory,
        CreateRace,
        CreateClass;

    public function testModelRelationships() {
        $inventory = $this->createInventory([
            'character_id' => $this->createCharacter([
                'user_id'       => $this->createUser()->id,
                'game_race_id'  => $this->createRace()->id,
                'game_class_id' => $this->createClass()->id,
            ])->id
        ]);

        
        $this->assertNotNull($inventory->character);
    }
}
