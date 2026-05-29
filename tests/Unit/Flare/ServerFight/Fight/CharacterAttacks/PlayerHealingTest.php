<?php

namespace Tests\Unit\Flare\ServerFight\Fight\CharacterAttacks;

use App\Flare\ServerFight\Fight\CharacterAttacks\PlayerHealing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;

class PlayerHealingTest extends TestCase
{
    use CreateClass, RefreshDatabase;

    public function test_life_steal_does_not_heal_above_max_health(): void
    {
        $class = $this->createClass(['name' => 'Vampire']);
        $character = (new CharacterFactory)->createBaseCharacter([], $class)->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
            'dur_modded' => 1000,
        ]);

        $playerHealing = resolve(PlayerHealing::class);
        $playerHealing->setCharacterHealth(990);
        $playerHealing->setMonsterHealth(1000);

        $playerHealing->lifeSteal($character);

        $this->assertEquals(950, $playerHealing->getMonsterHealth());
        $this->assertEquals(1000, $playerHealing->getCharacterHealth());
    }
}
