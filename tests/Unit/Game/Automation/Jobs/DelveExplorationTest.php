<?php

namespace Tests\Unit\Game\Automation\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\DelveExploration as DelveExplorationModel;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\LocationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Automation\Enums\DelveOutcome;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Jobs\DelveExploration;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\SkillService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Monster\MonsterFactory;
use Tests\TestCase;

class DelveExplorationTest extends TestCase
{
    use RefreshDatabase;

    private Character $character;

    private Location $location;

    private Monster $monster;

    public function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->createSessionForCharacter()
            ->getCharacter();

        $this->location = Location::factory()->create([
            'x' => $this->character->map->character_position_x,
            'y' => $this->character->map->character_position_y,
            'game_map_id' => $this->character->map->game_map_id,
            'type' => LocationType::CAVE_OF_MEMORIES,
            'minutes_between_delve_fights' => 5,
            'delve_enemy_strength_increase' => 0.05,
        ]);

        $this->monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $this->character->map->game_map_id,
                'is_celestial_entity' => false,
                'is_raid_monster' => false,
                'is_raid_boss' => false,
                'only_for_location_type' => null,
                'raid_special_attack_type' => null,
            ])
            ->getMonster();

        Item::factory()->create([
            'type' => 'weapon',
            'specialty_type' => null,
        ]);
    }

    public function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::flush();
        Mockery::close();

        parent::tearDown();
    }

    public function testHandleDeletesCurrentAutomationsWhenAutomationDoesNotExist(): void
    {
        Event::fake();

        $this->createAutomation();

        $this->runJob(999999, 999999);

        $this->assertEquals(0, $this->character->currentAutomations()->count());
    }

    public function testHandleDeletesAutomationWhenDelveDoesNotExist(): void
    {
        Event::fake();

        $automation = $this->createAutomation();

        $this->runJob($automation->id, 999999);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleDeletesAutomationWhenLocationDoesNotExist(): void
    {
        Event::fake();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id, [], 999999);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleDoesNotChangeCompletedDelveCompletedAt(): void
    {
        Event::fake();

        $completedAt = now()->subMinute();

        $automation = $this->createAutomation();
        $delve = $this->createDelve([
            'completed_at' => $completedAt,
        ]);

        $this->runJob($automation->id, $delve->id);

        $this->assertEquals($completedAt->toDateTimeString(), $delve->refresh()->completed_at->toDateTimeString());
    }

    public function testHandleCompletesDelveWhenAutomationExpired(): void
    {
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        $this->assertNotNull($delve->refresh()->completed_at);
    }

    public function testHandleClearsSurvivalCacheWhenAutomationExpired(): void
    {
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        $delve = $this->createDelve();

        Cache::put('can-character-survive-' . $this->character->id, true);

        $this->runJob($automation->id, $delve->id);

        $this->assertFalse(Cache::has('can-character-survive-' . $this->character->id));
    }

    public function testHandleRewardsBaseGoldWhenAutomationEnds(): void
    {
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        $this->assertEquals(1010, $this->character->refresh()->gold);
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

        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD, $this->character->refresh()->gold);
    }

    public function testHandleDispatchesCurrencyUpdateWhenAutomationEnds(): void
    {
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        Event::assertDispatched(UpdateCharacterCurrenciesEvent::class);
    }

    public function testHandleDispatchesUpdateCharacterStatusWhenAutomationEnds(): void
    {
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        Event::assertDispatched(UpdateCharacterStatus::class);
    }

    public function testHandleDispatchesAutomationTimeoutWhenAutomationEnds(): void
    {
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testHandleDispatchesAutomationLogUpdateWhenCharacterIsLoggedIn(): void
    {
        Event::fake();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function testHandleDoesNotDispatchAutomationLogUpdateWhenCharacterIsNotLoggedIn(): void
    {
        Event::fake();

        DB::table('sessions')->truncate();

        $automation = $this->createAutomation([
            'completed_at' => now()->subMinute(),
        ]);

        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        Event::assertNotDispatched(AutomationLogUpdate::class);
    }

    public function testHandleDispatchesNextDelveJobAfterSurvivedEncounter(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        Queue::assertPushed(DelveExploration::class);
    }

    public function testHandleDoesNotDispatchNextDelveJobWhenPackMemberDoesNotSurvive(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindFightServiceSequence(
            [
                $this->livingFightData(),
                $this->deadFightData(),
            ],
            [
                $this->wonFightData(),
            ],
        );

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id, [
            'pack_size' => 2,
        ]);

        Queue::assertNotPushed(DelveExploration::class);
    }

    public function testHandleDelaysNextDelveJobByTimeDelay(): void
    {
        Queue::fake();
        Event::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');

        Carbon::setTestNow($now);

        $this->bindWinningFight();

        $automation = $this->createAutomation([
            'completed_at' => $now->copy()->addHour(),
        ]);

        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        Queue::assertPushed(DelveExploration::class, function (DelveExploration $queuedJob) use ($now): bool {
            return $queuedJob->delay->toDateTimeString() === $now->copy()->addMinutes(5)->toDateTimeString();
        });
    }

    public function testHandleCreatesSurvivedDelveLog(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        $this->assertDelveLogExists($delve, DelveOutcome::SURVIVED, 1);
    }

    public function testHandleDoesNotCreateSecondDelveLogWhenSameJobInstanceRunsAgain(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindFightServiceSequence(
            [
                $this->livingFightData(),
                $this->livingFightData(),
            ],
            [
                $this->wonFightData(),
                $this->wonFightData(),
            ],
        );

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $job = new DelveExploration(
            $this->character->id,
            $this->location->id,
            $automation->id,
            $delve->id,
            [
                'attack_type' => AttackTypeValue::ATTACK,
                'pack_size' => 1,
            ],
            5,
        );

        $this->handleJob($job);
        $this->handleJob($job);

        $this->assertEquals(
            1,
            DB::table('delve_logs')
                ->where('character_id', $this->character->id)
                ->where('delve_exploration_id', $delve->id)
                ->where('outcome', DelveOutcome::SURVIVED->value)
                ->count()
        );
    }

    public function testHandleIncreasesEnemyStrengthAfterSurvivedEncounter(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve([
            'increase_enemy_strength' => 0,
        ]);

        $this->runJob($automation->id, $delve->id);

        $this->assertEqualsWithDelta(0.05, $delve->refresh()->increase_enemy_strength, 0.0001);
    }

    public function testHandleCapsEnemyStrengthAfterSurvivedEncounter(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();

        $delve = $this->createDelve([
            'increase_enemy_strength' => 999.99,
        ]);

        $this->runJob($automation->id, $delve->id);

        $this->assertEquals(DelveExploration::MAX_INCREASE_PERCENTAGE, $delve->refresh()->increase_enemy_strength);
    }

    public function testHandleDoesNotIncreaseEnemyStrengthWhenAlreadyAtCap(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve([
            'increase_enemy_strength' => DelveExploration::MAX_INCREASE_PERCENTAGE,
        ]);

        $this->runJob($automation->id, $delve->id);

        $this->assertEquals(DelveExploration::MAX_INCREASE_PERCENTAGE, $delve->refresh()->increase_enemy_strength);
    }

    public function testHandleUpdatesMonsterForNextFight(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        $this->assertTrue(Monster::where('id', $delve->refresh()->monster_id)->exists());
    }

    public function testHandleDeletesPackCacheAfterSurvivedEncounter(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        Cache::put('delve-monster-' . $this->character->id . '-' . $this->monster->id . '-fight', [
            'id' => $this->monster->id,
        ]);

        $this->runJob($automation->id, $delve->id);

        $this->assertFalse(Cache::has('delve-monster-' . $this->character->id . '-' . $this->monster->id . '-fight'));
    }

    public function testHandleCompletesDelveWhenEncounterHasNoTimeRemaining(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation([
            'completed_at' => now()->addSeconds(30),
        ]);

        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        $this->assertNotNull($delve->refresh()->completed_at);
    }

    public function testHandleCreatesDeathLogWhenCharacterDiesDuringSetup(): void
    {
        Event::fake();

        $this->bindSetupDeathFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        $this->assertDelveLogExists($delve, DelveOutcome::DIED);
    }

    public function testHandleDeletesAutomationWhenCharacterDiesDuringSetup(): void
    {
        Event::fake();

        $this->bindSetupDeathFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleCompletesDelveWhenCharacterDiesDuringSetup(): void
    {
        Event::fake();

        $this->bindSetupDeathFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        $this->assertNotNull($delve->refresh()->completed_at);
    }

    public function testHandleCreatesDeathLogWhenCharacterDiesAfterFight(): void
    {
        Event::fake();

        $this->bindFightDeathFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        $this->assertDelveLogExists($delve, DelveOutcome::DIED);
    }

    public function testHandleDeletesAutomationWhenCharacterDiesAfterFight(): void
    {
        Event::fake();

        $this->bindFightDeathFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleCompletesDelveWhenCharacterDiesAfterFight(): void
    {
        Event::fake();

        $this->bindFightDeathFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        $this->assertNotNull($delve->refresh()->completed_at);
    }

    public function testHandleCreatesTimeoutLogWhenFightExceedsMaximumAttempts(): void
    {
        Event::fake();

        $this->bindTimeoutFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        $this->assertDelveLogExists($delve, DelveOutcome::TIMEOUT);
    }

    public function testHandleDeletesAutomationWhenFightExceedsMaximumAttempts(): void
    {
        Event::fake();

        $this->bindTimeoutFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleCompletesDelveWhenFightExceedsMaximumAttempts(): void
    {
        Event::fake();

        $this->bindTimeoutFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id);

        $this->assertNotNull($delve->refresh()->completed_at);
    }

    public function testHandleCreatesPackSizeTwoSurvivedLog(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id, [
            'pack_size' => 2,
        ]);

        $this->assertDelveLogExists($delve, DelveOutcome::SURVIVED, 2);
    }

    public function testHandleCreatesPackSizeFiveSurvivedLog(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id, [
            'pack_size' => 5,
        ]);

        $this->assertDelveLogExists($delve, DelveOutcome::SURVIVED, 5);
    }

    public function testHandleCreatesPackSizeTenSurvivedLog(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id, [
            'pack_size' => 10,
        ]);

        $this->assertDelveLogExists($delve, DelveOutcome::SURVIVED, 10);
    }

    public function testHandleCreatesPackSizeTwentySurvivedLog(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id, [
            'pack_size' => 20,
        ]);

        $this->assertDelveLogExists($delve, DelveOutcome::SURVIVED, 20);
    }

    public function testHandleCreatesPackSizeTwentyFiveSurvivedLog(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id, [
            'pack_size' => 25,
        ]);

        $this->assertDelveLogExists($delve, DelveOutcome::SURVIVED, 25);
    }

    public function testHandleDispatchesPackEncounterMessageOnce(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id, [
            'pack_size' => 2,
        ]);

        Event::assertDispatched(AutomationLogUpdate::class, function (AutomationLogUpdate $event): bool {
            return str_contains($event->message, 'Holy shit child, there are 2 of them.');
        });
    }

    public function testHandleDispatchesEnemyStrengthMessageWhenEnemyStrengthIsIncreased(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve([
            'increase_enemy_strength' => 0.05,
        ]);

        $this->runJob($automation->id, $delve->id);

        Event::assertDispatched(AutomationLogUpdate::class, function (AutomationLogUpdate $event): bool {
            return str_contains($event->message, 'strength has increased by: 5%');
        });
    }

    public function testHandleRewardsUniqueItemAfterMoreThanTwoHours(): void
    {
        $initialSlots = $this->character->inventory->slots()->count();

        $this->runCompletedPackRewardJob(3);

        $this->assertEquals($initialSlots + 1, $this->character->inventory->slots()->count());
    }

    public function testHandleDispatchesUniqueServerMessageAfterMoreThanTwoHours(): void
    {
        $this->runCompletedPackRewardJob(3);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event): bool {
            return str_contains($event->message, 'unique item');
        });
    }

    public function testHandleRewardsMythicAndUniqueItemsAfterMoreThanFourHours(): void
    {
        $initialSlots = $this->character->inventory->slots()->count();

        $this->runCompletedPackRewardJob(5);

        $this->assertEquals($initialSlots + 2, $this->character->inventory->slots()->count());
    }

    public function testHandleDispatchesMythicServerMessageAfterMoreThanFourHours(): void
    {
        $this->runCompletedPackRewardJob(5);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event): bool {
            return str_contains($event->message, 'mythic item');
        });
    }

    public function testHandleRewardsCosmicAndUniqueItemsAfterMoreThanSixHours(): void
    {
        $initialSlots = $this->character->inventory->slots()->count();

        $this->runCompletedPackRewardJob(7);

        $this->assertEquals($initialSlots + 2, $this->character->inventory->slots()->count());
    }

    public function testHandleDispatchesCosmicServerMessageAfterMoreThanSixHours(): void
    {
        $this->runCompletedPackRewardJob(7);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event): bool {
            return str_contains($event->message, 'cosmic item');
        });
    }

    public function testHandleDoesNotDispatchDelveRewardServerMessageWhenCharacterIsNotLoggedIn(): void
    {
        $this->runCompletedPackRewardJob(3, false);

        Event::assertNotDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event): bool {
            return str_contains($event->message, 'for surviving for more then 2 hours in a delve');
        });
    }

    public function testHandleAttemptsResetPerCreatureInPack(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindFightServiceSequence(
            [$this->livingFightData(), $this->livingFightData()],
            [
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->wonFightData(),
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->monsterStillAliveFightData(),
                $this->wonFightData(),
            ],
        );

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id, ['pack_size' => 2]);

        $this->assertDelveLogExists($delve, DelveOutcome::SURVIVED, 2);
    }

    public function testHandlePackSizeTwoDoesNotCreateTimeoutLogWhenBothCreaturesAreKilledQuickly(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id, ['pack_size' => 2]);

        $this->assertFalse(
            DB::table('delve_logs')
                ->where('character_id', $this->character->id)
                ->where('delve_exploration_id', $delve->id)
                ->where('outcome', DelveOutcome::TIMEOUT->value)
                ->exists()
        );
    }

    public function testHandlePackSizeGreaterThanOneCreatesOneDelveLog(): void
    {
        Queue::fake();
        Event::fake();

        $this->bindWinningFight();

        $automation = $this->createAutomation();
        $delve = $this->createDelve();

        $this->runJob($automation->id, $delve->id, ['pack_size' => 2]);

        $this->assertEquals(
            1,
            DB::table('delve_logs')
                ->where('character_id', $this->character->id)
                ->where('delve_exploration_id', $delve->id)
                ->count()
        );
    }

    private function runCompletedPackRewardJob(int $hoursElapsed, bool $loggedIn = true): void
    {
        Queue::fake();
        Event::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');

        Carbon::setTestNow($now);

        if (! $loggedIn) {
            DB::table('sessions')
                ->where('user_id', $this->character->user_id)
                ->delete();

            $this->assertFalse($this->character->refresh()->isLoggedIn());
        }

        $this->bindWinningFight();

        $automation = $this->createAutomation([
            'completed_at' => $now->copy()->addSeconds(30),
        ]);

        $delve = $this->createDelve([
            'started_at' => $now->copy()->subHours($hoursElapsed),
        ]);

        $this->runJob($automation->id, $delve->id, [
            'pack_size' => 2,
        ]);
    }

    private function runJob(
        int $automationId,
        int $delveExplorationId,
        array $params = [],
        ?int $locationId = null,
        int $timeDelay = 5,
    ): void {
        $job = new DelveExploration(
            $this->character->id,
            $locationId ?? $this->location->id,
            $automationId,
            $delveExplorationId,
            [
                'attack_type' => AttackTypeValue::ATTACK,
                'pack_size' => 1,
                ...$params,
            ],
            $timeDelay
        );

        $this->handleJob($job);
    }

    private function handleJob(DelveExploration $job): void
    {
        $job->handle(
            resolve(MonsterFightService::class),
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(CharacterRewardService::class),
            resolve(SkillService::class),
        );
    }

    private function createAutomation(array $attributes = []): CharacterAutomation
    {
        return CharacterAutomation::factory()->create([
            ...[
                'character_id' => $this->character->id,
                'monster_id' => $this->monster->id,
                'type' => AutomationType::DELVE,
                'started_at' => now(),
                'completed_at' => now()->addHour(),
                'attack_type' => AttackTypeValue::ATTACK,
            ],
            ...$attributes,
        ]);
    }

    private function createDelve(array $attributes = []): DelveExplorationModel
    {
        return DelveExplorationModel::factory()->create([
            ...[
                'character_id' => $this->character->id,
                'monster_id' => $this->monster->id,
                'started_at' => now(),
                'completed_at' => null,
                'attack_type' => AttackTypeValue::ATTACK,
                'increase_enemy_strength' => 0,
            ],
            ...$attributes,
        ]);
    }

    private function bindWinningFight(): void
    {
        $this->bindFightService($this->livingFightData(), $this->wonFightData());
    }

    private function bindSetupDeathFight(): void
    {
        $this->bindFightService($this->deadFightData());
    }

    private function bindFightDeathFight(): void
    {
        $this->bindFightService($this->livingFightData(), $this->deadFightData());
    }

    private function bindTimeoutFight(): void
    {
        $this->bindFightService($this->livingFightData(), $this->monsterStillAliveFightData());
    }

    private function bindFightService(array $setupData, ?array $fightData = null): void
    {
        $monster = $this->monster;

        $monsterFightService = Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($setupData, $fightData, $monster) {
            $mock->shouldReceive('setupMonster')->andReturn($setupData);
            $mock->shouldReceive('getMonster')->andReturn($monster);

            if (is_null($fightData)) {
                $mock->shouldReceive('fightMonster')->never();

                return;
            }

            $mock->shouldReceive('fightMonster')->andReturn($fightData);
        });

        $this->app->instance(MonsterFightService::class, $monsterFightService);
    }

    private function bindFightServiceSequence(array $setupDataSequence, array $fightDataSequence): void
    {
        $monster = $this->monster;

        $monsterFightService = Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($setupDataSequence, $fightDataSequence, $monster) {
            $mock->shouldReceive('setupMonster')
                ->times(count($setupDataSequence))
                ->andReturnValues($setupDataSequence);

            $mock->shouldReceive('getMonster')
                ->andReturn($monster);

            $mock->shouldReceive('fightMonster')
                ->times(count($fightDataSequence))
                ->andReturnValues($fightDataSequence);
        });

        $this->app->instance(MonsterFightService::class, $monsterFightService);
    }

    private function livingFightData(): array
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

    private function wonFightData(): array
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

    private function deadFightData(): array
    {
        return [
            'health' => [
                'current_character_health' => 0,
                'current_monster_health' => 0,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ];
    }

    private function monsterStillAliveFightData(): array
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

    private function assertDelveLogExists(DelveExplorationModel $delve, DelveOutcome $outcome, ?int $packSize = null): void
    {
        $query = DB::table('delve_logs')
            ->where('character_id', $this->character->id)
            ->where('delve_exploration_id', $delve->id)
            ->where('outcome', $outcome->value);

        if (! is_null($packSize)) {
            $query->where('pack_size', $packSize);
        }

        $this->assertTrue($query->exists());
    }
}
