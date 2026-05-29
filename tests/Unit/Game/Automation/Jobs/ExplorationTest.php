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
use App\Game\Automation\Services\ExplorationAutomationService;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Skills\Services\SkillService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
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

        Carbon::setTestNow();

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

        Exploration::dispatchSync($this->character, 999999, AttackTypeValue::ATTACK, 5);

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

        Exploration::dispatchSync($this->character, $automation->id + 1, AttackTypeValue::ATTACK, 5);

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

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

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

        $this->instance(MonsterFightService::class, $monsterFightService);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

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

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->assertEquals(10010, $this->character->refresh()->gold);
    }

    public function testBeginExplorationAcceptsClearedSelectionsAndUsesDefaultValues(): void
    {
        Event::fake();
        Queue::fake();

        resolve(ExplorationAutomationService::class)->beginAutomation($this->character, [
            'auto_attack_length' => null,
            'move_down_the_list_every' => null,
            'selected_monster_id' => null,
            'attack_type' => null,
        ]);

        $automation = CharacterAutomation::where('character_id', $this->character->id)->first();
        $monster = Monster::find($automation->monster_id);

        $this->assertNotNull($automation);
        $this->assertNotNull($monster);
        $this->assertEquals($this->character->map->game_map_id, $monster->game_map_id);
        $this->assertEquals(AttackTypeValue::ATTACK, $automation->attack_type);
        $this->assertNull($automation->move_down_monster_list_every);
        $this->assertTrue($automation->completed_at->greaterThan(now()));
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

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

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

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

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

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

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

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

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

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function testHandleWithZeroFightTimeoutModifierFightsSixRealCreatures(): void
    {
        Event::fake();

        $characterStatBuilder = Mockery::mock(CharacterStatBuilder::class);
        $characterStatBuilder->shouldReceive('setCharacter')
            ->once()
            ->with(Mockery::type(Character::class))
            ->andReturnSelf();
        $characterStatBuilder->shouldReceive('buildTimeOutModifier')
            ->once()
            ->with('fight_time_out')
            ->andReturn(0);

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
        $monsterFightService->shouldReceive('setupMonster')->times(6)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('fightMonster')->times(6)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')
            ->once()
            ->withArgs(function (int $characterId, int $monsterId, array $context): bool {
                Queue::fake();

                return $characterId === $this->character->id &&
                    $monsterId === $this->monster->id &&
                    $context['total_creatures'] === 6;
            });

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

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

        $this->instance(CharacterStatBuilder::class, $characterStatBuilder);
        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleWithFullFightTimeoutModifierFightsTwelveRealCreatures(): void
    {
        Event::fake();

        $characterStatBuilder = Mockery::mock(CharacterStatBuilder::class);
        $characterStatBuilder->shouldReceive('setCharacter')
            ->once()
            ->with(Mockery::type(Character::class))
            ->andReturnSelf();
        $characterStatBuilder->shouldReceive('buildTimeOutModifier')
            ->once()
            ->with('fight_time_out')
            ->andReturn(1);

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
        $monsterFightService->shouldReceive('setupMonster')->times(12)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('fightMonster')->times(12)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')
            ->once()
            ->withArgs(function (int $characterId, int $monsterId, array $context): bool {
                Queue::fake();

                return $characterId === $this->character->id &&
                    $monsterId === $this->monster->id &&
                    $context['total_creatures'] === 12;
            });

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

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

        $this->instance(CharacterStatBuilder::class, $characterStatBuilder);
        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleWithPartialFightTimeoutModifierRoundsCreatureCountDown(): void
    {
        Event::fake();

        $characterStatBuilder = Mockery::mock(CharacterStatBuilder::class);
        $characterStatBuilder->shouldReceive('setCharacter')
            ->once()
            ->with(Mockery::type(Character::class))
            ->andReturnSelf();
        $characterStatBuilder->shouldReceive('buildTimeOutModifier')
            ->once()
            ->with('fight_time_out')
            ->andReturn(0.5);

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
        $monsterFightService->shouldReceive('setupMonster')->times(8)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('fightMonster')->times(8)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')
            ->once()
            ->withArgs(function (int $characterId, int $monsterId, array $context): bool {
                Queue::fake();

                return $characterId === $this->character->id &&
                    $monsterId === $this->monster->id &&
                    $context['total_creatures'] === 8;
            });

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

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

        $this->instance(CharacterStatBuilder::class, $characterStatBuilder);
        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleCreatureCountIsNeverBelowSix(): void
    {
        Event::fake();

        $characterStatBuilder = Mockery::mock(CharacterStatBuilder::class);
        $characterStatBuilder->shouldReceive('setCharacter')
            ->once()
            ->with(Mockery::type(Character::class))
            ->andReturnSelf();
        $characterStatBuilder->shouldReceive('buildTimeOutModifier')
            ->once()
            ->with('fight_time_out')
            ->andReturn(-1);

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
        $monsterFightService->shouldReceive('setupMonster')->times(6)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('fightMonster')->times(6)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')
            ->once()
            ->withArgs(function (int $characterId, int $monsterId, array $context): bool {
                Queue::fake();

                return $characterId === $this->character->id &&
                    $monsterId === $this->monster->id &&
                    $context['total_creatures'] === 6;
            });

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

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

        $this->instance(CharacterStatBuilder::class, $characterStatBuilder);
        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleCreatureCountIsNeverAboveTwelve(): void
    {
        Event::fake();

        $characterStatBuilder = Mockery::mock(CharacterStatBuilder::class);
        $characterStatBuilder->shouldReceive('setCharacter')
            ->once()
            ->with(Mockery::type(Character::class))
            ->andReturnSelf();
        $characterStatBuilder->shouldReceive('buildTimeOutModifier')
            ->once()
            ->with('fight_time_out')
            ->andReturn(2);

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
        $monsterFightService->shouldReceive('setupMonster')->times(12)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('fightMonster')->times(12)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')
            ->once()
            ->withArgs(function (int $characterId, int $monsterId, array $context): bool {
                Queue::fake();

                return $characterId === $this->character->id &&
                    $monsterId === $this->monster->id &&
                    $context['total_creatures'] === 12;
            });

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

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

        $this->instance(CharacterStatBuilder::class, $characterStatBuilder);
        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleUsesVisibleEncounterCreatureCountForRewardData(): void
    {
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

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')
            ->once()
            ->withArgs(function (int $characterId, int $monsterId, array $context): bool {
                Queue::fake();

                $message = Event::dispatched(AutomationLogUpdate::class)
                    ->map(fn (array $event): string => $event[0]->message)
                    ->first(fn (string $automationMessage): bool => str_contains($automationMessage, 'there are:')) ?? '';

                preg_match('/there are: ([0-9]+) of them/', $message, $matches);

                return $characterId === $this->character->id &&
                    $monsterId === $this->monster->id &&
                    isset($matches[1]) &&
                    $context['total_creatures'] === (int) $matches[1];
            });

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

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

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleDispatchesNextExplorationJobAfterOneMinute(): void
    {
        Event::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');

        Carbon::setTestNow($now);

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

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')
            ->once()
            ->withArgs(function (int $characterId, int $monsterId, array $context): bool {
                Queue::fake();

                return $characterId === $this->character->id &&
                    $monsterId === $this->monster->id &&
                    $context['total_creatures'] >= 6;
            });

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

        $automation = CharacterAutomation::factory()->create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => $now,
            'completed_at' => $now->copy()->addHour(),
            'move_down_monster_list_every' => null,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        Queue::assertPushed(Exploration::class, function (Exploration $queuedJob) use ($now): bool {
            return $queuedJob->delay->toDateTimeString() === $now->copy()->addMinute()->toDateTimeString();
        });
    }

    public function testHandleDispatchesUpdateCharacterStatusWhenCharacterDiesDuringSetup(): void
    {
        Event::fake();

        Cache::put('can-character-survive-' . $this->character->id, true);

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

        $characterCacheData = Mockery::mock(CharacterCacheData::class);
        $characterCacheData->shouldReceive('deleteCharacterSheet')
            ->once()
            ->with(Mockery::type(Character::class));

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

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(CharacterCacheData::class, $characterCacheData);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
        $this->assertFalse(Cache::has('can-character-survive-' . $this->character->id));
        Event::assertDispatched(UpdateCharacterStatus::class);
        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testHandleDispatchesUpdateCharacterStatusWhenCharacterDiesDuringFight(): void
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

        $characterCacheData = Mockery::mock(CharacterCacheData::class);
        $characterCacheData->shouldReceive('deleteCharacterSheet')
            ->once()
            ->with(Mockery::type(Character::class));

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

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(CharacterCacheData::class, $characterCacheData);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
        Event::assertDispatched(UpdateCharacterStatus::class);
        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testHandleDispatchesUpdateCharacterStatusWhenSetupDataIsMissingHealthPayload(): void
    {
        Event::fake();

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn([
            'monster' => [
                'id' => $this->monster->id,
            ],
        ]);

        $characterCacheData = Mockery::mock(CharacterCacheData::class);
        $characterCacheData->shouldReceive('deleteCharacterSheet')
            ->once()
            ->with(Mockery::type(Character::class));

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

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(CharacterCacheData::class, $characterCacheData);

        Log::shouldReceive('error')
            ->once()
            ->with('Exploration automation received malformed battle data.', [
                'character_id' => $this->character->id,
                'automation_id' => $automation->id,
                'source' => 'setupMonster',
                'missing_or_invalid_payload' => ['health'],
            ]);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
        Event::assertDispatched(UpdateCharacterStatus::class);
        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testHandleDispatchesUpdateCharacterStatusWhenFightDataIsMissingHealthPayload(): void
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
            'monster' => [
                'id' => $this->monster->id,
            ],
        ]);

        $characterCacheData = Mockery::mock(CharacterCacheData::class);
        $characterCacheData->shouldReceive('deleteCharacterSheet')
            ->once()
            ->with(Mockery::type(Character::class));

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

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(CharacterCacheData::class, $characterCacheData);

        Log::shouldReceive('error')
            ->once()
            ->with('Exploration automation received malformed battle data.', [
                'character_id' => $this->character->id,
                'automation_id' => $automation->id,
                'source' => 'fightMonster',
                'missing_or_invalid_payload' => ['health'],
            ]);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
        Event::assertDispatched(UpdateCharacterStatus::class);
        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testHandleDispatchesUpdateCharacterStatusWhenFightCannotBeProcessed(): void
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
        $monsterFightService->shouldReceive('fightMonster')->andReturn([]);

        $characterCacheData = Mockery::mock(CharacterCacheData::class);
        $characterCacheData->shouldReceive('deleteCharacterSheet')
            ->once()
            ->with(Mockery::type(Character::class));

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

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(CharacterCacheData::class, $characterCacheData);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
        Event::assertDispatched(UpdateCharacterStatus::class);
        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testHandleDispatchesUpdateCharacterStatusWhenMaxAttemptsAreExceeded(): void
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
        $monsterFightService->shouldReceive('fightMonster')->times(11)->andReturn([
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 100,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ]);

        $characterCacheData = Mockery::mock(CharacterCacheData::class);
        $characterCacheData->shouldReceive('deleteCharacterSheet')
            ->once()
            ->with(Mockery::type(Character::class));

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

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(CharacterCacheData::class, $characterCacheData);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
        Event::assertDispatched(UpdateCharacterStatus::class);
        Event::assertDispatched(AutomationTimeOut::class);
    }
}
