<?php

namespace Tests\Console;

use Database\Seeders\GameSkillsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class GiveCharacterItemTest extends TestCase
{
    use RefreshDatabase, CreateItem;

    public function testGiveCharacterItem()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $item = $this->createItem();


        $this->assertEquals(0, $this->artisan('give:item', ['characterId' => $character->id, 'itemId' => $item->id]));

        $items = $character->refresh()->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->all();

        $this->assertFalse(empty($items));
    }

    public function testFailToGiveItemNonMatchingCharacter()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $item = $this->createItem();

        $this->assertEquals(0, $this->artisan('give:item', ['characterId' => 100, 'itemId' => $item->id]));

        $items = $character->refresh()->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->all();

        $this->assertTrue(empty($items));
    }

    public function testFailToGiveItem()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $item = $this->createItem();

        $this->assertEquals(0, $this->artisan('give:item', ['characterId' => $character->id, 'itemId' => 1000]));

        $items = $character->refresh()->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->all();

        $this->assertTrue(empty($items));
    }

    public function testFailToGiveItemMaxedInventory()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->updateCharacter(['inventory_max' => 0])->getCharacter();

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
