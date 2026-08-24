<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks;

use App\Game\Battle\ServerFight\Fight\CharacterAttacks\BaseCharacterAttack;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\CharacterAttack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Battle\ServerFight\BaseCharacterAttackFactory;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class BaseCharacterAttackTest extends TestCase
{
    use RefreshDatabase;

    private BaseCharacterAttackFactory $baseCharacterAttackFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseCharacterAttackFactory = new BaseCharacterAttackFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->baseCharacterAttackFactory);
    }

    public function test_do_attack_delegates_to_attack_for_the_attack_type(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = $this->baseCharacterAttackFactory->buildMonster();

        $characterAttack = Mockery::mock(CharacterAttack::class);
        $characterAttack->shouldReceive('attack')->once()->with($character, $monster, false, 0, 0)->andReturnSelf();

        $baseCharacterAttack = (new BaseCharacterAttack($characterAttack))->setCharacterHealth(0)->setMonsterHealth(0);
        $result = $baseCharacterAttack->doAttack($character, $monster, false, 'attack');

        $this->assertSame($characterAttack, $result);
    }

    public function test_do_attack_delegates_to_cast_for_the_cast_type(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = $this->baseCharacterAttackFactory->buildMonster();

        $characterAttack = Mockery::mock(CharacterAttack::class);
        $characterAttack->shouldReceive('cast')->once()->with($character, $monster, false, 0, 0)->andReturnSelf();

        $baseCharacterAttack = (new BaseCharacterAttack($characterAttack))->setCharacterHealth(0)->setMonsterHealth(0);
        $result = $baseCharacterAttack->doAttack($character, $monster, false, 'cast');

        $this->assertSame($characterAttack, $result);
    }

    public function test_do_attack_delegates_to_attack_and_cast_for_the_attack_and_cast_type(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = $this->baseCharacterAttackFactory->buildMonster();

        $characterAttack = Mockery::mock(CharacterAttack::class);
        $characterAttack->shouldReceive('attackAndCast')->once()->with($character, $monster, false, 0, 0)->andReturnSelf();

        $baseCharacterAttack = (new BaseCharacterAttack($characterAttack))->setCharacterHealth(0)->setMonsterHealth(0);
        $result = $baseCharacterAttack->doAttack($character, $monster, false, 'attack_and_cast');

        $this->assertSame($characterAttack, $result);
    }

    public function test_do_attack_delegates_to_cast_and_attack_for_the_cast_and_attack_type(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = $this->baseCharacterAttackFactory->buildMonster();

        $characterAttack = Mockery::mock(CharacterAttack::class);
        $characterAttack->shouldReceive('castAndAttack')->once()->with($character, $monster, false, 0, 0)->andReturnSelf();

        $baseCharacterAttack = (new BaseCharacterAttack($characterAttack))->setCharacterHealth(0)->setMonsterHealth(0);
        $result = $baseCharacterAttack->doAttack($character, $monster, false, 'cast_and_attack');

        $this->assertSame($characterAttack, $result);
    }

    public function test_do_attack_delegates_to_defend_for_the_defend_type(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = $this->baseCharacterAttackFactory->buildMonster();

        $characterAttack = Mockery::mock(CharacterAttack::class);
        $characterAttack->shouldReceive('defend')->once()->with($character, $monster, false, 0, 0)->andReturnSelf();

        $baseCharacterAttack = (new BaseCharacterAttack($characterAttack))->setCharacterHealth(0)->setMonsterHealth(0);
        $result = $baseCharacterAttack->doAttack($character, $monster, false, 'defend');

        $this->assertSame($characterAttack, $result);
    }

    public function test_do_attack_adds_a_message_for_an_unknown_attack_type(): void
    {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $monster = $this->baseCharacterAttackFactory->buildMonster();

        $baseCharacterAttack = new BaseCharacterAttack(Mockery::mock(CharacterAttack::class));
        $result = $baseCharacterAttack->doAttack($character, $monster, false, 'unknown');

        $this->assertNull($result);
        $this->assertContains([
            'message' => 'No Attack Type Supplied. Attack Failed for character.',
            'type' => 'event-action',
        ], $baseCharacterAttack->getMessages());
    }

    public function test_set_character_health_and_monster_health_are_fluent(): void
    {
        $baseCharacterAttack = new BaseCharacterAttack(Mockery::mock(CharacterAttack::class));

        $this->assertSame($baseCharacterAttack, $baseCharacterAttack->setCharacterHealth(100));
        $this->assertSame($baseCharacterAttack, $baseCharacterAttack->setMonsterHealth(200));
    }
}
