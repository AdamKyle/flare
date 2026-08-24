<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks;

use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Types\CastType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Battle\ServerFight\PlayerHealingFactory;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;

class PlayerHealingTest extends TestCase
{
    use CreateClass, RefreshDatabase;

    private PlayerHealingFactory $playerHealingFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->playerHealingFactory = new PlayerHealingFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->playerHealingFactory);
    }

    public function test_life_steal_does_not_heal_above_max_health(): void
    {
        $class = $this->createClass(['name' => 'Vampire']);
        $character = (new CharacterFactory)->createBaseCharacter([], $class)->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
            'dur_modded' => 1000,
        ]);

        $playerHealing = $this->playerHealingFactory->buildPlayerHealing();
        $playerHealing->setCharacterHealth(990);
        $playerHealing->setMonsterHealth(1000);

        $playerHealing->lifeSteal($character);

        $this->assertEquals(950, $playerHealing->getMonsterHealth());
        $this->assertEquals(1000, $playerHealing->getCharacterHealth());
    }

    public function test_life_steal_does_nothing_for_non_vampires(): void
    {
        $class = $this->createClass(['name' => 'Fighter']);
        $character = (new CharacterFactory)->createBaseCharacter([], $class)->givePlayerLocation()->getCharacter();

        $playerHealing = $this->playerHealingFactory->buildPlayerHealing();
        $playerHealing->setCharacterHealth(990);
        $playerHealing->setMonsterHealth(1000);

        $playerHealing->lifeSteal($character);

        $this->assertEquals(1000, $playerHealing->getMonsterHealth());
        $this->assertEquals(990, $playerHealing->getCharacterHealth());
    }

    public function test_resurrect_revives_the_character_when_the_chance_passes(): void
    {
        $playerHealing = $this->playerHealingFactory->buildPlayerHealing();

        $result = $playerHealing->resurrect(['res_chance' => 1.0]);

        $this->assertTrue($result);
        $this->assertSame(1, $playerHealing->getCharacterHealth());
    }

    public function test_resurrect_fails_when_the_chance_does_not_pass(): void
    {
        $playerHealing = $this->playerHealingFactory->buildPlayerHealing();

        $result = $playerHealing->resurrect(['res_chance' => 0.0]);

        $this->assertFalse($result);
    }

    public function test_heal_in_battle_delegates_to_cast_type(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $castType = Mockery::mock(CastType::class);
        $castType->shouldReceive('setMonsterHealth')->once()->with(1000);
        $castType->shouldReceive('setCharacterHealth')->once()->with(990);
        $castType->shouldReceive('setCharacterAttackData')->once()->with($character, false, 'cast');
        $castType->shouldReceive('healDuringFight')->once()->with($character);
        $castType->shouldReceive('getMonsterHealth')->once()->andReturn(1000);
        $castType->shouldReceive('getCharacterHealth')->once()->andReturn(1000);
        $castType->shouldReceive('getMessages')->once()->andReturn([]);
        $castType->shouldReceive('clearMessages')->once();

        $playerHealing = $this->playerHealingFactory->buildPlayerHealing($this->playerHealingFactory->buildAffixes(), $castType);

        $playerHealing->setCharacterHealth(990);
        $playerHealing->setMonsterHealth(1000);

        $playerHealing->healInBattle($character, ['attack_type' => 'cast']);

        $this->assertSame(1000, $playerHealing->getCharacterHealth());
        $this->assertSame(1000, $playerHealing->getMonsterHealth());
    }
}
