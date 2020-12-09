<?php

namespace Tests\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class GiveCharacterGoldTest extends TestCase
{
    use RefreshDatabase;

    public function testGiveCharacterGold()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();


        $this->assertEquals(0, $this->artisan('give:gold', ['characterId' => 1, 'amount' => 100]));

        $this->assertEquals(100, $character->refresh()->gold);
    }

    public function testFailToGiveGold()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $this->assertEquals(0, $this->artisan('give:gold', ['characterId' => 100, 'amount' => 1000]));

        $this->assertEquals(10, $character->refresh()->gold);
    }
}
