<?php

namespace Tests\Unit\Game\Automation\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Jobs\Exploration;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleRewardHandler;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Skills\Services\SkillService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Monster\MonsterFactory;
use Tests\TestCase;

class ExplorationTest extends TestCase
{
    use RefreshDatabase;

    private ?Character $character;

    private ?Monster $monster;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->createSessionForCharacter()
            ->equipStrongGear()
            ->getCharacter();

        $this->monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $this->character->map->game_map_id,
            ])
            ->getMonster();
    }

    public function tearDown(): void
    {
        Mockery::close();

        $this->character = null;
        $this->monster = null;

        parent::tearDown();
    }

    public function testHandleDoesNotDeleteCurrentAutomationWhenStaleAutomationDoesNotExist(): void
    {
        Event::fake();

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $job = new Exploration($this->character, 999999, AttackTypeValue::ATTACK, 5);

        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        $automation = $automation->refresh();

        $this->assertEquals(AutomationType::EXPLORING, $automation->type);
        $this->assertEquals($this->monster->id, $automation->monster_id);
        $this->assertEquals(10, $automation->move_down_monster_list_every);
        $this->assertEquals($this->character->level, $automation->previous_level);
        $this->assertEquals($this->character->level, $automation->current_level);
        $this->assertEquals(AttackTypeValue::ATTACK, $automation->attack_type);
    }

    public function testHandleMissingAutomationSafelyBailsWithoutCancellingCurrentAutomation(): void
    {
        Event::fake();

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $job = new Exploration($this->character, $automation->id + 1, AttackTypeValue::ATTACK, 5);

        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        $this->assertNotNull(CharacterAutomation::find($automation->id));

        Event::assertNotDispatched(AutomationTimeOut::class);
        Event::assertNotDispatched(AutomationLogUpdate::class);
    }

    public function testHandleEndsExpiredAutomation(): void
    {
        Event::fake();

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->subMinute(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleClearsSurvivalCacheWhenAutomationExpired(): void
    {
        Event::fake();

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->subMinute(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        Cache::put('can-character-survive-' . $this->character->id, true);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        $this->assertFalse(Cache::has('can-character-survive-' . $this->character->id));
    }

    public function testHandleRewardsGoldWhenAutomationEnds(): void
    {
        Event::fake();

        $this->character->update([
            'gold' => 10,
        ]);

        $this->character = $this->character->refresh();

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->subMinute(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        $this->assertEquals(10010, $this->character->refresh()->gold);
    }

    public function testHandleCapsGoldWhenAutomationRewardWouldExceedMaxGold(): void
    {
        Event::fake();

        $this->character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
        ]);

        $this->character = $this->character->refresh();

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->subMinute(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD, $this->character->refresh()->gold);
    }

    public function testHandleDispatchesCurrencyUpdateWhenAutomationEnds(): void
    {
        Event::fake();

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->subMinute(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        Event::assertDispatched(UpdateCharacterCurrenciesEvent::class);
    }

    public function testHandleDispatchesUpdateCharacterStatusWhenAutomationEnds(): void
    {
        Event::fake();

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->subMinute(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        Event::assertDispatched(UpdateCharacterStatus::class);
    }

    public function testHandleDispatchesAutomationTimeoutWhenAutomationEnds(): void
    {
        Event::fake();

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->subMinute(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testHandleDispatchesAutomationLogUpdateWhenCharacterIsLoggedIn(): void
    {
        Event::fake();

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->subMinute(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function testHandleProcessesCachedSurvivableEncounter(): void
    {
        Queue::fake();
        Event::fake();

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        Cache::put('can-character-survive-' . $this->character->id, true);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        Queue::assertPushed(BattleRewardHandler::class);
    }

    public function testHandleDispatchesNextExplorationJobAfterCachedSurvivableEncounter(): void
    {
        Queue::fake();
        Event::fake();

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        Cache::put('can-character-survive-' . $this->character->id, true);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        Queue::assertPushed(Exploration::class);
    }

    public function testHandleEndsAutomationWhenEncounterHasNoTimeRemaining(): void
    {
        Queue::fake();
        Event::fake();

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(30),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        Cache::put('can-character-survive-' . $this->character->id, true);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleMovesDownMonsterListWhenLevelDifferenceMeetsThreshold(): void
    {
        Queue::fake();
        Event::fake();

        $nextMonster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $this->character->map->game_map_id,
            ])
            ->getMonster();

        $this->character->update([
            'level' => 2,
        ]);

        $this->character = $this->character->refresh();

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'move_down_monster_list_every' => 1,
            'previous_level' => 1,
            'current_level' => 1,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        Cache::put('can-character-survive-' . $this->character->id, true);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        $automation = $automation->refresh();

        $this->assertEquals($nextMonster->id, $automation->monster_id);
    }

    public function testHandleBuildsSurvivalCacheWhenCharacterCanSurviveInitialFight(): void
    {
        Queue::fake();
        Event::fake();

        $successfulFightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ];

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('fightMonster')->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            $monsterFightService,
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        $this->assertTrue(Cache::has('can-character-survive-' . $this->character->id));
    }

    public function testHandleDeletesAutomationWhenCharacterDiesDuringExploration(): void
    {
        Event::fake();

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn([
            'health' => [
                'current_character_health' => 0,
                'current_monster_health' => 100,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ]);

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            $monsterFightService,
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleDeletesAutomationWhenFightCannotBeProcessed(): void
    {
        Event::fake();

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn([
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 100,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ]);
        $monsterFightService->shouldReceive('fightMonster')->andReturn([
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 100,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ]);

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            $monsterFightService,
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleDelaysNextExplorationJobByTimeDelay(): void
    {
        Queue::fake();
        Event::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');

        Carbon::setTestNow($now);

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => $now->copy()->addHour(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        Cache::put('can-character-survive-' . $this->character->id, true);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        Queue::assertPushed(Exploration::class, function (Exploration $queuedJob) use ($now): bool {
            return $queuedJob->delay->toDateTimeString() === $now->copy()->addMinutes(5)->toDateTimeString();
        });

        Carbon::setTestNow();
    }

    public function testHandleStopsAutomationWhenCharacterDiesAfterFight(): void
    {
        Event::fake();

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn([
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 100,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ]);
        $monsterFightService->shouldReceive('fightMonster')->andReturn([
            'health' => [
                'current_character_health' => 0,
                'current_monster_health' => 0,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ]);

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $job->handle(
            $monsterFightService,
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );

        $this->assertNull(CharacterAutomation::find($automation->id));
    }
}
