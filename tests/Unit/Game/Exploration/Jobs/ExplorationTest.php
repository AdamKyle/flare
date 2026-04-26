<?php

namespace Tests\Unit\Game\Exploration\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleRewardHandler;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Exploration\Events\AutomationLogUpdate;
use App\Game\Exploration\Events\AutomationTimeOut;
use App\Game\Exploration\Jobs\Exploration;
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

    private Character $character;

    private Monster $monster;

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

        parent::tearDown();
    }

    public function testHandleDeletesCurrentAutomationsWhenAutomationDoesNotExist(): void
    {
        Event::fake();

        CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $job = new Exploration($this->character, 999999, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job);

        $this->assertEquals(0, $this->character->currentAutomations()->count());
    }

    public function testHandleEndsExpiredAutomation(): void
    {
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleClearsSurvivalCacheWhenAutomationExpired(): void
    {
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        Cache::put('can-character-survive-' . $this->character->id, true);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job);

        $this->assertFalse(Cache::has('can-character-survive-' . $this->character->id));
    }

    public function testHandleRewardsGoldWhenAutomationEnds(): void
    {
        Event::fake();

        $this->character->update([
            'gold' => 10,
        ]);

        $this->character = $this->character->refresh();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job);

        $this->assertEquals(10010, $this->character->refresh()->gold);
    }

    public function testHandleCapsGoldWhenAutomationRewardWouldExceedMaxGold(): void
    {
        Event::fake();

        $this->character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
        ]);

        $this->character = $this->character->refresh();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job);

        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD, $this->character->refresh()->gold);
    }

    public function testHandleDispatchesCurrencyUpdateWhenAutomationEnds(): void
    {
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job);

        Event::assertDispatched(UpdateCharacterCurrenciesEvent::class);
    }

    public function testHandleDispatchesUpdateCharacterStatusWhenAutomationEnds(): void
    {
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job);

        Event::assertDispatched(UpdateCharacterStatus::class);
    }

    public function testHandleDispatchesAutomationTimeoutWhenAutomationEnds(): void
    {
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job);

        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testHandleDispatchesAutomationLogUpdateWhenCharacterIsLoggedIn(): void
    {
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job);

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function testHandleProcessesCachedSurvivableEncounter(): void
    {
        Queue::fake();
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->addHour(),
        ]);

        Cache::put('can-character-survive-' . $this->character->id, true);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job);

        Queue::assertPushed(BattleRewardHandler::class);
    }

    public function testHandleDispatchesNextExplorationJobAfterCachedSurvivableEncounter(): void
    {
        Queue::fake();
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->addHour(),
        ]);

        Cache::put('can-character-survive-' . $this->character->id, true);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job);

        Queue::assertPushed(Exploration::class);
    }

    public function testHandleEndsAutomationWhenEncounterHasNoTimeRemaining(): void
    {
        Queue::fake();
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->addSeconds(30),
        ]);

        Cache::put('can-character-survive-' . $this->character->id, true);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job);

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

        $automation = $this->createAutomation([
            'monster_id' => $this->monster->id,
            'previous_level' => 1,
            'current_level' => 1,
            'move_down_monster_list_every' => 1,
            'completed_at' => now()->addHour(),
        ]);

        Cache::put('can-character-survive-' . $this->character->id, true);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job);

        $automation = $automation->refresh();

        $this->assertEquals($nextMonster->id, $automation->monster_id);
    }

    public function testHandleBuildsSurvivalCacheWhenCharacterCanSurviveInitialFight(): void
    {
        Queue::fake();
        Event::fake();

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn($this->successfulFightData());
        $monsterFightService->shouldReceive('fightMonster')->andReturn($this->successfulFightData());
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $automation = $this->createAutomation([
            'completed_at' => now()->addHour(),
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job, $monsterFightService);

        $this->assertTrue(Cache::has('can-character-survive-' . $this->character->id));
    }

    public function testHandleDeletesAutomationWhenCharacterDiesDuringExploration(): void
    {
        Event::fake();

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn($this->deadCharacterFightData());

        $automation = $this->createAutomation([
            'completed_at' => now()->addHour(),
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job, $monsterFightService);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleDeletesAutomationWhenFightCannotBeProcessed(): void
    {
        Event::fake();

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn($this->livingMonsterFightData());
        $monsterFightService->shouldReceive('fightMonster')->andReturn([
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 100,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ]);

        $automation = $this->createAutomation([
            'completed_at' => now()->addHour(),
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job, $monsterFightService);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleDelaysNextExplorationJobByTimeDelay(): void
    {
        Queue::fake();
        Event::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');

        Carbon::setTestNow($now);

        $automation = $this->createAutomation([
            'completed_at' => $now->copy()->addHour(),
        ]);

        Cache::put('can-character-survive-' . $this->character->id, true);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job);

        Queue::assertPushed(Exploration::class, function (Exploration $queuedJob) use ($now): bool {
            return $queuedJob->delay->toDateTimeString() === $now->copy()->addMinutes(5)->toDateTimeString();
        });

        Carbon::setTestNow();
    }

    public function testHandleStopsAutomationWhenCharacterDiesAfterFight(): void
    {
        Event::fake();

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn($this->livingMonsterFightData());
        $monsterFightService->shouldReceive('fightMonster')->andReturn([
            'health' => [
                'current_character_health' => 0,
                'current_monster_health' => 0,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ]);

        $automation = $this->createAutomation([
            'completed_at' => now()->addHour(),
        ]);

        $job = new Exploration($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->handleJob($job, $monsterFightService);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    private function handleJob(Exploration $job, ?MonsterFightService $monsterFightService = null): void
    {
        $job->handle(
            $monsterFightService ?? resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
            resolve(FactionHandler::class),
        );
    }

    private function createAutomation(array $attributes = []): CharacterAutomation
    {
        return CharacterAutomation::factory()->create([
            ...[
                'character_id' => $this->character->id,
                'monster_id' => $this->monster->id,
                'type' => AutomationType::EXPLORING,
                'started_at' => now(),
                'completed_at' => now()->addHour(),
                'move_down_monster_list_every' => null,
                'previous_level' => $this->character->level,
                'current_level' => $this->character->level,
                'attack_type' => AttackTypeValue::ATTACK,
            ],
            ...$attributes,
        ]);
    }

    private function successfulFightData(): array
    {
        return [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ];
    }

    private function livingMonsterFightData(): array
    {
        return [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 100,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ];
    }

    private function deadCharacterFightData(): array
    {
        return [
            'health' => [
                'current_character_health' => 0,
                'current_monster_health' => 100,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ];
    }
}