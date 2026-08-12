<?php

namespace Tests\Unit\Game\Automation\Services;

use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Game\Automation\Events\ExplorationOutputUpdated;
use App\Game\Automation\Events\ExplorationWarningState;
use App\Game\Automation\Services\ExplorationLogService;
use App\Game\Automation\Values\AutomationType;
use App\Game\Core\Combat\Values\AttackType;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use PDOException;
use RuntimeException;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateExplorationLog;
use Tests\Traits\CreateExplorationWarning;
use Tests\Traits\CreateMonster;

class ExplorationLogServiceTest extends TestCase
{
    use CreateCharacterAutomation, CreateExplorationLog, CreateExplorationWarning, CreateMonster, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?ExplorationLogService $explorationLogService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->explorationLogService = resolve(ExplorationLogService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->explorationLogService = null;
    }

    public function test_start_creates_a_running_exploration_log(): void
    {
        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $log = $this->explorationLogService->start($character, $automation);

        $this->assertSame($character->id, $log->character_id);
        $this->assertSame('running', $log->stopped_reason);
    }

    public function test_record_fight_totals_accumulates_totals_and_currencies(): void
    {
        $character = $this->character->getCharacter();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'fights' => 1,
            'kills' => 1,
        ]);

        $this->explorationLogService->recordFightTotals($log, [
            'fights' => 2,
            'kills' => 1,
            'weapon_damage' => 10,
            'xp_gained' => 100,
            'currencies_gained' => ['gold' => 50],
            'healing_done' => 5,
            'damage_blocked' => 3,
            'monster' => ['id' => 1, 'name' => 'Test Monster'],
        ], false);

        $log = $log->fresh();

        $this->assertSame(3, $log->fights);
        $this->assertSame(2, $log->kills);
        $this->assertSame(50, $log->currencies_gained['gold']);
        $this->assertSame(5, $log->currencies_gained['healing_done']);
        $this->assertSame(3, $log->currencies_gained['damage_blocked']);
        $this->assertSame('Test Monster', $log->summary['monster']['name']);
    }

    public function test_record_fight_totals_broadcasts_output_by_default(): void
    {
        $character = $this->character->getCharacter();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'ended_at' => null,
        ]);

        $this->explorationLogService->recordFightTotals($log, ['fights' => 1]);

        $this->assertSame(1, $log->fresh()->fights);
    }

    public function test_record_monster_snapshot_stores_monster_data_in_summary(): void
    {
        $character = $this->character->getCharacter();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $this->explorationLogService->recordMonsterSnapshot($log, ['id' => 5, 'name' => 'Snapshot Monster'], false);

        $this->assertSame('Snapshot Monster', $log->fresh()->summary['monster']['name']);
    }

    public function test_record_monster_snapshot_broadcasts_output_by_default(): void
    {
        $character = $this->character->getCharacter();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'ended_at' => null,
        ]);

        $this->explorationLogService->recordMonsterSnapshot($log, ['id' => 5, 'name' => 'Broadcast Monster']);

        $this->assertSame('Broadcast Monster', $log->fresh()->summary['monster']['name']);
    }

    public function test_record_current_round_creatures_stores_the_count_in_summary(): void
    {
        $character = $this->character->getCharacter();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $this->explorationLogService->recordCurrentRoundCreatures($log, 4, false);

        $this->assertSame(4, $log->fresh()->summary['current_round_creatures']);
    }

    public function test_record_current_round_creatures_broadcasts_output_by_default(): void
    {
        $character = $this->character->getCharacter();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'ended_at' => null,
        ]);

        $this->explorationLogService->recordCurrentRoundCreatures($log, 7);

        $this->assertSame(7, $log->fresh()->summary['current_round_creatures']);
    }

    public function test_finalize_ends_the_log_and_records_summary(): void
    {
        $character = $this->character->getCharacter();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'fights' => 5,
            'kills' => 3,
        ]);

        $this->explorationLogService->finalize($log, 'died', true);

        $log = $log->fresh();

        $this->assertNotNull($log->ended_at);
        $this->assertSame('died', $log->stopped_reason);
        $this->assertTrue($log->stopped_by_player);
        $this->assertSame(5, $log->summary['fights']);
    }

    public function test_latest_for_character_returns_the_most_recent_log(): void
    {
        $character = $this->character->getCharacter();

        $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $latest = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        $result = $this->explorationLogService->latestForCharacter($character);

        $this->assertSame($latest->id, $result->id);
    }

    public function test_active_for_character_returns_only_the_undismissed_running_log(): void
    {
        $character = $this->character->getCharacter();

        $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'ended_at' => now(),
        ]);

        $activeLog = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'ended_at' => null,
        ]);

        $result = $this->explorationLogService->activeForCharacter($character);

        $this->assertSame($activeLog->id, $result->id);
    }

    public function test_apply_reward_context_records_currency_deltas_and_context_totals(): void
    {
        $character = $this->character->getCharacter();
        $character->update(['gold' => 500, 'gold_dust' => 10]);
        $character = $character->refresh();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        ExplorationLogService::applyRewardContext(
            $log,
            $character,
            ['gold' => 100, 'gold_dust' => 10],
            ['total_xp' => 50, 'total_skill_xp' => 25, 'total_faction_points' => 5]
        );

        $log = $log->fresh();

        $this->assertSame(400, $log->currencies_gained['gold']);
        $this->assertArrayNotHasKey('gold_dust', $log->currencies_gained);
        $this->assertSame(50, $log->xp_gained);
        $this->assertSame(25, $log->skill_xp_gained);
        $this->assertSame(5, $log->faction_points_gained);
    }

    public function test_apply_reward_context_logs_a_warning_when_broadcasting_warning_state_fails(): void
    {
        $character = $this->character->getCharacter();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        Event::listen(ExplorationWarningState::class, function (): void {
            throw new RuntimeException('Broadcast connection failed.');
        });

        Log::spy();

        ExplorationLogService::applyRewardContext($log, $character, [], ['total_xp' => 42]);

        $this->assertSame(42, $log->fresh()->xp_gained);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('ExplorationLogService::applyRewardContext failed to broadcast ExplorationWarningState.', Mockery::on(
                fn (array $context): bool => $context['character_id'] === $character->id
            ));
    }

    public function test_apply_reward_context_logs_a_warning_when_broadcasting_exploration_output_fails(): void
    {
        $character = $this->character->getCharacter();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
        ]);

        Event::listen(ExplorationOutputUpdated::class, function (): void {
            throw new RuntimeException('Broadcast connection failed.');
        });

        Log::spy();

        ExplorationLogService::applyRewardContext($log, $character, [], ['total_skill_xp' => 7]);

        $this->assertSame(7, $log->fresh()->skill_xp_gained);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('ExplorationLogService::applyRewardContext failed to broadcast exploration output.', Mockery::on(
                fn (array $context): bool => $context['character_id'] === $character->id
            ));
    }

    public function test_clear_without_warning_deletes_the_active_log(): void
    {
        $character = $this->character->getCharacter();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'ended_at' => null,
        ]);

        $this->explorationLogService->clear($character);

        $this->assertNull(ExplorationLog::find($log->id));
    }

    public function test_clear_with_warning_dismisses_the_warning_and_referenced_log(): void
    {
        $character = $this->character->getCharacter();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'ended_at' => now(),
            'panel_dismissed_at' => null,
        ]);

        $warning = $this->createExplorationWarning([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'exploration_log_id' => $log->id,
            'type' => 'fight',
            'message' => 'Something happened.',
        ]);

        $this->explorationLogService->clear($character, $warning);

        $this->assertNotNull($warning->fresh()->dismissed_at);
        $this->assertNotNull($log->fresh()->panel_dismissed_at);
    }

    public function test_dismiss_ended_log_marks_ended_logs_as_dismissed(): void
    {
        $character = $this->character->getCharacter();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'ended_at' => now(),
            'panel_dismissed_at' => null,
        ]);

        $this->explorationLogService->dismissEndedLog($character);

        $this->assertNotNull($log->fresh()->panel_dismissed_at);
    }

    public function test_output_for_character_repairs_active_log_with_missing_automation(): void
    {
        $character = $this->character->getCharacter();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => 999999,
            'ended_at' => null,
        ]);

        $result = $this->explorationLogService->outputForCharacter($character);

        $log = $log->fresh();

        $this->assertNotNull($log->ended_at);
        $this->assertSame('missing_automation', $log->stopped_reason);
        $this->assertSame('warning', $result['type']);
    }

    public function test_output_for_character_repairs_active_log_and_skips_warning_after_retryable_lock_error(): void
    {
        $character = $this->character->getCharacter();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => 999999,
            'ended_at' => null,
        ]);

        ExplorationWarning::creating(function (): void {
            throw new QueryException('mysql', 'insert into exploration_warnings', [], new PDOException('Lock wait timeout exceeded', 1205));
        });

        $result = $this->explorationLogService->outputForCharacter($character);

        $log = $log->fresh();

        $this->assertNotNull($log->ended_at);
        $this->assertSame('missing_automation', $log->stopped_reason);
        $this->assertSame(0, ExplorationWarning::where('exploration_log_id', $log->id)->count());
        $this->assertNotSame('warning', $result['type']);
    }

    public function test_output_for_character_rethrows_non_retryable_query_exception_during_repair(): void
    {
        $character = $this->character->getCharacter();

        $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => 999999,
            'ended_at' => null,
        ]);

        ExplorationWarning::creating(function (): void {
            throw new QueryException('mysql', 'insert into exploration_warnings', [], new PDOException('Column not found', 1054));
        });

        $this->expectException(QueryException::class);

        $this->explorationLogService->outputForCharacter($character);
    }

    public function test_output_for_character_returns_warning_type_when_undismissed_warning_exists(): void
    {
        $character = $this->character->getCharacter();

        $this->createExplorationWarning([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'exploration_log_id' => null,
            'type' => 'fight',
            'message' => 'Something happened.',
            'dismissed_at' => null,
        ]);

        $result = $this->explorationLogService->outputForCharacter($character);

        $this->assertSame('warning', $result['type']);
    }

    public function test_output_for_character_returns_null_type_when_nothing_to_report(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->explorationLogService->outputForCharacter($character);

        $this->assertNull($result['type']);
        $this->assertNull($result['output']);
    }

    public function test_output_for_character_uses_snapshot_health_and_attack_damage_when_present(): void
    {
        $character = $this->character->getCharacter();

        $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'ended_at' => now(),
            'panel_dismissed_at' => null,
            'summary' => [
                'monster' => [
                    'stats' => [
                        'health' => 50,
                        'attack_damage' => 20,
                    ],
                ],
            ],
        ]);

        $result = $this->explorationLogService->outputForCharacter($character);

        $this->assertSame(50, $result['output']['monster']['stats']['health']);
        $this->assertSame(20, $result['output']['monster']['stats']['attack_damage']);
    }
}
