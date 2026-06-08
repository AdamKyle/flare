<?php

namespace Tests\Unit\Game\Automation\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Jobs\Exploration;
use App\Game\Automation\Services\ExplorationAutomationService;
use App\Game\Automation\Services\ExplorationCreatureCountCalculator;
use App\Game\Automation\Services\ExplorationLogService;
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
use Tests\Traits\CreateExplorationLog;

class ExplorationTest extends TestCase
{
    use CreateExplorationLog;
    use RefreshDatabase;

    private ?Character $character;

    private ?CharacterFactory $characterFactory = null;

    private ?Monster $monster;

    public function setUp(): void
    {
        parent::setUp();

        $factory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->createSessionForCharacter()
            ->equipStrongGear();

        $this->character = $factory->getCharacter();
        $this->characterFactory = $factory;

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
        $this->characterFactory = null;
        $this->monster = null;

        parent::tearDown();
    }

    public function testHandleDoesNotDeleteCurrentAutomationWhenStaleAutomationDoesNotExist(): void
    {
        Event::fake();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        Exploration::dispatchSync($this->character, 999999, AttackTypeValue::ATTACK, 5);

        $automation = $this->character->refresh()->currentAutomations()->first();

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

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        Exploration::dispatchSync($this->character, $automation->id + 1, AttackTypeValue::ATTACK, 5);

        $this->assertNotNull(CharacterAutomation::find($automation->id));

        Event::assertNotDispatched(AutomationTimeOut::class);
        Event::assertNotDispatched(AutomationLogUpdate::class);
    }

    public function testHandleEndsExpiredAutomation(): void
    {
        Event::fake();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

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
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleClearsSurvivalCacheWhenAutomationExpired(): void
    {
        Event::fake();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

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
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->assertFalse(Cache::has('can-character-survive-' . $this->character->id));
    }

    public function testHandleRewardsGoldWhenAutomationEnds(): void
    {
        Event::fake();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $this->character->update([
            'gold' => 10,
        ]);

        $this->character = $this->character->refresh();

        $automation = $this->character->currentAutomations()->first();

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
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->assertEquals(10010, $this->character->refresh()->gold);
    }

    public function testBeginExplorationAcceptsClearedSelectionsAndUsesDefaultValues(): void
    {
        Event::fake();

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

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $this->character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
        ]);

        $this->character = $this->character->refresh();

