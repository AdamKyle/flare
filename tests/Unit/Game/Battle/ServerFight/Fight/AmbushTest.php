<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight;

use App\Game\Battle\ServerFight\Fight\Ambush;
use App\Game\Core\Chance\ChanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Battle\ServerFight\AmbushFactory;
use Tests\TestCase;

class AmbushTest extends TestCase
{
    use RefreshDatabase;

    private AmbushFactory $ambushFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ambushFactory = new AmbushFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->ambushFactory);
    }

    public function test_handle_ambush_sets_up_the_health_object_and_routes_to_the_monster_when_not_in_purgatory(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();
        $character = $this->ambushFactory->buildCharacter(false);
        $this->ambushFactory->seedCharacterSheet($character);
        $monster = $this->ambushFactory->buildMonster()->setHealth(500);

        $result = $ambush->handleAmbush($character, $monster);

        $this->assertSame($ambush, $result);
        $this->assertSame([
            'current_character_health' => 1000,
            'current_monster_health' => 500,
        ], $ambush->getHealthObject());
    }

    public function test_handle_ambush_routes_to_the_player_when_in_purgatory(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();
        $character = $this->ambushFactory->buildCharacter(true);
        $this->ambushFactory->seedCharacterSheet($character);
        $monster = $this->ambushFactory->buildMonster(['ambush_chance' => 2.0]);

        $ambush->handleAmbush($character, $monster);

        $this->assertSame(960, $ambush->getHealthObject()['current_character_health']);
    }

    public function test_player_ambushes_monster_deals_damage_and_caps_for_raid_bosses(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();
        $character = $this->ambushFactory->buildCharacter();
        $this->ambushFactory->seedCharacterSheet($character, ['ambush_chance' => 2.0, 'base_stat' => 4_000_000_000_000]);
        $monster = $this->ambushFactory->buildMonster(['is_raid_boss' => true]);
        $ambush->handleAmbush($character, $monster);

        $this->assertSame(500 - Ambush::MAX_DAMAGE_FOR_RAID_BOSSES, $ambush->getHealthObject()['current_monster_health']);
    }

    public function test_player_ambushes_monster_uses_voided_base_stat_when_voided(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();
        $character = $this->ambushFactory->buildCharacter();
        $this->ambushFactory->seedCharacterSheet($character, ['ambush_chance' => 2.0]);
        $monster = $this->ambushFactory->buildMonster()->setHealth(500);

        $ambush->handleAmbush($character, $monster, true, false);

        $this->assertSame(490, $ambush->getHealthObject()['current_monster_health']);
        $this->assertContains([
            'message' => 'The enemy has voided you, your ambush will be weaker, your strength escapes you',
            'type' => 'enemy-action',
        ], $ambush->getMessages());
    }

    public function test_player_ambush_fails_and_monster_ambushes_the_player_instead(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();
        $character = $this->ambushFactory->buildCharacter();
        $this->ambushFactory->seedCharacterSheet($character, ['ambush_chance' => 0.0]);
        $monster = $this->ambushFactory->buildMonster(['ambush_chance' => 2.0])->setHealth(500);

        $ambush->handleAmbush($character, $monster);

        $this->assertSame(960, $ambush->getHealthObject()['current_character_health']);
        $this->assertContains([
            'message' => 'Test Monster strikes you in an ambush doing: 40 damage!',
            'type' => 'enemy-action',
        ], $ambush->getMessages());
    }

    public function test_player_ambush_fails_and_monster_ambush_is_halved_when_enemy_is_voided(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();
        $character = $this->ambushFactory->buildCharacter();
        $this->ambushFactory->seedCharacterSheet($character, ['ambush_chance' => 0.0]);
        $monster = $this->ambushFactory->buildMonster(['ambush_chance' => 2.0])->setHealth(500);

        $ambush->handleAmbush($character, $monster, false, true);

        $this->assertSame(980, $ambush->getHealthObject()['current_character_health']);
        $this->assertContains([
            'message' => 'The enemies strength is sapped from its core as your voidance washes over it!',
            'type' => 'player-action',
        ], $ambush->getMessages());
    }

    public function test_player_ambushes_monster_returns_early_when_raid_boss_ambush_is_too_weak(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();
        $character = $this->ambushFactory->buildCharacter();
        $this->ambushFactory->seedCharacterSheet($character, ['ambush_chance' => 0.0]);
        $monster = $this->ambushFactory->buildMonster(['ambush_chance' => 2.0, 'is_raid_boss' => true]);

        $ambush->handleAmbush($character, $monster);

        $this->assertContains([
            'message' => 'The enemy spots you from the shadows. They contemplate their ambush and then laugh to them selves as they walk from the shadows. "Child, I could have ambushed you, alas lets see what you have to offer!"',
            'type' => 'enemy-action',
        ], $ambush->getMessages());
    }

    public function test_player_ambushes_monster_does_nothing_when_neither_side_can_ambush(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();
        $character = $this->ambushFactory->buildCharacter();
        $this->ambushFactory->seedCharacterSheet($character, ['ambush_chance' => 0.0, 'ambush_resistance_chance' => 2.0]);
        $monster = $this->ambushFactory->buildMonster(['ambush_chance' => 0.0, 'ambush_resistance_chance' => 2.0]);

        $ambush->handleAmbush($character, $monster);

        $this->assertEmpty($ambush->getMessages());
    }

    public function test_monster_ambushes_player_deals_damage(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();
        $character = $this->ambushFactory->buildCharacter(true);
        $this->ambushFactory->seedCharacterSheet($character, ['ambush_chance' => 0.0]);
        $monster = $this->ambushFactory->buildMonster(['ambush_chance' => 2.0]);

        $ambush->handleAmbush($character, $monster);

        $this->assertSame(960, $ambush->getHealthObject()['current_character_health']);
    }

    public function test_monster_ambushes_player_halved_when_enemy_voided(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();
        $character = $this->ambushFactory->buildCharacter(true);
        $this->ambushFactory->seedCharacterSheet($character, ['ambush_chance' => 0.0]);
        $monster = $this->ambushFactory->buildMonster(['ambush_chance' => 2.0]);

        $ambush->handleAmbush($character, $monster, false, true);

        $this->assertSame(980, $ambush->getHealthObject()['current_character_health']);
    }

    public function test_monster_ambushes_player_returns_early_for_raid_boss_with_no_ambush_chance(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();
        $character = $this->ambushFactory->buildCharacter(true);
        $this->ambushFactory->seedCharacterSheet($character, ['ambush_chance' => 0.0]);
        $monster = $this->ambushFactory->buildMonster(['ambush_chance' => 2.0, 'is_raid_boss' => true]);

        $ambush->handleAmbush($character, $monster);

        $this->assertContains([
            'message' => 'The enemy spots you from the shadows. They contemplate their ambush and then laugh to them selves as they walk from the shadows. "Child, I could have ambushed you, alas lets see what you have to offer!"',
            'type' => 'enemy-action',
        ], $ambush->getMessages());
    }

    public function test_monster_ambush_fails_and_player_ambushes_the_monster_instead(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();
        $character = $this->ambushFactory->buildCharacter(true);
        $this->ambushFactory->seedCharacterSheet($character, ['ambush_chance' => 2.0]);
        $monster = $this->ambushFactory->buildMonster(['ambush_chance' => 0.0])->setHealth(500);

        $ambush->handleAmbush($character, $monster);

        $this->assertSame(480, $ambush->getHealthObject()['current_monster_health']);
        $this->assertContains([
            'message' => 'You strike the enemy in an ambush doing: 20 damage!',
            'type' => 'player-action',
        ], $ambush->getMessages());
    }

    public function test_monster_ambush_fails_and_player_ambush_uses_voided_base_stat(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();
        $character = $this->ambushFactory->buildCharacter(true);
        $this->ambushFactory->seedCharacterSheet($character, ['ambush_chance' => 2.0]);
        $monster = $this->ambushFactory->buildMonster(['ambush_chance' => 0.0])->setHealth(500);

        $ambush->handleAmbush($character, $monster, true, false);

        $this->assertSame(490, $ambush->getHealthObject()['current_monster_health']);
        $this->assertContains([
            'message' => 'The enemy has voided you, your ambush will be weaker, your strength escapes you',
            'type' => 'enemy-action',
        ], $ambush->getMessages());
    }

    public function test_monster_ambushes_player_does_nothing_when_neither_side_can_ambush(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();
        $character = $this->ambushFactory->buildCharacter(true);
        $this->ambushFactory->seedCharacterSheet($character, ['ambush_chance' => 0.0, 'ambush_resistance_chance' => 2.0]);
        $monster = $this->ambushFactory->buildMonster(['ambush_chance' => 0.0, 'ambush_resistance_chance' => 2.0]);

        $ambush->handleAmbush($character, $monster);

        $this->assertEmpty($ambush->getMessages());
    }

    public function test_can_player_ambush_monster_false_when_monster_resistance_is_at_least_one(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();

        $this->assertFalse($ambush->canPlayerAmbushMonster(0.5, 1.0));
    }

    public function test_can_player_ambush_monster_true_when_chance_is_at_least_one(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();

        $this->assertTrue($ambush->canPlayerAmbushMonster(1.0, 0.0));
    }

    public function test_can_player_ambush_monster_false_when_chance_is_zero_or_less(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();

        $this->assertFalse($ambush->canPlayerAmbushMonster(0.0, 0.0));
    }

    public function test_can_player_ambush_monster_uses_the_chance_calculator_on_the_normal_path(): void
    {
        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(true);

        $ambush = $this->ambushFactory->buildAmbush($chanceCalculator);

        $this->assertTrue($ambush->canPlayerAmbushMonster(0.5, 0.0));
    }

    public function test_can_monster_ambush_player_false_when_player_resistance_is_at_least_one(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();

        $this->assertFalse($ambush->canMonsterAmbushPlayer(0.5, 1.0));
    }

    public function test_can_monster_ambush_player_true_when_chance_is_at_least_one(): void
    {
        $ambush = $this->ambushFactory->buildAmbush();

        $this->assertTrue($ambush->canMonsterAmbushPlayer(1.0, 0.0));
    }

    public function test_can_monster_ambush_player_uses_the_chance_calculator_on_the_normal_path(): void
    {
        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(false);

        $ambush = $this->ambushFactory->buildAmbush($chanceCalculator);

        $this->assertFalse($ambush->canMonsterAmbushPlayer(0.5, 0.0));
    }
}
