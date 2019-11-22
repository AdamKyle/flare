<?php

namespace Tests\Unit\Flare;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateUser;
use App\Flare\Builders\CharacterBuilder;

class CreateCharacterTest extends TestCase
{

    use RefreshDatabase,
        CreateRace,
        CreateClass,
        CreateUser;

    public function testCreateCharacter()
    {
        $race = $this->createRace([
            'str_mod' => 3,
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
        ]);

        $character = resolve(CharacterBuilder::class)->setRace($race)
                                                     ->setClass($class)
                                                     ->createCharacter($this->createUser(), 'sample');

        $this->assertEquals('sample', $character->name);
        $this->assertEquals(8, $character->str);
        $this->assertEquals(8, $character->dex);
        $this->assertEquals('dex', $character->damage_stat);
        $this->assertEquals($race->name, $character->race->name);
        $this->assertEquals($class->name, $character->class->name);
    }
}
