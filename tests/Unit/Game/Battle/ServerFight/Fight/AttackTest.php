<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight;

use App\Game\Battle\ServerFight\Fight\Attack;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\BaseCharacterAttack;
use App\Game\Battle\ServerFight\Fight\MonsterAttack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Battle\ServerFight\AttackFactory;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class AttackTest extends TestCase
{
    use RefreshDatabase;

    public function test_attack_requires_resurrection_when_character_health_is_zero(): void
    {
        $baseCharacterAttack = Mockery::mock(BaseCharacterAttack::class);
        $monsterAttack = Mockery::mock(MonsterAttack::class);

        $attack = new Attack($baseCharacterAttack, $monsterAttack);
        $attack->setIsCharacterVoided(false)->setHealth(['current_character_health' => 0, 'current_monster_health' => 1000]);

        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = (new AttackFactory())->buildMonster();

        $attack->attack($character, $monster, 'attack', 'character');

        $this->assertContains(['message' => 'You must resurrect first!', 'type' => 'enemy-action'], $attack->getMessages());
    }

    public function test_attack_reports_defeat_when_monster_health_is_zero(): void
    {
        $baseCharacterAttack = Mockery::mock(BaseCharacterAttack::class);
        $monsterAttack = Mockery::mock(MonsterAttack::class);

        $attack = new Attack($baseCharacterAttack, $monsterAttack);
        $attack->setIsCharacterVoided(false)->setHealth(['current_character_health' => 1000, 'current_monster_health' => 0]);

        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = (new AttackFactory())->buildMonster();

        $attack->attack($character, $monster, 'attack', 'character');

        $this->assertContains(['message' => 'Test Monster has been defeated!', 'type' => 'enemy-action'], $attack->getMessages());
    }

    public function test_attack_stops_immediately_once_the_monster_is_defeated_by_the_character(): void
    {
        $response = Mockery::mock();
        $response->shouldReceive('getMessages')->andReturn([]);
        $response->shouldReceive('getCharacterHealth')->andReturn(1000);
        $response->shouldReceive('getMonsterHealth')->andReturn(0);
        $response->shouldReceive('resetMessages');

        $baseCharacterAttack = Mockery::mock(BaseCharacterAttack::class);
        $baseCharacterAttack->shouldReceive('setMonsterHealth')->once()->andReturnSelf();
        $baseCharacterAttack->shouldReceive('setCharacterHealth')->once()->andReturnSelf();
        $baseCharacterAttack->shouldReceive('doAttack')->once()->andReturn($response);

        $monsterAttack = Mockery::mock(MonsterAttack::class);

        $attack = new Attack($baseCharacterAttack, $monsterAttack);
        $attack->setIsCharacterVoided(false)->setHealth(['current_character_health' => 1000, 'current_monster_health' => 1000]);

        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = (new AttackFactory())->buildMonster();

        $attack->attack($character, $monster, 'attack', 'character');

        $this->assertSame(0, $attack->getMonsterHealth());
        $this->assertContains(['message' => 'Test Monster has been defeated!', 'type' => 'enemy-action'], $attack->getMessages());
    }

    public function test_attack_stops_and_resurrects_once_the_character_is_defeated_by_the_monster(): void
    {
        $response = Mockery::mock();
        $response->shouldReceive('getMessages')->andReturn([]);
        $response->shouldReceive('getCharacterHealth')->andReturn(1000);
        $response->shouldReceive('getMonsterHealth')->andReturn(900);
        $response->shouldReceive('resetMessages');

        $baseCharacterAttack = Mockery::mock(BaseCharacterAttack::class);
        $baseCharacterAttack->shouldReceive('setMonsterHealth')->once()->andReturnSelf();
        $baseCharacterAttack->shouldReceive('setCharacterHealth')->once()->andReturnSelf();
        $baseCharacterAttack->shouldReceive('doAttack')->once()->andReturn($response);

        $monsterAttack = Mockery::mock(MonsterAttack::class);
        $monsterAttack->shouldReceive('setIsCharacterVoided')->once();
        $monsterAttack->shouldReceive('setCharacterHealth')->once();
        $monsterAttack->shouldReceive('setMonsterHealth')->once();
        $monsterAttack->shouldReceive('setIsEnemyVoided')->once();
        $monsterAttack->shouldReceive('monsterAttack')->once();
        $monsterAttack->shouldReceive('getMessages')->once()->andReturn([]);
        $monsterAttack->shouldReceive('getCharacterHealth')->once()->andReturn(0);
        $monsterAttack->shouldReceive('getMonsterHealth')->once()->andReturn(900);
        $monsterAttack->shouldReceive('clearMessages')->once();

        $attack = new Attack($baseCharacterAttack, $monsterAttack);
        $attack->setIsCharacterVoided(false)->setHealth(['current_character_health' => 1000, 'current_monster_health' => 1000]);

        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = (new AttackFactory())->buildMonster();

        $attack->attack($character, $monster, 'attack', 'character');

        $this->assertSame(0, $attack->getCharacterHealth());
        $this->assertContains(['message' => 'You must resurrect first!', 'type' => 'enemy-action'], $attack->getMessages());
    }

    public function test_attack_clamps_monster_health_to_its_maximum(): void
    {
        $response = Mockery::mock();
        $response->shouldReceive('getMessages')->andReturn([]);
        $response->shouldReceive('getCharacterHealth')->andReturn(1000);
        $response->shouldReceive('getMonsterHealth')->andReturn(900);
        $response->shouldReceive('resetMessages');

        $baseCharacterAttack = Mockery::mock(BaseCharacterAttack::class);
        $baseCharacterAttack->shouldReceive('setMonsterHealth')->once()->andReturnSelf();
        $baseCharacterAttack->shouldReceive('setCharacterHealth')->once()->andReturnSelf();
        $baseCharacterAttack->shouldReceive('doAttack')->once()->andReturn($response);

        $monsterAttack = Mockery::mock(MonsterAttack::class);
        $monsterAttack->shouldReceive('setIsCharacterVoided')->once();
        $monsterAttack->shouldReceive('setCharacterHealth')->once();
        $monsterAttack->shouldReceive('setMonsterHealth')->once();
        $monsterAttack->shouldReceive('setIsEnemyVoided')->once();
        $monsterAttack->shouldReceive('monsterAttack')->once();
        $monsterAttack->shouldReceive('getMessages')->once()->andReturn([]);
        $monsterAttack->shouldReceive('getCharacterHealth')->once()->andReturn(1000);
        $monsterAttack->shouldReceive('getMonsterHealth')->once()->andReturn(5000);
        $monsterAttack->shouldReceive('clearMessages')->once();

        $attack = new Attack($baseCharacterAttack, $monsterAttack);
        $attack->onlyAttackOnce(true);
        $attack->setIsCharacterVoided(false)->setHealth(['current_character_health' => 1000, 'current_monster_health' => 1000]);

        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = (new AttackFactory())->buildMonster()->setHealth(1000);

        $attack->attack($character, $monster, 'attack', 'character');

        $this->assertSame(1000, $attack->getMonsterHealth());
    }

    public function test_attack_stops_after_ten_rounds_when_neither_side_is_defeated(): void
    {
        $response = Mockery::mock();
        $response->shouldReceive('getMessages')->andReturn([]);
        $response->shouldReceive('getCharacterHealth')->andReturn(1000);
        $response->shouldReceive('getMonsterHealth')->andReturn(1000);
        $response->shouldReceive('resetMessages');

        $baseCharacterAttack = Mockery::mock(BaseCharacterAttack::class);
        $baseCharacterAttack->shouldReceive('setMonsterHealth')->andReturnSelf();
        $baseCharacterAttack->shouldReceive('setCharacterHealth')->andReturnSelf();
        $baseCharacterAttack->shouldReceive('doAttack')->andReturn($response);

        $monsterAttack = Mockery::mock(MonsterAttack::class);
        $monsterAttack->shouldReceive('setIsCharacterVoided');
        $monsterAttack->shouldReceive('setCharacterHealth');
        $monsterAttack->shouldReceive('setMonsterHealth');
        $monsterAttack->shouldReceive('setIsEnemyVoided');
        $monsterAttack->shouldReceive('monsterAttack');
        $monsterAttack->shouldReceive('getMessages')->andReturn([]);
        $monsterAttack->shouldReceive('getCharacterHealth')->andReturn(1000);
        $monsterAttack->shouldReceive('getMonsterHealth')->andReturn(1000);
        $monsterAttack->shouldReceive('clearMessages');

        $attack = new Attack($baseCharacterAttack, $monsterAttack);
        $attack->setIsCharacterVoided(false)->setHealth(['current_character_health' => 1000, 'current_monster_health' => 1000]);

        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = (new AttackFactory())->buildMonster()->setHealth(1000);

        $attack->attack($character, $monster, 'attack', 'character');

        $this->assertTrue($attack->tookTooLong());
        $this->assertContains([
            'message' => 'Something is wrong. You attack took way too long. You seem evenly matched, try buying better gear or crafting it.',
            'type' => 'enemy-action',
        ], $attack->getMessages());
    }

    public function test_attack_only_attacks_once_when_configured(): void
    {
        $response = Mockery::mock();
        $response->shouldReceive('getMessages')->andReturn([]);
        $response->shouldReceive('getCharacterHealth')->andReturn(1000);
        $response->shouldReceive('getMonsterHealth')->andReturn(1000);
        $response->shouldReceive('resetMessages');

        $baseCharacterAttack = Mockery::mock(BaseCharacterAttack::class);
        $baseCharacterAttack->shouldReceive('setMonsterHealth')->once()->andReturnSelf();
        $baseCharacterAttack->shouldReceive('setCharacterHealth')->once()->andReturnSelf();
        $baseCharacterAttack->shouldReceive('doAttack')->once()->andReturn($response);

        $monsterAttack = Mockery::mock(MonsterAttack::class);
        $monsterAttack->shouldReceive('setIsCharacterVoided')->once();
        $monsterAttack->shouldReceive('setCharacterHealth')->once();
        $monsterAttack->shouldReceive('setMonsterHealth')->once();
        $monsterAttack->shouldReceive('setIsEnemyVoided')->once();
        $monsterAttack->shouldReceive('monsterAttack')->once();
        $monsterAttack->shouldReceive('getMessages')->once()->andReturn([]);
        $monsterAttack->shouldReceive('getCharacterHealth')->once()->andReturn(1000);
        $monsterAttack->shouldReceive('getMonsterHealth')->once()->andReturn(1000);
        $monsterAttack->shouldReceive('clearMessages')->once();

        $attack = new Attack($baseCharacterAttack, $monsterAttack);
        $attack->onlyAttackOnce(true);
        $attack->setIsCharacterVoided(false)->setHealth(['current_character_health' => 1000, 'current_monster_health' => 1000]);

        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = (new AttackFactory())->buildMonster()->setHealth(1000);

        $attack->attack($character, $monster, 'attack', 'character');

        $this->assertFalse($attack->tookTooLong());
        $this->assertSame(1000, $attack->getCharacterHealth());
        $this->assertSame(1000, $attack->getMonsterHealth());
    }

    public function test_attack_taunts_instead_of_attacking_when_a_raid_boss_faces_a_weak_character(): void
    {
        $response = Mockery::mock();
        $response->shouldReceive('getMessages')->andReturn([]);
        $response->shouldReceive('getCharacterHealth')->andReturn(1000);
        $response->shouldReceive('getMonsterHealth')->andReturn(1000);
        $response->shouldReceive('resetMessages');

        $baseCharacterAttack = Mockery::mock(BaseCharacterAttack::class);
        $baseCharacterAttack->shouldReceive('setMonsterHealth')->once()->andReturnSelf();
        $baseCharacterAttack->shouldReceive('setCharacterHealth')->once()->andReturnSelf();
        $baseCharacterAttack->shouldReceive('doAttack')->once()->andReturn($response);

        $monsterAttack = Mockery::mock(MonsterAttack::class);
        $monsterAttack->shouldReceive('setIsCharacterVoided')->once();
        $monsterAttack->shouldReceive('setCharacterHealth')->once();
        $monsterAttack->shouldReceive('setMonsterHealth')->once();
        $monsterAttack->shouldReceive('setIsEnemyVoided')->once();
        $monsterAttack->shouldReceive('addMessage')->once()->with(
            'Oh silly child, you tickle with me your feeble attempts. "I dare say, try again! You won\'t even be able to touch me!"',
            'enemy-action'
        );
        $monsterAttack->shouldReceive('getMessages')->once()->andReturn([]);
        $monsterAttack->shouldReceive('getCharacterHealth')->once()->andReturn(1000);
        $monsterAttack->shouldReceive('getMonsterHealth')->once()->andReturn(1000);
        $monsterAttack->shouldReceive('clearMessages')->once();

        $attack = new Attack($baseCharacterAttack, $monsterAttack);
        $attack->onlyAttackOnce(true);
        $attack->setIsCharacterVoided(false)->setHealth(['current_character_health' => 1000, 'current_monster_health' => 1000]);

        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = (new AttackFactory())->buildMonster(['is_raid_boss' => true])->setHealth(1000);

        $attack->attack($character, $monster, 'attack', 'character');

        $monsterAttack->shouldNotHaveReceived('monsterAttack');
        $this->assertSame(1000, $attack->getCharacterHealth());
        $this->assertSame(1000, $attack->getMonsterHealth());
    }

    public function test_reset_battle_messages_clears_existing_messages(): void
    {
        $baseCharacterAttack = Mockery::mock(BaseCharacterAttack::class);
        $monsterAttack = Mockery::mock(MonsterAttack::class);

        $attack = new Attack($baseCharacterAttack, $monsterAttack);
        $attack->mergeBattleMessages([['message' => 'test', 'type' => 'regular']]);

        $attack->resetBattleMessages();

        $this->assertEmpty($attack->getMessages());
    }
}
