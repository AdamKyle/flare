<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Battle\ServerFight\CharacterCacheDataFactory;
use Tests\Setup\Battle\ServerFight\VoidanceFactory;
use Tests\TestCase;

class VoidanceTest extends TestCase
{
    use RefreshDatabase;

    private VoidanceFactory $voidanceFactory;

    private CharacterCacheDataFactory $characterCacheDataFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->voidanceFactory = new VoidanceFactory();
        $this->characterCacheDataFactory = new CharacterCacheDataFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->voidanceFactory, $this->characterCacheDataFactory);
    }

    public function test_character_voids_first_when_player_succeeds_at_devoiding_and_voiding(): void
    {
        $voidance = $this->voidanceFactory->buildVoidance();
        $character = $this->voidanceFactory->buildCharacter(false);
        $this->voidanceFactory->seedCharacterSheet($character, ['devouring_darkness' => 2.0]);
        $monster = $this->voidanceFactory->buildMonster();

        $voidance->void($character, $this->characterCacheDataFactory->build(), $monster);

        $this->assertTrue($voidance->isEnemyVoided());
        $this->assertFalse($voidance->isPlayerVoided());
        $this->assertContains([
            'message' => 'Magic crackles in the air, the darkness consumes the enemy. They are devoided!',
            'type' => 'regular',
        ], $voidance->getMessages());
        $this->assertContains([
            'message' => 'The light of the heavens shines through this darkness. The enemy is voided!',
            'type' => 'regular',
        ], $voidance->getMessages());
    }

    public function test_character_voids_first_when_the_monster_resists_and_counters(): void
    {
        $voidance = $this->voidanceFactory->buildVoidance();
        $character = $this->voidanceFactory->buildCharacter(false);
        $this->voidanceFactory->seedCharacterSheet($character);
        $monster = $this->voidanceFactory->buildMonster(['devouring_darkness_chance' => 1.0, 'devouring_light_chance' => 1.0]);

        $voidance->void($character, $this->characterCacheDataFactory->build(), $monster);

        $this->assertTrue($voidance->isPlayerVoided());
        $this->assertFalse($voidance->isEnemyVoided());
        $this->assertContains([
            'message' => 'Test Monster has devoided your voidance! You feel fear start to build.',
            'type' => 'enemy-action',
        ], $voidance->getMessages());
        $this->assertContains([
            'message' => 'Test Monster has voided your enchantments! You feel much weaker!',
            'type' => 'enemy-action',
        ], $voidance->getMessages());
    }

    public function test_character_voids_first_when_it_is_a_rank_fight_even_in_purgatory(): void
    {
        $voidance = $this->voidanceFactory->buildVoidance();
        $character = $this->voidanceFactory->buildCharacter(true);
        $this->voidanceFactory->seedCharacterSheet($character, ['devouring_darkness' => 2.0]);
        $monster = $this->voidanceFactory->buildMonster();

        $voidance->void($character, $this->characterCacheDataFactory->build(), $monster, true);

        $this->assertTrue($voidance->isEnemyVoided());
    }

    public function test_monster_voids_first_when_the_monster_succeeds_at_devoiding_and_voiding_in_purgatory(): void
    {
        $voidance = $this->voidanceFactory->buildVoidance();
        $character = $this->voidanceFactory->buildCharacter(true);
        $this->voidanceFactory->seedCharacterSheet($character);
        $monster = $this->voidanceFactory->buildMonster(['devouring_darkness_chance' => 1.0, 'devouring_light_chance' => 1.0]);

        $voidance->void($character, $this->characterCacheDataFactory->build(), $monster);

        $this->assertTrue($voidance->isPlayerVoided());
        $this->assertContains([
            'message' => 'Test Monster has devoided your voidance! You feel fear start to build.',
            'type' => 'enemy-action',
        ], $voidance->getMessages());
        $this->assertContains([
            'message' => 'Test Monster has voided your enchantments! You feel much weaker!',
            'type' => 'enemy-action',
        ], $voidance->getMessages());
    }

    public function test_monster_voids_first_when_the_player_resists_and_counters_in_purgatory(): void
    {
        $voidance = $this->voidanceFactory->buildVoidance();
        $character = $this->voidanceFactory->buildCharacter(true);
        $this->voidanceFactory->seedCharacterSheet($character, ['devouring_darkness' => 2.0]);
        $monster = $this->voidanceFactory->buildMonster();

        $voidance->void($character, $this->characterCacheDataFactory->build(), $monster);

        $this->assertFalse($voidance->isPlayerVoided());
        $this->assertContains([
            'message' => 'Magic crackles in the air, the darkness consumes the enemy. They are devoided!',
            'type' => 'regular',
        ], $voidance->getMessages());
        $this->assertContains([
            'message' => 'The light of the heavens shines through this darkness. The enemy is voided!',
            'type' => 'regular',
        ], $voidance->getMessages());
    }
}
