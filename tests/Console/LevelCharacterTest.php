<?php

namespace Tests\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class LevelCharacterTest extends TestCase
{
    use RefreshDatabase;

    public function testLevelCharacter() {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $this->assertEquals(0, $this->artisan('level:character', [
            'id' => $character->id,
            'levels' => 2
        ]));

        $character = $character->refresh();

        $this->assertEquals(3, $character->level);
    }

    public function testCannotLevelCharacter() {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $this->assertEquals(0, $this->artisan('level:character', [
            'id' => 34,
            'levels' => 2
        ]));

        $character = $character->refresh();

        $this->assertEquals(1, $character->level);
    }

    public function testCannotLevelCharacterPastMaxLevel() {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $this->assertEquals(0, $this->artisan('level:character', [
            'id' => $character->id,
            'levels' => 20000
        ]));

        $character = $character->refresh();

        $this->assertEquals(1, $character->level);
    }
}
