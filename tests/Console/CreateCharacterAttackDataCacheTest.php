<?php

namespace Tests\Console;


use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;

class CreateCharacterAttackDataCacheTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateAttackData()
    {

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter(false);

        $this->assertEquals(0, $this->artisan('create:character-attack-data'));

        $this->assertTrue(Cache::has('character-attack-data-' . $character->id));
    }
}
