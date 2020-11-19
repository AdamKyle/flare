<?php

namespace Tests\Unit\Flare\Calculators;

use Database\Seeders\GameSkillsSeeder;
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

        $this->seed(GameSkillsSeeder::class);
    }

    public function testGoldDropRushCheck()
    {
        $adventure = $this->createNewAdventure();

        $adventure->update([
            'gold_rush_chance' => 2
        ]);
        
        $chance = GoldRushCheckCalculator::fetchGoldRushChance(
            $this->createMonster(), 100, $adventure->refresh()
        );

        $this->assertTrue($chance);
    }
}