        $automation = $this->character->currentAutomations()->first();

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
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD, $this->character->gold);
    }

    public function testHandleDispatchesCurrencyUpdateWhenAutomationEnds(): void
    {
        Event::fake();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

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
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        Event::assertDispatched(UpdateCharacterCurrenciesEvent::class);
    }

    public function testHandleDispatchesUpdateCharacterStatusWhenAutomationEnds(): void
    {
        Event::fake();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

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
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        Event::assertDispatched(UpdateCharacterStatus::class);
    }

    public function testHandleDispatchesAutomationTimeoutWhenAutomationEnds(): void
    {
        Event::fake();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

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
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testHandleDispatchesAutomationLogUpdateWhenCharacterIsLoggedIn(): void
    {
        Event::fake();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

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
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function testHandleRecordsDerivedRuntimeAttackDamageFromBuiltMonsterAttackRange(): void
    {
        Event::fake();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();
        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
        ]);

        $setupFightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
                'max_monster_health' => 500,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'attack_range' => '10-10',
                'increases_damage_by' => 0.25,
            ],
        ];

        $fightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
            'attack_damage' => 0,
        ];

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn($setupFightData);
        $monsterFightService->shouldReceive('fightMonster')->andReturn($fightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $explorationCreatureCountCalculator = Mockery::mock(ExplorationCreatureCountCalculator::class);
        $explorationCreatureCountCalculator->shouldReceive('calculate')->once()->andReturn(1);

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(ExplorationCreatureCountCalculator::class, $explorationCreatureCountCalculator);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $log->refresh();

        $this->assertEquals(12, $log->summary['monster']['stats']['attack_damage']);
    }

    public function testHandleRecordsFightAttackDamageWhenFightProvidesPositiveValue(): void
    {
        Event::fake();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();
        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
        ]);

        $setupFightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
                'max_monster_health' => 500,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'attack_range' => '10-10',
                'increases_damage_by' => 0.25,
            ],
        ];

        $fightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
            'attack_damage' => 99,
        ];

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn($setupFightData);
        $monsterFightService->shouldReceive('fightMonster')->andReturn($fightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $explorationCreatureCountCalculator = Mockery::mock(ExplorationCreatureCountCalculator::class);
        $explorationCreatureCountCalculator->shouldReceive('calculate')->once()->andReturn(1);

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(ExplorationCreatureCountCalculator::class, $explorationCreatureCountCalculator);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $log->refresh();

        $this->assertEquals(99, $log->summary['monster']['stats']['attack_damage']);
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
            ->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $this->instance(CharacterStatBuilder::class, $characterStatBuilder);
        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
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
            ->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $this->instance(CharacterStatBuilder::class, $characterStatBuilder);
        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
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
            ->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $this->instance(CharacterStatBuilder::class, $characterStatBuilder);
        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
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
            ->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $this->instance(CharacterStatBuilder::class, $characterStatBuilder);
        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
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
            ->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $this->instance(CharacterStatBuilder::class, $characterStatBuilder);
        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
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
            ->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleRecordsEncounterLogTotalsFromRewardsAndBattleMessages(): void
    {
        Event::fake();

        $this->monster->update([
            'name' => 'Base Monster',
            'str' => 1,
            'dur' => 2,
            'dex' => 3,
            'chr' => 4,
            'int' => 5,
            'agi' => 6,
            'focus' => 7,
            'ac' => 8,
            'health_range' => 9,
            'attack_range' => 10,
            'max_spell_damage' => 11,
            'healing_percentage' => 12,
            'xp' => 13,
            'gold' => 14,
            'max_level' => 15,
        ]);

        $this->monster = $this->monster->refresh();

        $runtimeMonster = new Monster([
            'name' => 'Runtime Snapshot Monster',
            'str' => 11,
            'dur' => 12,
            'dex' => 13,
            'chr' => 14,
            'int' => 15,
            'agi' => 16,
            'focus' => 17,
            'ac' => 18,
            'health_range' => 0,
            'attack_range' => 0,
            'max_spell_damage' => 0,
            'healing_percentage' => 0,
            'xp' => 23,
            'gold' => 24,
            'max_level' => 25,
        ]);

        $runtimeMonster->id = $this->monster->id;
        $runtimeMonster->exists = true;
        $runtimeMonster->setAttribute('health', 1900);
        $runtimeMonster->setAttribute('attack_damage', 200);
        $runtimeMonster->setAttribute('spell_damage', 210);
        $runtimeMonster->setAttribute('healing', 22);

        $fightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
                'max_monster_health' => 1900,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'name' => 'Runtime Snapshot Monster',
                'str' => 11,
                'dur' => 12,
                'dex' => 13,
                'chr' => 14,
                'int' => 15,
                'agi' => 16,
                'focus' => 17,
                'ac' => 18,
                'attack_damage' => 200,
                'spell_damage' => 210,
                'healing' => 22,
                'xp' => 23,
                'gold' => 24,
                'max_level' => 25,
            ],
            'weapon_damage' => 5,
            'spell_damage' => 7,
            'healing_done' => 11,
            'damage_blocked' => 13,
            'messages' => [
                [
                    'message' => 'Your weapon hits '.$runtimeMonster->name.' for: 1,250',
                    'type' => 'player-action',
                ],
                [
                    'message' => 'Your damage spell(s) hits '.$runtimeMonster->name.' for: 650',
                    'type' => 'player-action',
                ],
                [
                    'message' => 'The enemy\'s blood flows through the air and gives you life: 75',
                    'type' => 'player-action',
                ],
                [
                    'message' => 'You reduced the incoming (Physical) damage with your armour by: 40',
                    'type' => 'player-action',
                ],
            ],
        ];

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn($fightData);
        $monsterFightService->shouldReceive('fightMonster')->andReturn($fightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($runtimeMonster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')
            ->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(100);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(15);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(4);

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
        ]);

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $log = $log->refresh();
        $currencies = $log->currencies_gained;

        $this->assertEquals(1, $log->fights);
        $this->assertEquals(6, $log->kills);
        $this->assertEquals(0, $log->xp_gained);
        $this->assertEquals(0, $log->skill_xp_gained);
        $this->assertEquals(0, $log->faction_points_gained);
        $expectedWeaponDamage = (5 + 1250) * 6;

        $this->assertEquals($expectedWeaponDamage, $log->weapon_damage);
        $this->assertNotEquals($expectedWeaponDamage * 2, $log->weapon_damage);
        $this->assertEquals(3942, $log->spell_damage);
        $this->assertEquals(516, $currencies['healing_done']);
        $this->assertEquals(318, $currencies['damage_blocked']);
        $this->assertEquals('Runtime Snapshot Monster', $log->summary['monster']['name']);
        $this->assertNotEquals(Monster::find($this->monster->id)->name, $log->summary['monster']['name']);
        $this->assertEquals('/monsters/'.$this->monster->id, $log->summary['monster']['link']);
        $this->assertEquals(11, $log->summary['monster']['stats']['str']);
        $this->assertEquals(12, $log->summary['monster']['stats']['dur']);
        $this->assertEquals(13, $log->summary['monster']['stats']['dex']);
        $this->assertEquals(14, $log->summary['monster']['stats']['chr']);
        $this->assertEquals(15, $log->summary['monster']['stats']['int']);
        $this->assertEquals(16, $log->summary['monster']['stats']['agi']);
        $this->assertEquals(17, $log->summary['monster']['stats']['focus']);
        $this->assertEquals(18, $log->summary['monster']['stats']['ac']);
        $this->assertEquals(1900, $log->summary['monster']['stats']['health_range']);
        $this->assertEquals(200, $log->summary['monster']['stats']['attack_range']);
        $this->assertEquals(210, $log->summary['monster']['stats']['max_spell_damage']);
        $this->assertEquals(22, $log->summary['monster']['stats']['healing_percentage']);
        $this->assertEquals(23, $log->summary['monster']['stats']['xp']);
        $this->assertEquals(24, $log->summary['monster']['stats']['gold']);
        $this->assertEquals(25, $log->summary['monster']['stats']['max_level']);

        $output = (new ExplorationLogService)->outputForCharacter($this->character);

        $this->assertEquals(7530, $output['output']['damage']['weapon']);
        $this->assertEquals(3942, $output['output']['damage']['spell']);
        $this->assertEquals(516, $output['output']['healing']);
        $this->assertEquals(318, $output['output']['blocked']);
        $this->assertEquals(1900, $output['output']['monster']['stats']['health_range']);
        $this->assertEquals(200, $output['output']['monster']['stats']['attack_range']);
        $this->assertEquals(210, $output['output']['monster']['stats']['max_spell_damage']);
        $this->assertEquals(22, $output['output']['monster']['stats']['healing_percentage']);
        $this->assertEquals(23, $output['output']['monster']['stats']['xp']);
        $this->assertEquals(24, $output['output']['monster']['stats']['gold']);
        $this->assertEquals(25, $output['output']['monster']['stats']['max_level']);
    }

    public function testHandleRecordsBaseSnapshotBeforeFightAndKeepsFirstRuntimeSnapshot(): void
    {
        Event::fake();

        $this->monster->update([
            'name' => 'Base Monster',
            'str' => 1,
            'dur' => 2,
            'dex' => 3,
            'chr' => 4,
            'int' => 5,
            'agi' => 6,
            'focus' => 7,
            'ac' => 8,
            'health_range' => 90,
            'attack_range' => 45,
            'max_spell_damage' => 11,
            'healing_percentage' => 12,
            'xp' => 13,
            'gold' => 14,
            'max_level' => 15,
        ]);

        $this->monster = $this->monster->refresh();

        $firstRuntimeMonster = new Monster([
            'name' => 'First Runtime Monster',
            'str' => 21,
            'dur' => 22,
            'dex' => 23,
            'chr' => 24,
            'int' => 25,
            'agi' => 26,
            'focus' => 27,
            'ac' => 28,
            'health_range' => 0,
            'attack_range' => 0,
            'max_spell_damage' => 0,
            'healing_percentage' => 0,
            'xp' => 29,
            'gold' => 30,
            'max_level' => 31,
        ]);

        $firstRuntimeMonster->id = $this->monster->id;
        $firstRuntimeMonster->exists = true;
        $firstRuntimeMonster->setAttribute('health', 777);
        $firstRuntimeMonster->setAttribute('attack_damage', 88);
        $firstRuntimeMonster->setAttribute('spell_damage', 66);
        $firstRuntimeMonster->setAttribute('healing', 7);

        $laterRuntimeMonster = new Monster([
            'name' => 'Later Runtime Monster',
            'str' => 41,
            'dur' => 42,
            'dex' => 43,
            'chr' => 44,
            'int' => 45,
            'agi' => 46,
            'focus' => 47,
            'ac' => 48,
            'health_range' => 0,
            'attack_range' => 0,
            'max_spell_damage' => 0,
            'healing_percentage' => 0,
            'xp' => 49,
            'gold' => 50,
            'max_level' => 51,
        ]);

        $laterRuntimeMonster->id = $this->monster->id;
        $laterRuntimeMonster->exists = true;
        $laterRuntimeMonster->setAttribute('health', 0);
        $laterRuntimeMonster->setAttribute('attack_damage', 0);

        $setupData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 100,
                'max_monster_health' => 777,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'name' => 'First Runtime Monster',
                'str' => 21,
                'dur' => 22,
                'dex' => 23,
                'chr' => 24,
                'int' => 25,
                'agi' => 26,
                'focus' => 27,
                'ac' => 28,
                'attack_damage' => 88,
                'spell_damage' => 66,
                'healing' => 7,
                'xp' => 29,
                'gold' => 30,
                'max_level' => 31,
            ],
        ];

        $firstFightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
                'max_monster_health' => 777,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'attack_damage' => 88,
                'spell_damage' => 66,
                'healing' => 7,
            ],
        ];

        $laterFightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
                'max_monster_health' => 0,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'attack_damage' => 0,
                'spell_damage' => 0,
                'healing' => 0,
            ],
        ];

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->twice()->andReturn($setupData);
        $monsterFightService->shouldReceive('fightMonster')->twice()->andReturn($firstFightData, $laterFightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($firstRuntimeMonster, $laterRuntimeMonster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(100);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(15);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(4);

        $explorationCreatureCountCalculator = Mockery::mock(ExplorationCreatureCountCalculator::class);
        $explorationCreatureCountCalculator->shouldReceive('calculate')->once()->andReturn(2);

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
        ]);

        $explorationLogService = Mockery::mock(ExplorationLogService::class);
        $explorationLogService->shouldReceive('recordCurrentRoundCreatures')
            ->once()
            ->withArgs(function (ExplorationLog $passedLog, int $currentRoundCreatures) use ($log): bool {
                return $passedLog->id === $log->id
                    && $currentRoundCreatures === 2;
            });
        $explorationLogService->shouldReceive('recordMonsterSnapshot')
            ->once()
            ->withArgs(function (ExplorationLog $passedLog, array $monster) use ($log): bool {
                return $passedLog->id === $log->id
                    && $monster['name'] === 'First Runtime Monster'
                    && (int) $monster['stats']['health_range'] === 777
                    && (int) $monster['stats']['attack_range'] === 88
                    && (int) $monster['stats']['max_spell_damage'] === 66
                    && (int) $monster['stats']['healing_percentage'] === 7
                    && (int) $monster['stats']['ac'] === 28
                    && (int) $monster['stats']['xp'] === 29
                    && (int) $monster['stats']['gold'] === 30
                    && (int) $monster['stats']['max_level'] === 31;
            });
        $explorationLogService->shouldReceive('recordFightTotals')
            ->once()
            ->withArgs(function (ExplorationLog $passedLog, array $totals) use ($log): bool {
                return $passedLog->id === $log->id
                    && ! array_key_exists('monster', $totals);
            });
        $explorationLogService->shouldReceive('finalize')->once();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);
        $this->instance(ExplorationCreatureCountCalculator::class, $explorationCreatureCountCalculator);
        $this->instance(ExplorationLogService::class, $explorationLogService);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
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

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

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

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

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

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

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

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

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

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

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

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(CharacterCacheData::class, $characterCacheData);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
        Event::assertDispatched(UpdateCharacterStatus::class);
        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testHandlePassesBatchedFactionPointsForExplorationKills(): void
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
        $monsterFightService->shouldReceive('setupMonster')->times(3)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('fightMonster')->times(3)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')
            ->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $explorationCreatureCountCalculator = Mockery::mock(ExplorationCreatureCountCalculator::class);
        $explorationCreatureCountCalculator->shouldReceive('calculate')
            ->once()
            ->with(Mockery::type(Character::class))
            ->andReturn(3);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')
            ->once()
            ->with(Mockery::type(Character::class))
            ->andReturn(7);

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(ExplorationCreatureCountCalculator::class, $explorationCreatureCountCalculator);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testEncounterUpdatesExplorationLogFightsKillsAndPassesLogIdToContext(): void
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

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

        $automation = $this->characterFactory->automationManagement()
            ->assignExplorationAutomation([
                'monster_id' => $this->monster->id,
                'move_down_monster_list_every' => null,
                'previous_level' => $this->character->level,
                'current_level' => $this->character->level,
            ])
            ->getCharacterAutomation();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')
            ->once();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $log->refresh();

        $this->assertEquals(1, $log->fights);
        $this->assertGreaterThanOrEqual(6, $log->kills);
    }

    public function testEncounterStoresCurrentRoundCreaturesFromCalculatedEnemiesWithoutChangingCompletedFights(): void
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
        $monsterFightService->shouldReceive('setupMonster')->times(5)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('fightMonster')->times(5)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $explorationCreatureCountCalculator = Mockery::mock(ExplorationCreatureCountCalculator::class);
        $explorationCreatureCountCalculator->shouldReceive('calculate')
            ->once()
            ->with(Mockery::type(Character::class))
            ->andReturn(5);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(ExplorationCreatureCountCalculator::class, $explorationCreatureCountCalculator);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $log->refresh();
        $output = (new ExplorationLogService)->outputForCharacter($this->character);

        $this->assertEquals(5, $log->summary['current_round_creatures']);
        $this->assertEquals(5, $output['output']['current_round_creatures']);
        $this->assertEquals(1, $output['output']['totals']['fights']);
        $this->assertEquals(5, $output['output']['totals']['kills']);
    }

    public function testCharacterDeathFinalizesExplorationLogAndCreatesWarning(): void
    {
        Event::fake();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn([
            'health' => [
                'current_character_health' => 0,
                'current_monster_health' => 100,
            ],
            'monster' => ['id' => $this->monster->id],
        ]);

        $characterCacheData = Mockery::mock(CharacterCacheData::class);
        $characterCacheData->shouldReceive('deleteCharacterSheet')->with(Mockery::type(Character::class));

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(CharacterCacheData::class, $characterCacheData);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $log->refresh();

        $this->assertNotNull($log->ended_at);
        $this->assertEquals('character_died', $log->stopped_reason);
        $this->assertEquals(1, ExplorationWarning::where('character_id', $this->character->id)->count());
    }

    public function testTimeoutFinalizesExplorationLogAndCreatesWarning(): void
    {
        Event::fake();

        $automation = $this->characterFactory->automationManagement()
            ->assignExplorationAutomation([
                'monster_id' => $this->monster->id,
                'move_down_monster_list_every' => null,
                'previous_level' => $this->character->level,
                'current_level' => $this->character->level,
            ])
            ->getCharacterAutomation();

        Carbon::setTestNow(now()->addSeconds(3));

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 5);

        $log->refresh();

        $this->assertNotNull($log->ended_at);
        $this->assertEquals('natural_end', $log->stopped_reason);
        $this->assertEquals(1, ExplorationWarning::where('character_id', $this->character->id)->count());
    }

    public function testErrorFinalizesExplorationLogAndCreatesWarning(): void
    {
        Event::fake();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn([
            'monster' => ['id' => $this->monster->id],
        ]);

        $characterCacheData = Mockery::mock(CharacterCacheData::class);
        $characterCacheData->shouldReceive('deleteCharacterSheet')->with(Mockery::type(Character::class));

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(CharacterCacheData::class, $characterCacheData);

        Log::shouldReceive('error')->once();

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $log->refresh();

        $this->assertNotNull($log->ended_at);
        $this->assertEquals(1, ExplorationWarning::where('character_id', $this->character->id)->count());
    }

    public function testNaturalEndFinalizesExplorationLogAndCreatesWarning(): void
    {
        Event::fake();

        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $successfulFightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
            ],
            'monster' => ['id' => $this->monster->id],
        ];

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('fightMonster')->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(1);

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $log->refresh();

        $this->assertNotNull($log->ended_at);
        $this->assertEquals('natural_end', $log->stopped_reason);
        $this->assertEquals(1, ExplorationWarning::where('character_id', $this->character->id)->count());
    }

    public function testHandleCallsProcessMonsterDeathWithRewardContextWhenAutomationEndsNaturally(): void
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

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(50);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(10);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(3);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')
            ->once()
            ->withArgs(function (int $characterId, int $monsterId, array $context): bool {
                return $characterId === $this->character->id
                    && $monsterId === $this->monster->id
                    && isset($context['total_xp'])
                    && $context['total_xp'] > 0;
            });

        $automation = $this->characterFactory->automationManagement()
            ->assignExplorationAutomation([
                'monster_id' => $this->monster->id,
            ])
            ->getCharacterAutomation();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandlePassesZeroBatchedFactionPointsWhenNoFactionPointsAreAvailable(): void
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
        $monsterFightService->shouldReceive('setupMonster')->times(4)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('fightMonster')->times(4)->andReturn($successfulFightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')
            ->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $explorationCreatureCountCalculator = Mockery::mock(ExplorationCreatureCountCalculator::class);
        $explorationCreatureCountCalculator->shouldReceive('calculate')
            ->once()
            ->with(Mockery::type(Character::class))
            ->andReturn(4);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')
            ->once()
            ->with(Mockery::type(Character::class))
            ->andReturn(0);

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(ExplorationCreatureCountCalculator::class, $explorationCreatureCountCalculator);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertNull(CharacterAutomation::find($automation->id));
    }

    public function testHandleRecordsBuiltMonsterSnapshotFromSetupData(): void
    {
        Event::fake();

        $this->monster->update([
            'health_range' => 0,
            'attack_range' => 0,
        ]);

        $this->monster = $this->monster->refresh();

        $automation = $this->characterFactory->automationManagement()
            ->assignExplorationAutomation([
                'monster_id' => $this->monster->id,
                'move_down_monster_list_every' => null,
                'previous_level' => $this->character->level,
                'current_level' => $this->character->level,
            ])
            ->getCharacterAutomation();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
        ]);

        $setupFightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
                'max_monster_health' => 9876,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'name' => 'Built '.$this->monster->name,
                'str' => 11,
                'dur' => 12,
                'dex' => 13,
                'chr' => 14,
                'int' => 15,
                'agi' => 16,
                'focus' => 17,
                'ac' => 18,
                'attack_damage' => 4321,
                'spell_damage' => 21,
                'healing' => 22,
                'xp' => 23,
                'gold' => 24,
                'max_level' => 25,
            ],
        ];

        $finishedFightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ];

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->once()->andReturn($setupFightData);
        $monsterFightService->shouldReceive('fightMonster')->once()->andReturn($finishedFightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $explorationCreatureCountCalculator = Mockery::mock(ExplorationCreatureCountCalculator::class);
        $explorationCreatureCountCalculator->shouldReceive('calculate')->once()->andReturn(1);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->once()->andReturn(1);

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(ExplorationCreatureCountCalculator::class, $explorationCreatureCountCalculator);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $log->refresh();

        $this->assertEquals('Built '.$this->monster->name, $log->summary['monster']['name']);
        $this->assertEquals(9876, $log->summary['monster']['stats']['health_range']);
        $this->assertEquals(4321, $log->summary['monster']['stats']['attack_range']);
    }

    public function testHandleRecordsBuiltMonsterSnapshotOnlyOncePerRound(): void
    {
        Event::fake();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
        ]);

        $firstSetupFightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
                'max_monster_health' => 1111,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'name' => 'First Built '.$this->monster->name,
                'attack_damage' => 2222,
            ],
        ];

        $secondSetupFightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
                'max_monster_health' => 3333,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'name' => 'Second Built '.$this->monster->name,
                'attack_damage' => 4444,
            ],
        ];

        $finishedFightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ];

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->twice()->andReturn($firstSetupFightData, $secondSetupFightData);
        $monsterFightService->shouldReceive('fightMonster')->twice()->andReturn($finishedFightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(1);

        $explorationCreatureCountCalculator = Mockery::mock(ExplorationCreatureCountCalculator::class);
        $explorationCreatureCountCalculator->shouldReceive('calculate')->once()->andReturn(2);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->once()->andReturn(1);

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(ExplorationCreatureCountCalculator::class, $explorationCreatureCountCalculator);
        $this->instance(FactionHandler::class, $factionHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $log->refresh();

        $this->assertEquals('First Built '.$this->monster->name, $log->summary['monster']['name']);
        $this->assertEquals(1111, $log->summary['monster']['stats']['health_range']);
        $this->assertEquals(2222, $log->summary['monster']['stats']['attack_range']);
    }

    public function testSnapshotDerivesAttackDamageFromAttackRangeWhenNoRolledAttack(): void
    {
        Event::fake();

        $this->monster->update([
            'name' => 'Range Monster',
            'health_range' => '100-500',
            'attack_range' => '10-40',
        ]);
        $this->monster = $this->monster->refresh();

        $setupData = [
            'health' => [
                'current_character_health' => 200,
                'current_monster_health' => 0,
                'max_monster_health' => 300,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'name' => 'Range Monster',
                'str' => 5,
                'dur' => 5,
                'dex' => 5,
                'chr' => 5,
                'int' => 5,
                'agi' => 5,
                'focus' => 5,
                'ac' => 5,
                'attack_range' => '10-40',
                'spell_damage' => 10,
                'healing' => 2,
                'xp' => 10,
                'gold' => 10,
                'max_level' => 5,
            ],
        ];

        $fightData = [
            'health' => [
                'current_character_health' => 200,
                'current_monster_health' => 0,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ];

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn($setupData);
        $monsterFightService->shouldReceive('fightMonster')->andReturn($fightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
        ]);

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $log = $log->refresh();

        $this->assertArrayHasKey('attack_damage', $log->summary['monster']['stats']);
        $this->assertGreaterThanOrEqual(10, $log->summary['monster']['stats']['attack_damage']);
        $this->assertLessThanOrEqual(40, $log->summary['monster']['stats']['attack_damage']);
    }

    public function testSnapshotDerivesAttackDamageWhenAttackRangeMinEqualsMax(): void
    {
        Event::fake();

        $this->monster->update([
            'name' => 'Fixed Range Monster',
            'health_range' => '100-500',
            'attack_range' => '25-25',
        ]);
        $this->monster = $this->monster->refresh();

        $setupData = [
            'health' => [
                'current_character_health' => 200,
                'current_monster_health' => 0,
                'max_monster_health' => 300,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'name' => 'Fixed Range Monster',
                'str' => 5,
                'dur' => 5,
                'dex' => 5,
                'chr' => 5,
                'int' => 5,
                'agi' => 5,
                'focus' => 5,
                'ac' => 5,
                'attack_range' => '25-25',
                'spell_damage' => 10,
                'healing' => 2,
                'xp' => 10,
                'gold' => 10,
                'max_level' => 5,
            ],
        ];

        $fightData = [
            'health' => [
                'current_character_health' => 200,
                'current_monster_health' => 0,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ];

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn($setupData);
        $monsterFightService->shouldReceive('fightMonster')->andReturn($fightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
        ]);

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $log = $log->refresh();

        $this->assertArrayHasKey('attack_damage', $log->summary['monster']['stats']);
        $this->assertEquals(25, $log->summary['monster']['stats']['attack_damage']);
    }

    public function testSnapshotAppliesIncreasesDamageByToAttackRangeDerivedDamage(): void
    {
        Event::fake();

        $this->monster->update([
            'name' => 'Boosted Monster',
            'health_range' => '100-500',
            'attack_range' => '100-100',
        ]);
        $this->monster = $this->monster->refresh();

        $setupData = [
            'health' => [
                'current_character_health' => 200,
                'current_monster_health' => 0,
                'max_monster_health' => 300,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'name' => 'Boosted Monster',
                'str' => 5,
                'dur' => 5,
                'dex' => 5,
                'chr' => 5,
                'int' => 5,
                'agi' => 5,
                'focus' => 5,
                'ac' => 5,
                'attack_range' => '100-100',
                'increases_damage_by' => 0.5,
                'spell_damage' => 10,
                'healing' => 2,
                'xp' => 10,
                'gold' => 10,
                'max_level' => 5,
            ],
        ];

        $fightData = [
            'health' => [
                'current_character_health' => 200,
                'current_monster_health' => 0,
            ],
            'monster' => [
                'id' => $this->monster->id,
            ],
        ];

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn($setupData);
        $monsterFightService->shouldReceive('fightMonster')->andReturn($fightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
        ]);

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $log = $log->refresh();

        $this->assertArrayHasKey('attack_damage', $log->summary['monster']['stats']);
        $this->assertEquals(150, $log->summary['monster']['stats']['attack_damage']);
    }

    public function testBuiltMonsterSnapshotIncludesAttackDamageAndHealthKeys(): void
    {
        Event::fake();

        $this->monster->update([
            'name' => 'Snapshot Monster',
            'health_range' => '100-500',
            'attack_range' => '20-80',
        ]);
        $this->monster = $this->monster->refresh();

        $fightData = [
            'health' => [
                'current_character_health' => 200,
                'current_monster_health' => 0,
                'max_monster_health' => 350,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'name' => 'Snapshot Monster',
                'str' => 10,
                'dur' => 10,
                'dex' => 10,
                'chr' => 10,
                'int' => 10,
                'agi' => 10,
                'focus' => 10,
                'ac' => 10,
                'attack_damage' => 55,
                'spell_damage' => 20,
                'healing' => 5,
                'xp' => 15,
                'gold' => 25,
                'max_level' => 10,
            ],
        ];

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn($fightData);
        $monsterFightService->shouldReceive('fightMonster')->andReturn($fightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->refresh()->currentAutomations()->first();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'started_at' => now(),
        ]);

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $log = $log->refresh();

        $this->assertArrayHasKey('attack_damage', $log->summary['monster']['stats']);
        $this->assertEquals(55, $log->summary['monster']['stats']['attack_damage']);
        $this->assertArrayHasKey('health', $log->summary['monster']['stats']);
        $this->assertEquals(350, $log->summary['monster']['stats']['health']);
    }

    public function testHandleRepeatDispatchUsesExplorationQueueWithLongRunningConnection(): void
    {
        Queue::fake();
        Event::fake();

        $setupData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
                'max_monster_health' => 200,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'name' => $this->monster->name,
                'str' => 1, 'dur' => 1, 'dex' => 1, 'chr' => 1,
                'int' => 1, 'agi' => 1, 'focus' => 1, 'ac' => 1,
                'attack_damage' => 10, 'spell_damage' => 0, 'healing' => 0,
                'xp' => 10, 'gold' => 10, 'max_level' => 5,
            ],
        ];

        $fightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
            ],
            'monster' => ['id' => $this->monster->id],
        ];

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')->andReturn($setupData);
        $monsterFightService->shouldReceive('fightMonster')->andReturn($fightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(0);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(0);

        $explorationCreatureCountCalculator = Mockery::mock(ExplorationCreatureCountCalculator::class);
        $explorationCreatureCountCalculator->shouldReceive('calculate')->andReturn(1);

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->currentAutomations()->first();
        $automation->update(['completed_at' => now()->addHours(8)]);

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);
        $this->instance(ExplorationCreatureCountCalculator::class, $explorationCreatureCountCalculator);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        Queue::assertPushed(Exploration::class, function (Exploration $job): bool {
            return $job->queue === 'exploration' && $job->connection === 'long_running';
        });
    }

    public function testHandleRepeatDispatchDelayIsBasedOnRoundStartNotJobFinish(): void
    {
        Queue::fake();
        Event::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');
        Carbon::setTestNow($now);

        $setupData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
                'max_monster_health' => 200,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'name' => $this->monster->name,
                'str' => 1, 'dur' => 1, 'dex' => 1, 'chr' => 1,
                'int' => 1, 'agi' => 1, 'focus' => 1, 'ac' => 1,
                'attack_damage' => 10, 'spell_damage' => 0, 'healing' => 0,
                'xp' => 10, 'gold' => 10, 'max_level' => 5,
            ],
        ];

        $fightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
            ],
            'monster' => ['id' => $this->monster->id],
        ];

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')
            ->andReturnUsing(function () use ($setupData, $now): array {
                Carbon::setTestNow($now->copy()->addSeconds(30));

                return $setupData;
            });
        $monsterFightService->shouldReceive('fightMonster')->andReturn($fightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(0);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(0);

        $explorationCreatureCountCalculator = Mockery::mock(ExplorationCreatureCountCalculator::class);
        $explorationCreatureCountCalculator->shouldReceive('calculate')->andReturn(1);

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->currentAutomations()->first();
        $automation->update(['completed_at' => now()->addHours(8)]);

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);
        $this->instance(ExplorationCreatureCountCalculator::class, $explorationCreatureCountCalculator);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        Queue::assertPushed(Exploration::class, function (Exploration $job) use ($now): bool {
            return $job->delay->toDateTimeString() === $now->copy()->addSeconds(60)->toDateTimeString();
        });
    }

    public function testHandleSetUpFightPreservesCharacterSheetCache(): void
    {
        Event::fake();

        $setupData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
                'max_monster_health' => 200,
            ],
            'monster' => [
                'id' => $this->monster->id,
                'name' => $this->monster->name,
                'str' => 1, 'dur' => 1, 'dex' => 1, 'chr' => 1,
                'int' => 1, 'agi' => 1, 'focus' => 1, 'ac' => 1,
                'attack_damage' => 10, 'spell_damage' => 0, 'healing' => 0,
                'xp' => 10, 'gold' => 10, 'max_level' => 5,
            ],
        ];

        $fightData = [
            'health' => [
                'current_character_health' => 100,
                'current_monster_health' => 0,
            ],
            'monster' => ['id' => $this->monster->id],
        ];

        $preserveCharacterSheetCachePassed = null;

        $monsterFightService = Mockery::mock(MonsterFightService::class);
        $monsterFightService->shouldReceive('setupMonster')
            ->andReturnUsing(function ($char, $params, $arg3, $arg4, $preserveFlag) use (&$preserveCharacterSheetCachePassed, $setupData): array {
                $preserveCharacterSheetCachePassed = $preserveFlag;

                return $setupData;
            });
        $monsterFightService->shouldReceive('fightMonster')->andReturn($fightData);
        $monsterFightService->shouldReceive('getMonster')->andReturn($this->monster);

        $battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $battleEventHandler->shouldReceive('processMonsterDeath')->once();

        $characterRewardService = Mockery::mock(CharacterRewardService::class);
        $characterRewardService->shouldReceive('setCharacter')->andReturnSelf();
        $characterRewardService->shouldReceive('fetchXpForMonster')->andReturn(10);

        $skillService = Mockery::mock(SkillService::class);
        $skillService->shouldReceive('setSkillInTraining')->andReturnSelf();
        $skillService->shouldReceive('getXpForSkillIntraining')->andReturn(0);

        $factionHandler = Mockery::mock(FactionHandler::class);
        $factionHandler->shouldReceive('getFactionPointsPerKill')->andReturn(0);

        $explorationCreatureCountCalculator = Mockery::mock(ExplorationCreatureCountCalculator::class);
        $explorationCreatureCountCalculator->shouldReceive('calculate')->andReturn(1);

        $this->character = $this->characterFactory->automationManagement()->assignExplorationAutomation([
            'monster_id' => $this->monster->id,
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
        ])->getCharacter();

        $automation = $this->character->currentAutomations()->first();

        $this->instance(MonsterFightService::class, $monsterFightService);
        $this->instance(BattleEventHandler::class, $battleEventHandler);
        $this->instance(CharacterRewardService::class, $characterRewardService);
        $this->instance(SkillService::class, $skillService);
        $this->instance(FactionHandler::class, $factionHandler);
        $this->instance(ExplorationCreatureCountCalculator::class, $explorationCreatureCountCalculator);

        Exploration::dispatchSync($this->character, $automation->id, AttackTypeValue::ATTACK, 1);

        $this->assertTrue($preserveCharacterSheetCachePassed);
    }
}
