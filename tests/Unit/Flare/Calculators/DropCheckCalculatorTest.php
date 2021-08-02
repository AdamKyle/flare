<?php

namespace Tests\Unit\Flare\Calculators;

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
    }

    public function testDropCheckCalculator() {
        $chance = DropCheckCalculator::fetchDropCheckChance(
            $this->createMonster(), 100, 0.0, $this->createNewAdventure()
        );

        $this->assertTrue($chance);
    }

    public function testDropCheckCalculatorDefaultTrue() {
        $adventure = $this->createNewAdventure();

        $adventure->update([
            'item_find_chance' => 2,
        ]);

        $chance = DropCheckCalculator::fetchDropCheckChance(
            $this->createMonster(), 100, 0.0, $adventure->refresh()
        );

        $this->assertTrue($chance);
    }

    public function testDropCheckFetchQuestItemDropCheckTrue() {
        $adventure = $this->createNewAdventure();

        $adventure->update([
            'item_find_chance' => 2,
        ]);

        $chance = DropCheckCalculator::fetchQuestItemDropCheck(
            $this->createMonster(), 100, 0.0, $adventure->refresh()
        );

        $this->assertTrue($chance);
    }
}
