<?php

namespace Tests\Console;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\User;
use Database\Seeders\CreateClasses;
use Database\Seeders\CreateRaces;
use Database\Seeders\GameSkillsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Storage;
use Tests\Setup\CharacterSetup;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateUser;

class GiveCharacterItemTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateItem;

    public function testGiveCharacterItem()
    {
        $this->seed(GameSkillsSeeder::class);
        
        $user = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter($user)->getCharacter();

        $item = $this->createItem();


        $this->assertEquals(0, $this->artisan('give:item', ['characterId' => $character->id, 'itemId' => $item->id]));

        $items = $character->refresh()->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->all();

        $this->assertFalse(empty($items));
    }

    public function testFailToGiveItemNonMatchingCharacter()
    {
        $this->seed(GameSkillsSeeder::class);
        
        $user = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter($user)->getCharacter();

        $item = $this->createItem();


        $this->assertEquals(0, $this->artisan('give:item', ['characterId' => 100, 'itemId' => $item->id]));

        $items = $character->refresh()->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->all();

        $this->assertTrue(empty($items));
    }

    public function testFailToGiveItem()
    {
        $this->seed(GameSkillsSeeder::class);
        
        $user = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter($user)->getCharacter();

        $item = $this->createItem();

        $this->assertEquals(0, $this->artisan('give:item', ['characterId' => $character->id, 'itemId' => 1000]));

        $items = $character->refresh()->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->all();

        $this->assertTrue(empty($items));
    }

    public function testFailToGiveItemMaxedInventory()
    {
        $this->seed(GameSkillsSeeder::class);
        
        $user = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter($user)->getCharacter();

        $character->update([
            'inventory_max' => 0
        ]);

        $item = $this->createItem();

        $this->assertEquals(0, $this->artisan('give:item', ['characterId' => $character->id, 'itemId' => $item->id]));

        $items = $character->refresh()->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->all();

        $this->assertTrue(empty($items));
    }
}
