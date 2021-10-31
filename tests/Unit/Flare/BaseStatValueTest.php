<?php

namespace Tests\Unit\Flare;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use App\Flare\Values\BaseStatValue;

class BaseStatValueTest extends TestCase
{
    use RefreshDatabase,
        CreateRace,
        CreateClass;

    public function testCreateCharacter()
    {
        $race = $this->createRace([
            'str_mod' => 3,
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
        ]);

        $baseStat = resolve(BaseStatValue::class)->setRace($race)->setClass($class);

        $this->assertEquals(13.0, $baseStat->str());
        $this->assertEquals(13.0, $baseStat->dex());
        $this->assertEquals(10, $baseStat->int());
        $this->assertEquals(10, $baseStat->dur());
        $this->assertEquals(10, $baseStat->chr());
    }
}
