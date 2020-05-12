<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Models\QuestItemSlot;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateUser;
use Tests\Setup\CharacterSetup;

class QuestItemSlotTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateUser;


    public function testQuestItemSlotCanGetParentItem()
    {
        $user      = $this->createUser();
        $character = (new CharacterSetup)->setupCharacter(['can_attack' => false], $user)
                                         ->getCharacter();
        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6'
        ]);

        QuestItemSlot::create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $item->id,
        ]);

        $this->assertEquals($item->id, QuestItemSlot::first()->item->id);
    }
}
