<?php

namespace Tests\Unit\Game\Automation\Jobs;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\DelveLog;
use App\Flare\Models\Session;
use App\Game\Automation\Enums\DelveOutcome;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Jobs\DelveExploration;
use App\Game\Automation\Values\AutomationType;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\BattleRewardProcessing\Services\CharacterRewardService;
use App\Game\Character\Exceptions\MissingInventoryException;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\SkillService;
use App\Game\Tops\Services\BroadcastTopsUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateDelveExploration;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class DelveExplorationTest extends TestCase
{
    use CreateCharacterAutomation, CreateDelveExploration, CreateItem, CreateLocation, CreateMonster, MockeryPHPUnitIntegration, RefreshDatabase;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->instance(BroadcastTopsUpdateService::class, Mockery::mock(BroadcastTopsUpdateService::class, function (MockInterface $mock) {
            $mock->shouldReceive('broadcastDelveCurrentMonth');
        }));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_handle_returns_silently_when_character_is_missing(): void
    {
        Event::fake();

        $location = $this->createLocation();

        DelveExploration::dispatch(999999, $location->id, 1, 1, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertTrue(true);
    }

    public function test_handle_ends_automation_when_location_is_missing(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addHour(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
        ]);

        DelveExploration::dispatch($character->id, 999999, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_marks_user_for_deletion_when_character_has_no_inventory(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $character->inventory()->delete();

        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'completed_at' => now()->addHour(),
        ]);

        DelveExploration::dispatch($character->id, $location->id, $automation->id, 1, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertTrue($character->user->fresh()->will_be_deleted);
    }

    public function test_handle_ends_automation_when_character_automation_is_missing(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $location = $this->createLocation();

        DelveExploration::dispatch($character->id, $location->id, 999999, 1, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertTrue(true);
    }

    public function test_handle_ends_automation_when_delve_automation_record_is_missing(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'completed_at' => now()->addHour(),
        ]);

        DelveExploration::dispatch($character->id, $location->id, $automation->id, 999999, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_ends_automation_when_automation_time_is_up(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $character->update(['gold' => 0]);
        $character = $character->refresh();

        $location = $this->createLocation();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->subMinute(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
        ]);

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertNotNull($delveAutomation->fresh()->completed_at);
        $this->assertSame('natural_end', $delveAutomation->fresh()->ended_reason);
        $this->assertSame(1_000, $character->refresh()->gold);
    }

    public function test_handle_ends_automation_when_delve_already_completed(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $location = $this->createLocation();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addHour(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
            'ended_reason' => 'fight_failed',
        ]);

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertSame('fight_failed', $delveAutomation->fresh()->ended_reason);
    }

    public function test_handle_processes_monster_death_and_reschedules_when_round_completes(): void
    {
        Event::fake();
        config(['queue.connections.long_running.driver' => 'null']);

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $nextMonster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addHour(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
            'increase_enemy_strength' => 0,
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

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
        $this->assertSame(0.05, $delveAutomation->fresh()->increase_enemy_strength);
    }

    public function test_handle_caps_enemy_strength_increase_at_maximum_and_skips_redundant_update(): void
    {
        Event::fake();
        config(['queue.connections.long_running.driver' => 'null']);

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);

        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addHour(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
            'increase_enemy_strength' => 1000.00,
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

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertSame(1000.00, $delveAutomation->fresh()->increase_enemy_strength);
    }

    public function test_handle_ends_automation_via_timeout_after_max_fight_attempts(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $character->update(['gold' => 0]);
        $character = $character->refresh();

        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addHour(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($monster) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 10],
            ]);
            $mock->shouldReceive('getMonster')->andReturn($monster);
            $mock->shouldReceive('fightMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 10],
            ]);
        }));

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertSame(DelveOutcome::TIMEOUT->value, $delveAutomation->fresh()->ended_reason);
        $this->assertSame(1, DelveLog::where('delve_exploration_id', $delveAutomation->id)->where('outcome', DelveOutcome::TIMEOUT->value)->count());
    }

    public function test_handle_marks_user_for_deletion_on_missing_inventory_exception_mid_handle(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addHour(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setupMonster')->andThrow(new MissingInventoryException('No inventory found.'));
            $mock->shouldReceive('getMonster');
        }));

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertTrue($character->user->fresh()->will_be_deleted);
        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_survives_pack_of_multiple_enemies_and_ends_naturally(): void
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

        $character = $this->character->getCharacter();
        $character->update(['gold' => 0]);
        $character = $character->refresh();

        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addMinute(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
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

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value, 'pack_size' => 3], 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertSame('natural_end', $delveAutomation->fresh()->ended_reason);
    }

    public function test_handle_applies_pack_size_five_xp_multiplier(): void
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

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addHour(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
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
            $mock->shouldReceive('processMonsterDeath')->once()->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(fn (array $battleData): bool => ($battleData['total_xp'] ?? null) == 100.0)
            );
        }));

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value, 'pack_size' => 5], 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_applies_pack_size_ten_xp_multiplier(): void
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

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addHour(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
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
            $mock->shouldReceive('processMonsterDeath')->once()->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(fn (array $battleData): bool => ($battleData['total_xp'] ?? null) == 225.0)
            );
        }));

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value, 'pack_size' => 10], 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_applies_pack_size_twenty_xp_multiplier(): void
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

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addHour(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
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
            $mock->shouldReceive('processMonsterDeath')->once()->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(fn (array $battleData): bool => ($battleData['total_xp'] ?? null) == 500.0)
            );
        }));

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value, 'pack_size' => 20], 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_applies_pack_size_twenty_five_xp_multiplier(): void
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

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addHour(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
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
            $mock->shouldReceive('processMonsterDeath')->once()->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(fn (array $battleData): bool => ($battleData['total_xp'] ?? null) == 687)
            );
        }));

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value, 'pack_size' => 25], 3);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function test_handle_stops_pack_fight_when_character_dies_against_a_pack_member(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addHour(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($monster) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 0, 'current_monster_health' => 10],
            ]);
            $mock->shouldReceive('getMonster')->andReturn($monster);
        }));

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value, 'pack_size' => 3], 3);

        $this->assertSame(DelveOutcome::DIED->value, $delveAutomation->fresh()->ended_reason);
        $this->assertSame(0, CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::DELVE->value)->count());
    }

    public function test_handle_ends_automation_when_character_dies_during_setup(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addHour(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($monster) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 0, 'current_monster_health' => 10],
            ]);
            $mock->shouldReceive('getMonster')->andReturn($monster);
        }));

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertSame(DelveOutcome::DIED->value, $delveAutomation->fresh()->ended_reason);
        $this->assertSame(0, CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::DELVE->value)->count());
    }

    public function test_handle_ends_automation_when_character_dies_during_fight(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->addHour(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
        ]);

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($monster) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 10],
            ]);
            $mock->shouldReceive('getMonster')->andReturn($monster);
            $mock->shouldReceive('fightMonster')->andReturn([
                'health' => ['current_character_health' => 0, 'current_monster_health' => 5],
            ]);
        }));

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertSame(DelveOutcome::DIED->value, $delveAutomation->fresh()->ended_reason);
        $this->assertSame(0, CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::DELVE->value)->count());
    }

    public function test_handle_retries_fight_until_monster_is_defeated(): void
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

        $character = $this->character->getCharacter();
        $character->update(['gold' => 0]);
        $character = $character->refresh();

        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->subMinute(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
        ]);

        $callCount = 0;

        $this->instance(MonsterFightService::class, Mockery::mock(MonsterFightService::class, function (MockInterface $mock) use ($monster, &$callCount) {
            $mock->shouldReceive('setupMonster')->andReturn([
                'health' => ['current_character_health' => 10, 'current_monster_health' => 10],
            ]);
            $mock->shouldReceive('getMonster')->andReturn($monster);
            $mock->shouldReceive('fightMonster')->andReturnUsing(function () use (&$callCount) {
                $callCount++;

                return [
                    'health' => ['current_character_health' => 10, 'current_monster_health' => $callCount >= 2 ? 0 : 5],
                ];
            });
        }));

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertSame('natural_end', $delveAutomation->fresh()->ended_reason);
    }

    public function test_handle_rewards_cosmic_item_when_delve_lasts_over_six_hours(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $character->update(['gold' => 0]);
        $character = $character->refresh();

        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $location = $this->createLocation();
        $item = $this->createItem();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->subMinute(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHours(7),
            'completed_at' => null,
        ]);

        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) use ($item) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('getSpecialGearDrop')->twice()->andReturn($item);
        }));

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertSame(1_000_001_000_000, $character->refresh()->gold);
        $this->assertSame(2, $character->inventory->fresh()->slots()->where('item_id', $item->id)->count());
    }

    public function test_handle_rewards_mythic_item_when_delve_lasts_between_four_and_six_hours(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $character->update(['gold' => 0]);
        $character = $character->refresh();

        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $location = $this->createLocation();
        $item = $this->createItem();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->subMinute(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHours(5),
            'completed_at' => null,
        ]);

        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) use ($item) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('getSpecialGearDrop')->twice()->andReturn($item);
        }));

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertSame(1_001_000_000, $character->refresh()->gold);
        $this->assertSame(2, $character->inventory->fresh()->slots()->where('item_id', $item->id)->count());
    }

    public function test_handle_rewards_unique_item_when_delve_lasts_over_two_hours(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $character->update(['gold' => 0]);
        $character = $character->refresh();

        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $location = $this->createLocation();
        $item = $this->createItem();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->subMinute(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHours(3),
            'completed_at' => null,
        ]);

        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) use ($item) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('getSpecialGearDrop')->once()->andReturn($item);
        }));

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertSame(1_000_000, $character->refresh()->gold);
        $this->assertSame(1, $character->inventory->fresh()->slots()->where('item_id', $item->id)->count());
    }

    public function test_handle_caps_reward_gold_at_currency_limit(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $character->update(['gold' => CurrencyLimit::MAX_GOLD - 100]);
        $character = $character->refresh();

        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $location = $this->createLocation();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->subMinute(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHour(),
            'completed_at' => null,
        ]);

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value], 3);

        $this->assertSame(CurrencyLimit::MAX_GOLD, $character->refresh()->gold);
    }

    public function test_handle_broadcasts_log_update_and_server_message_when_character_is_logged_in(): void
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
        $location = $this->createLocation();
        $item = $this->createItem();

        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'monster_id' => $monster->id,
            'completed_at' => now()->subMinute(),
        ]);

        $delveAutomation = $this->createDelveExploration([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'started_at' => now()->subHours(3),
            'completed_at' => null,
        ]);

        $this->instance(CharacterRewardService::class, Mockery::mock(CharacterRewardService::class, function (MockInterface $mock) use ($item) {
            $mock->shouldReceive('setCharacter')->andReturnSelf();
            $mock->shouldReceive('getSpecialGearDrop')->once()->andReturn($item);
        }));

        DelveExploration::dispatch($character->id, $location->id, $automation->id, $delveAutomation->id, ['attack_type' => AttackType::ATTACK->value], 3);

        Event::assertDispatched(AutomationLogUpdate::class);
        Event::assertDispatched(ServerMessageEvent::class);
    }
}
