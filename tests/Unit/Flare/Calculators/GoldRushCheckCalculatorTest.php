<?php

namespace Tests\Unit\Flare\Calculators;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Facades\App\Flare\Calculators\GoldRushCheckCalculator;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateMonster;

class GoldRushCheckCalculatorTest extends TestCase
{
    use RefreshDatabase, CreateAdventure, CreateMonster;

    public function setUp(): void {
        parent::setUp();
    }

    public function testGoldDropRushCheck()
    {
        $adventure = $this->createNewAdventure();

        $adventure->update([
            'gold_rush_chance' => 2
        ]);

        $chance = GoldRushCheckCalculator::fetchGoldRushChance(
            $this->createMonster(), 100, 0.0, $adventure->refresh()
        );

        $this->assertTrue($chance);
    }
}
