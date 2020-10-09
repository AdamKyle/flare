<?php

namespace Tests\Unit\Flare\Calculators;

use Database\Seeders\GameSkillsSeeder;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateMonster;

class DropCheckCalculatorTest extends TestCase
{
    use RefreshDatabase, CreateAdventure, CreateMonster;

    public function setUp(): void {
        parent::setUp();

        $this->seed(GameSkillsSeeder::class);
    }

    public function testDropCheckCalculator()
    {
        $chance = DropCheckCalculator::fetchDropCheckChance(
            $this->createMonster(), 100, $this->createNewAdventure()
        );

        $this->assertTrue($chance);
    }
}
