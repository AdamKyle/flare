<?php

namespace Tests\Unit\Game\Automation\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Flare\Models\Session;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Jobs\Exploration;
use App\Game\Automation\Services\ExplorationCreatureCountCalculator;
use App\Game\Automation\Values\AutomationType;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\BattleRewardProcessing\Services\CharacterRewardService;
use App\Game\Character\Exceptions\MissingInventoryException;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Skills\Services\SkillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateExplorationLog;
use Tests\Traits\CreateMonster;

class ExplorationTest extends TestCase
{
    use CreateCharacterAutomation, CreateExplorationLog, CreateMonster, MockeryPHPUnitIntegration, RefreshDatabase;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_handle_returns_silently_when_automation_and_log_are_both_missing(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();

        Exploration::dispatch($character, 999999, AttackType::ATTACK->value, 3);

        $this->assertTrue(true);
    }

    public function test_handle_repairs_and_finalizes_log_when_automation_is_missing_but_log_exists(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => 999999,
            'ended_at' => null,
        ]);

        Exploration::dispatch($character, 999999, AttackType::ATTACK->value, 3);

        $this->assertNotNull($log->fresh()->ended_at);
        $this->assertSame('missing_automation', $log->fresh()->stopped_reason);
    }

    public function test_handle_ends_automation_when_time_is_up(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $character->update(['gold' => 0]);
        $character = $character->refresh();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertSame(10_000, $character->refresh()->gold);
    }

    public function test_handle_processes_monster_death_and_ends_automation_when_round_completes(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($monster) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
            ]);
            $mock->shouldReceive('fightMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
            ]);
            $mock->shouldReceive('getMonster')->andReturn($monster);
        }));

        $this->instance(BattleEventHandler::class, Mockery::mock(BattleEventHandler::class, function (MockInterface $mock) use ($character, $monster) {
            $mock->shouldReceive('processMonsterDeath')->once()->with($character->id, $monster->id, Mockery::type('array'));
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_records_fight_totals_and_monster_snapshot_when_log_exists(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => $automation->id,
            'ended_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($monster) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
                'monster' => ['id' => $monster->id, 'name' => $monster->name],
            ]);
            $mock->shouldReceive('fightMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
            ]);
            $mock->shouldReceive('getMonster')->andReturn($monster);
        }));

        $this->instance(BattleEventHandler::class, Mockery::mock(BattleEventHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('processMonsterDeath');
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $log = $log->fresh();

        $this->assertSame(1, $log->fights);
        $this->assertSame($monster->name, $log->summary['monster']['name']);
    }

    public function test_handle_cancels_automation_when_fight_setup_data_is_malformed(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setupMonster')->andReturn(['unexpected' => true]);
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_cancels_automation_when_character_dies_during_fight(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => $automation->id,
            'ended_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 0, 'current_monster_health' => 10],
            ]);
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertSame('character_died', $log->fresh()->stopped_reason);

        $warning = ExplorationWarning::where('character_id', $character->id)->first();
        $this->assertSame('character_died', $warning->type);
    }

    public function test_handle_returns_silently_when_automation_deleted_externally_after_failed_fight(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($automation) {
            $mock->shouldReceive('setupMonster')->andReturnUsing(function () use ($automation) {
                CharacterAutomation::where('id', $automation->id)->delete();

                return ['unexpected' => true];
            });
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_marks_user_for_deletion_on_missing_inventory_exception(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setupMonster')->andThrow(new MissingInventoryException('No inventory found.'));
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertTrue($character->user->fresh()->will_be_deleted);
        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_handles_unexpected_exception_via_handle_failure(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setupMonster')->andThrow(new \RuntimeException('Something broke.'));
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_advances_to_next_monster_when_level_threshold_reached(): void
    {
        Event::fake();
        config(['queue.connections.long_running.driver' => 'null']);
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $character->update(['level' => 5]);
        $character = $character->refresh();

        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $nextMonster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'move_down_monster_list_every' => 1,
            'previous_level' => 1,
            'current_level' => 1,
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($nextMonster) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
            ]);
            $mock->shouldReceive('fightMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
            ]);
            $mock->shouldReceive('getMonster')->andReturn($nextMonster);
        }));

        $this->instance(BattleEventHandler::class, Mockery::mock(BattleEventHandler::class, function (MockInterface $mock) use ($character, $nextMonster) {
            $mock->shouldReceive('processMonsterDeath')->once()->with($character->id, $nextMonster->id, Mockery::type('array'));
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
        $this->assertSame($nextMonster->id, CharacterAutomation::find($automation->id)->monster_id);
    }

    public function test_failed_reports_and_handles_failure(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $job = new Exploration($character, $automation->id, AttackType::ATTACK->value, 3);

        $job->failed(new \RuntimeException('Queue failure.'));

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function test_failed_finalizes_active_exploration_log_and_creates_warning_when_log_exists(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => $automation->id,
            'ended_at' => null,
        ]);

        $job = new Exploration($character, $automation->id, AttackType::ATTACK->value, 3);

        $job->failed(new \RuntimeException('Queue failure.'));

        $this->assertSame('queue_job_failed', $log->fresh()->stopped_reason);

        $warning = ExplorationWarning::where('exploration_log_id', $log->id)->first();
        $this->assertSame('queue_job_failed', $warning->type);
    }

    public function test_handle_includes_exploration_log_id_in_reward_context_when_round_completes_with_active_log(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => $automation->id,
            'ended_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($monster) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
            ]);
            $mock->shouldReceive('fightMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
            ]);
            $mock->shouldReceive('getMonster')->andReturn($monster);
        }));

        $this->instance(BattleEventHandler::class, Mockery::mock(BattleEventHandler::class, function (MockInterface $mock) use ($character, $monster, $log) {
            $mock->shouldReceive('processMonsterDeath')->once()->with(
                $character->id,
                $monster->id,
                Mockery::on(fn (array $context): bool => ($context['exploration_log_id'] ?? null) === $log->id)
            );
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_cancels_automation_when_fight_monster_returns_empty_data(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 10],
            ]);
            $mock->shouldReceive('fightMonster')->andReturn([]);
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_cancels_automation_with_malformed_reason_when_fight_monster_returns_incomplete_health_data(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => $automation->id,
            'ended_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 10],
            ]);
            $mock->shouldReceive('fightMonster')->andReturn(['unexpected' => true]);
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertSame('malformed_fight_data', $log->fresh()->stopped_reason);
    }

    public function test_handle_cancels_automation_when_character_dies_from_fight_response(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => $automation->id,
            'ended_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 10],
            ]);
            $mock->shouldReceive('fightMonster')->andReturn([
                'health' => ['current_character_health' => 0, 'current_monster_health' => 5],
            ]);
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertSame('character_died', $log->fresh()->stopped_reason);
    }

    public function test_handle_cancels_automation_when_max_fight_attempts_are_exceeded(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => $automation->id,
            'ended_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 10],
            ]);
            $mock->shouldReceive('fightMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 10],
            ]);
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertSame('max_attempts_reached', $log->fresh()->stopped_reason);
    }

    public function test_handle_broadcasts_log_update_when_character_is_logged_in(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $character->update(['gold' => 0]);
        $character = $character->refresh();

        $session = new Session;
        $session->timestamps = false;
        $session->forceFill([
            'id' => Str::random(40),
            'user_id' => $character->user_id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => base64_encode('a:0:{}'),
            'last_activity' => now()->timestamp,
        ])->save();

        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function test_handle_caps_reward_gold_at_currency_limit_when_automation_ends(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $character->update(['gold' => CurrencyLimit::MAX_GOLD - 100]);
        $character = $character->refresh();

        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertSame(CurrencyLimit::MAX_GOLD, $character->refresh()->gold);
    }

    public function test_handle_skips_monster_snapshot_when_setup_data_missing_monster_key(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => $automation->id,
            'ended_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($monster) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
            ]);
            $mock->shouldReceive('fightMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
            ]);
            $mock->shouldReceive('getMonster')->andReturn($monster);
        }));

        $this->instance(BattleEventHandler::class, Mockery::mock(BattleEventHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('processMonsterDeath');
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertArrayNotHasKey('monster', $log->fresh()->summary);
    }

    public function test_handle_skips_monster_snapshot_when_monster_id_missing(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => $automation->id,
            'ended_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($monster) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
                'monster' => ['name' => 'Nameless Ghost'],
            ]);
            $mock->shouldReceive('fightMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
            ]);
            $mock->shouldReceive('getMonster')->andReturn($monster);
        }));

        $this->instance(BattleEventHandler::class, Mockery::mock(BattleEventHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('processMonsterDeath');
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertArrayNotHasKey('monster', $log->fresh()->summary);
    }

    public function test_handle_records_monster_snapshot_with_overridden_attack_damage_and_explicit_max_health(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => $automation->id,
            'ended_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($monster) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0, 'max_monster_health' => 100],
                'monster' => ['id' => $monster->id, 'name' => $monster->name],
            ]);
            $mock->shouldReceive('fightMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
                'attack_damage' => 25,
            ]);
            $mock->shouldReceive('getMonster')->andReturn($monster);
        }));

        $this->instance(BattleEventHandler::class, Mockery::mock(BattleEventHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('processMonsterDeath');
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $summary = $log->fresh()->summary;

        $this->assertSame(25, $summary['monster']['stats']['attack_damage']);
        $this->assertSame(100, $summary['monster']['stats']['health']);
    }

    public function test_handle_falls_back_to_flat_attack_range_and_applies_damage_increase_in_snapshot(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => $automation->id,
            'ended_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($monster) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
                'monster' => [
                    'id' => $monster->id,
                    'name' => $monster->name,
                    'attack_range' => 20,
                    'increases_damage_by' => 0.5,
                ],
            ]);
            $mock->shouldReceive('fightMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
            ]);
            $mock->shouldReceive('getMonster')->andReturn($monster);
        }));

        $this->instance(BattleEventHandler::class, Mockery::mock(BattleEventHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('processMonsterDeath');
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertSame(30, $log->fresh()->summary['monster']['stats']['attack_damage']);
    }

    public function test_handle_extracts_battle_message_totals_and_skips_invalid_message_entries(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => $automation->id,
            'ended_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($monster) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
            ]);
            $mock->shouldReceive('fightMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
                'messages' => [
                    'not-an-array-entry',
                    ['no_message_key' => 'x'],
                    ['message' => 'Your weapon hits Goblin for: 50'],
                ],
            ]);
            $mock->shouldReceive('getMonster')->andReturn($monster);
        }));

        $this->instance(BattleEventHandler::class, Mockery::mock(BattleEventHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('processMonsterDeath');
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertSame(50, $log->fresh()->weapon_damage);
    }

    public function test_handle_includes_exploration_log_id_in_reward_context_when_round_completes_and_reschedules(): void
    {
        Event::fake();
        config(['queue.connections.long_running.driver' => 'null']);
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $log = $this->createExplorationLog([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => $automation->id,
            'ended_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($monster) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
            ]);
            $mock->shouldReceive('fightMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 0],
            ]);
            $mock->shouldReceive('getMonster')->andReturn($monster);
        }));

        $this->instance(BattleEventHandler::class, Mockery::mock(BattleEventHandler::class, function (MockInterface $mock) use ($character, $monster, $log) {
            $mock->shouldReceive('processMonsterDeath')->once()->with(
                $character->id,
                $monster->id,
                Mockery::on(fn (array $context): bool => ($context['exploration_log_id'] ?? null) === $log->id)
            );
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_failure_returns_early_when_automation_already_deleted(): void
    {
        Event::fake();
        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('fetchXpForMonster')->andReturn(10);
        }));

        $this->instance(SkillService::class, Mockery::mock(SkillService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setSkillInTraining')->andReturnSelf();
            $mock->shouldReceive('getXpForSkillIntraining')->andReturn(5);
        }));

        $this->instance(ExplorationCreatureCountCalculator::class, Mockery::mock(ExplorationCreatureCountCalculator::class, function (MockInterface $mock) {
            $mock->shouldReceive('calculate')->andReturn(1);
        }));

        $this->instance(FactionHandler::class, Mockery::mock(FactionHandler::class, function (MockInterface $mock) {
            $mock->shouldReceive('getFactionPointsPerKill')->andReturn(0);
        }));

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now(),
            'completed_at' => now()->addMinute(),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($automation) {
            $mock->shouldReceive('setupMonster')->andReturnUsing(function () use ($automation) {
                CharacterAutomation::where('id', $automation->id)->delete();

                throw new \RuntimeException('Something broke after deletion.');
            });
        }));

        Exploration::dispatch($character, $automation->id, AttackType::ATTACK->value, 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }
}
