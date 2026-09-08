<?php

namespace Tests\Unit\Game\Battle\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Automation\Values\AutomationType;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\ServerFight\MonsterPlayerFight;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\BattleRewardProcessing\Services\WeeklyBattleService;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Monster\MonsterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;

class MonsterFightServiceTest extends TestCase
{
    use CreateCharacterAutomation, RefreshDatabase;

    private ?Character $character = null;

    private ?Monster $monster = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $this->character->map->game_map_id,
            ])
            ->getMonster();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        $this->character = null;
        $this->monster = null;

        parent::tearDown();
    }

    public function test_setup_monster_deletes_character_sheet_cache_by_default(): void
    {
        Cache::put('character-sheet-'.$this->character->id, ['level' => 1]);

        $monsterPlayerFight = Mockery::mock(MonsterPlayerFight::class);
        $monsterPlayerFight->shouldReceive('setUpFight')->andReturn([]);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $weeklyBattleService = Mockery::mock(WeeklyBattleService::class);

        $this->instance(MonsterPlayerFight::class, $monsterPlayerFight);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(WeeklyBattleService::class, $weeklyBattleService);

        $service = resolve(MonsterFightService::class);

        $service->setupMonster($this->character, ['selected_monster_id' => $this->monster->id], true, false, false);

        $this->assertFalse(Cache::has('character-sheet-'.$this->character->id));
    }

    public function test_setup_monster_preserves_character_sheet_cache_when_flag_is_true(): void
    {
        Cache::put('character-sheet-'.$this->character->id, ['level' => 1]);

        $monsterPlayerFight = Mockery::mock(MonsterPlayerFight::class);
        $monsterPlayerFight->shouldReceive('setUpFight')->andReturn([]);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $weeklyBattleService = Mockery::mock(WeeklyBattleService::class);

        $this->instance(MonsterPlayerFight::class, $monsterPlayerFight);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(WeeklyBattleService::class, $weeklyBattleService);

        $service = resolve(MonsterFightService::class);

        $service->setupMonster($this->character, ['selected_monster_id' => $this->monster->id], true, false, true);

        $this->assertTrue(Cache::has('character-sheet-'.$this->character->id));
    }

    public function test_setup_monster_returns_automation_restriction_error_when_delve_automation_is_running(): void
    {
        $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $service = resolve(MonsterFightService::class);

        $result = $service->setupMonster($this->character, ['selected_monster_id' => $this->monster->id]);

        $this->assertSame(422, $result['status']);
        $this->assertSame('You cannot do that while Delve automation is running. Cancel it first.', $result['message']);
    }

    public function test_fight_monster_does_not_emit_defeat_message_when_monster_survives(): void
    {
        Event::fake([ServerMessageEvent::class]);

        Cache::put('monster-fight-'.$this->character->id, [
            'monster' => ['id' => $this->monster->id, 'name' => $this->monster->name],
        ], 900);

        $attackMessages = [
            ['message' => 'You attack the enemy for 10 damage!', 'type' => 'player-action'],
            ['message' => 'The enemy attacks you for 5 damage!', 'type' => 'enemy-action'],
        ];

        $monsterPlayerFight = Mockery::mock(MonsterPlayerFight::class);
        $monsterPlayerFight->shouldReceive('setCharacter')->once();
        $monsterPlayerFight->shouldReceive('fightMonster')->once();
        $monsterPlayerFight->shouldReceive('getCharacterHealth')->andReturn(50);
        $monsterPlayerFight->shouldReceive('getMonsterHealth')->andReturn(10);
        $monsterPlayerFight->shouldReceive('getBattleMessages')->andReturn($attackMessages);
        $monsterPlayerFight->shouldReceive('getMonsterLastRolledAttack')->andReturn(5);
        $monsterPlayerFight->shouldReceive('getMonster')->andReturn(['id' => $this->monster->id]);

        $weeklyBattleService = Mockery::mock(WeeklyBattleService::class);
        $weeklyBattleService->shouldReceive('canFightMonster')->once()->andReturn(true);

        $this->instance(MonsterPlayerFight::class, $monsterPlayerFight);
        $this->instance(WeeklyBattleService::class, $weeklyBattleService);

        $service = resolve(MonsterFightService::class);

        $result = $service->fightMonster($this->character, AttackType::ATTACK->value);

        $this->assertSame($attackMessages, $result['attack_messages']);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function test_fight_monster_emits_immediate_defeat_message_when_monster_is_killed(): void
    {
        Event::fake([ServerMessageEvent::class]);
        Queue::fake();

        Cache::put('monster-fight-'.$this->character->id, [
            'monster' => ['id' => $this->monster->id, 'name' => $this->monster->name],
        ], 900);

        $monsterPlayerFight = Mockery::mock(MonsterPlayerFight::class);
        $monsterPlayerFight->shouldReceive('setCharacter')->once();
        $monsterPlayerFight->shouldReceive('fightMonster')->once();
        $monsterPlayerFight->shouldReceive('getCharacterHealth')->andReturn(50);
        $monsterPlayerFight->shouldReceive('getMonsterHealth')->andReturn(0);
        $monsterPlayerFight->shouldReceive('getBattleMessages')->andReturn([]);
        $monsterPlayerFight->shouldReceive('getMonsterLastRolledAttack')->andReturn(5);
        $monsterPlayerFight->shouldReceive('getMonster')->andReturn(['id' => $this->monster->id]);

        $weeklyBattleService = Mockery::mock(WeeklyBattleService::class);
        $weeklyBattleService->shouldReceive('canFightMonster')->once()->andReturn(true);

        $this->instance(MonsterPlayerFight::class, $monsterPlayerFight);
        $this->instance(WeeklyBattleService::class, $weeklyBattleService);

        $service = resolve(MonsterFightService::class);

        $service->fightMonster($this->character, AttackType::ATTACK->value);

        Event::assertDispatchedTimes(ServerMessageEvent::class, 1);
        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) {
            return $event->message === 'You have defeated: '.$this->monster->name.'.';
        });
    }
}
