<?php

namespace Tests\Unit\Game\Automation\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Values\AttackTypeValue;
use App\Game\Automation\Enums\AutomatedFightResultType;
use App\Game\Automation\Handlers\AutomatedBountyFightHandler;
use App\Game\Automation\Loggers\FactionLoyaltyAutomationFightLogger;
use App\Game\Automation\Values\AutomatedFightResult;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\SkillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;

class AutomatedBountyFightHandlerTest extends TestCase
{
    use RefreshDatabase;

    private ?AutomatedBountyFightHandler $handler = null;

    private ?FactionLoyaltyFactory $factionLoyaltyFactory = null;

    private ?Character $character = null;

    private ?FactionLoyaltyAutomation $factionLoyaltyAutomation = null;

    private ?FactionLoyaltyNpc $factionLoyaltyNpc = null;

    private ?FactionLoyaltyAutomationFightLogger $fightLogger = null;

    private ?MonsterFightService $monsterFightService = null;

    private ?BattleEventHandler $battleEventHandler = null;

    private ?CharacterRewardService $characterRewardService = null;

    private ?SkillService $skillService = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->factionLoyaltyFactory = (new FactionLoyaltyFactory)
            ->setUp($this->character)
            ->createAutomation();

        $this->character = $this->factionLoyaltyFactory->getCharacter();
        $this->factionLoyaltyAutomation = $this->factionLoyaltyFactory->getFactionLoyaltyAutomation();
        $this->factionLoyaltyNpc = $this->factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $this->fightLogger = resolve(FactionLoyaltyAutomationFightLogger::class)->setUp($this->factionLoyaltyAutomation);

        $this->monsterFightService = Mockery::mock(MonsterFightService::class);
        $this->battleEventHandler = Mockery::mock(BattleEventHandler::class);
        $this->characterRewardService = Mockery::mock(CharacterRewardService::class);
        $this->skillService = Mockery::mock(SkillService::class);
        $this->handler = new AutomatedBountyFightHandler(
            $this->monsterFightService,
            $this->battleEventHandler,
            $this->characterRewardService,
            $this->skillService,
            new AutomatedFightResult,
        );
    }

    public function tearDown(): void
    {
        $this->handler = null;
        $this->factionLoyaltyFactory = null;
        $this->character = null;
        $this->factionLoyaltyAutomation = null;
        $this->factionLoyaltyNpc = null;
        $this->fightLogger = null;
        $this->monsterFightService = null;
        $this->battleEventHandler = null;
        $this->characterRewardService = null;
        $this->skillService = null;

        parent::tearDown();
    }

    public function testAutomatedBountyFightHandlerResolvesFromContainer(): void
    {
        $this->assertInstanceOf(
            AutomatedBountyFightHandler::class,
            resolve(AutomatedBountyFightHandler::class),
        );
    }

    public function testHandleReturnsInvalidTaskWhenTaskIsMissingRequiredFields(): void
    {
        Event::fake();

        $result = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation,
                $this->factionLoyaltyNpc,
                ['monster_id' => 1],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertEquals(AutomatedFightResultType::INVALID_TASK, $result->getResultType());
        $this->assertTrue($result->hasEndedAutomation());
    }

    public function testHandleReturnsMonsterNotFoundWhenBountyMonsterDoesNotExist(): void
    {
        Event::fake();

        $result = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation,
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => 999999,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertEquals(AutomatedFightResultType::MONSTER_NOT_FOUND, $result->getResultType());
        $this->assertTrue($result->hasEndedAutomation());
    }

    public function testHandleReturnsBountyCompletedWhenTaskAlreadyHasEnoughKills(): void
    {
        Event::fake();

        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];

        $result = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation,
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 1,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertEquals(AutomatedFightResultType::BOUNTY_COMPLETED, $result->getResultType());
        $this->assertEquals($bountyMonster->id, $result->getMonsterId());
        $this->assertTrue($result->isBountyTarget());
    }

    public function testHandleCompletesBountyWhenBountyMonsterDies(): void
    {
        Event::fake();

        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];

        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->once()
            ->with(Mockery::type(Character::class), [
                'selected_monster_id' => $bountyMonster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ], true)
            ->andReturn([
                'health' => [
                    'current_character_health' => 10,
                    'current_monster_health' => 0,
                ],
            ]);

        $this->characterRewardService
            ->shouldReceive('setCharacter')
            ->once()
            ->with(Mockery::type(Character::class))
            ->andReturnSelf();
        $this->characterRewardService
            ->shouldReceive('fetchXpForMonster')
            ->once()
            ->with(Mockery::on(fn (Monster $monster): bool => $monster->id === $bountyMonster->id))
            ->andReturn(10);

        $this->skillService
            ->shouldReceive('setSkillInTraining')
            ->once()
            ->with(Mockery::type(Character::class))
            ->andReturnSelf();
        $this->skillService
            ->shouldReceive('getXpForSkillIntraining')
            ->once()
            ->with(Mockery::type(Character::class), $bountyMonster->xp)
            ->andReturn(5);

        $this->battleEventHandler
            ->shouldReceive('processMonsterDeath')
            ->once()
            ->with($this->character->id, $bountyMonster->id, [
                'total_creatures' => 1,
                'total_xp' => 10,
                'total_faction_points' => 0,
                'total_skill_xp' => 5,
                'skip_faction_loyalty_update_event' => true,
            ]);

        $result = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation,
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertEquals(AutomatedFightResultType::BOUNTY_COMPLETED, $result->getResultType());
        $this->assertEquals(1, $result->getBountyKills());
        $this->assertEquals(10, $result->getTotalXp());
        $this->assertEquals(5, $result->getTotalSkillXp());
        $this->assertEquals(0, $result->getTotalFactionPoints());
    }

    public function testHandleReturnsInvalidStateWhenFightSetupReturnsEmptyData(): void
    {
        Event::fake();

        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];

        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->once()
            ->andReturn([]);

        $result = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation,
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertEquals(AutomatedFightResultType::NOT_ENOUGH_HEALTH_OR_INVALID_STATE, $result->getResultType());
        $this->assertTrue($result->hasEndedAutomation());
    }

    public function testMaximumBountyCompletesAcrossTwoRunsWithoutDuplicateRewards(): void
    {
        Event::fake();
        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];
        $fightData = [
            'health' => [
                'current_character_health' => 10,
                'current_monster_health' => 0,
            ],
        ];
        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->twice()
            ->with(Mockery::type(Character::class), [
                'selected_monster_id' => $bountyMonster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ], true)
            ->andReturn($fightData);
        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->times(48)
            ->with(Mockery::type(Character::class), [
                'selected_monster_id' => $bountyMonster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ], true, false, true)
            ->andReturn($fightData);
        $this->characterRewardService
            ->shouldReceive('setCharacter')
            ->times(50)
            ->andReturnSelf();
        $this->characterRewardService
            ->shouldReceive('fetchXpForMonster')
            ->times(50)
            ->with(Mockery::on(fn (Monster $monster): bool => $monster->id === $bountyMonster->id))
            ->andReturn(10);
        $this->skillService
            ->shouldReceive('setSkillInTraining')
            ->times(50)
            ->andReturnSelf();
        $this->skillService
            ->shouldReceive('getXpForSkillIntraining')
            ->times(50)
            ->with(Mockery::type(Character::class), $bountyMonster->xp)
            ->andReturn(5);
        $this->battleEventHandler
            ->shouldReceive('processMonsterDeath')
            ->twice()
            ->with($this->character->id, $bountyMonster->id, [
                'total_creatures' => 25,
                'total_xp' => 250,
                'total_faction_points' => 0,
                'total_skill_xp' => 125,
                'skip_faction_loyalty_update_event' => true,
            ]);

        $first = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation,
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 50,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();
        $second = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation->refresh(),
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 50,
                    'current_amount' => 25,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertSame(AutomatedFightResultType::BOUNTY_BATCH_YIELDED, $first->getResultType());
        $this->assertSame(25, $first->getBountyKills());
        $this->assertSame(AutomatedFightResultType::BOUNTY_COMPLETED, $second->getResultType());
        $this->assertSame(25, $second->getBountyKills());
    }

    public function testHandleReturnsBountyStalledRetryWhenAttackLimitIsReached(): void
    {
        Event::fake();

        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];
        $fightData = [
            'health' => [
                'current_character_health' => 10,
                'current_monster_health' => 5,
            ],
        ];

        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->once()
            ->andReturn($fightData);
        $this->monsterFightService
            ->shouldReceive('fightMonster')
            ->times(100)
            ->with(Mockery::type(Character::class), AttackTypeValue::ATTACK, false, true)
            ->andReturn($fightData);

        $result = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation,
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertEquals(AutomatedFightResultType::BOUNTY_STALLED_RETRY, $result->getResultType());
        $this->assertEquals(1, $result->getStalledAttempt());
        $this->assertFalse($result->hasEndedAutomation());
    }

    public function testTimeBudgetReachedInsideAttackLoopYieldsWithoutKillOrReward(): void
    {
        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];
        $fightData = [
            'health' => [
                'current_character_health' => 10,
                'current_monster_health' => 5,
            ],
        ];
        $clockCalls = 0;
        $handler = new AutomatedBountyFightHandler(
            $this->monsterFightService,
            $this->battleEventHandler,
            $this->characterRewardService,
            $this->skillService,
            new AutomatedFightResult,
            function () use (&$clockCalls): float {
                $clockCalls++;

                return $clockCalls <= 3 ? 0.0 : 91.0;
            },
        );
        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->once()
            ->andReturn([
                'health' => [
                    'current_character_health' => 10,
                    'current_monster_health' => 10,
                ],
            ]);
        $this->monsterFightService
            ->shouldReceive('fightMonster')
            ->once()
            ->with(Mockery::type(Character::class), AttackTypeValue::ATTACK, false, true)
            ->andReturn($fightData);
        $this->battleEventHandler
            ->shouldNotReceive('processMonsterDeath');
        $this->characterRewardService
            ->shouldNotReceive('setCharacter');
        $this->skillService
            ->shouldNotReceive('setSkillInTraining');

        $result = $handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation,
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertSame(AutomatedFightResultType::BOUNTY_FIGHT_YIELDED, $result->getResultType());
        $this->assertSame(0, $result->getBountyKills());
        $this->assertFalse($result->hasEndedAutomation());
    }

    public function testYieldedBountyFightResumesDamagedMonsterAndRewardsOneDefeat(): void
    {
        Event::fake();

        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];
        $this->factionLoyaltyAutomation->update([
            'last_fight_outcome' => AutomatedFightResultType::BOUNTY_FIGHT_YIELDED->value,
            'last_fight_monster_id' => $bountyMonster->id,
            'last_fight_was_bounty_target' => true,
            'last_fight_was_training' => false,
            'last_fight_stalled_attempt' => 0,
        ]);
        $this->monsterFightService
            ->shouldNotReceive('setupMonster');
        $this->monsterFightService
            ->shouldReceive('fightMonster')
            ->once()
            ->with(Mockery::type(Character::class), AttackTypeValue::ATTACK, false, true)
            ->andReturn([
                'health' => [
                    'current_character_health' => 10,
                    'current_monster_health' => 0,
                ],
            ]);
        $this->characterRewardService
            ->shouldReceive('setCharacter')
            ->once()
            ->with(Mockery::type(Character::class))
            ->andReturnSelf();
        $this->characterRewardService
            ->shouldReceive('fetchXpForMonster')
            ->once()
            ->with(Mockery::on(fn (Monster $monster): bool => $monster->id === $bountyMonster->id))
            ->andReturn(10);
        $this->skillService
            ->shouldReceive('setSkillInTraining')
            ->once()
            ->with(Mockery::type(Character::class))
            ->andReturnSelf();
        $this->skillService
            ->shouldReceive('getXpForSkillIntraining')
            ->once()
            ->with(Mockery::type(Character::class), $bountyMonster->xp)
            ->andReturn(5);
        $this->battleEventHandler
            ->shouldReceive('processMonsterDeath')
            ->once()
            ->with($this->character->id, $bountyMonster->id, [
                'total_creatures' => 1,
                'total_xp' => 10,
                'total_faction_points' => 0,
                'total_skill_xp' => 5,
                'skip_faction_loyalty_update_event' => true,
            ]);

        $result = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation->refresh(),
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertSame(AutomatedFightResultType::BOUNTY_COMPLETED, $result->getResultType());
        $this->assertSame(1, $result->getBountyKills());
    }

    public function testHandleRetriesCachedBountyFightWithoutSettingUpMonsterWhenBountyStalled(): void
    {
        Event::fake();

        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];
        $fightData = [
            'health' => [
                'current_character_health' => 10,
                'current_monster_health' => 5,
            ],
        ];

        $this->factionLoyaltyAutomation->update([
            'last_fight_outcome' => AutomatedFightResultType::BOUNTY_STALLED_RETRY->value,
            'last_fight_monster_id' => $bountyMonster->id,
            'last_fight_was_bounty_target' => true,
            'last_fight_was_training' => false,
            'last_fight_stalled_attempt' => 1,
        ]);

        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->never();
        $this->monsterFightService
            ->shouldReceive('fightMonster')
            ->times(100)
            ->with(Mockery::type(Character::class), AttackTypeValue::ATTACK, false, true)
            ->andReturn($fightData);

        $result = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation->refresh(),
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertEquals(AutomatedFightResultType::BOUNTY_STALLED_RETRY, $result->getResultType());
        $this->assertEquals(2, $result->getStalledAttempt());
    }

    public function testHandleEndsAutomationAndDispatchesWarningWhenBountyStalledMaxAttemptsIsReached(): void
    {
        Event::fake();

        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];
        $fightData = [
            'health' => [
                'current_character_health' => 10,
                'current_monster_health' => 5,
            ],
        ];
        $this->factionLoyaltyAutomation->update([
            'last_fight_outcome' => AutomatedFightResultType::BOUNTY_STALLED_RETRY->value,
            'last_fight_monster_id' => $bountyMonster->id,
            'last_fight_was_bounty_target' => true,
            'last_fight_was_training' => false,
            'last_fight_stalled_attempt' => 9,
        ]);

        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->never();
        $this->monsterFightService
            ->shouldReceive('fightMonster')
            ->times(100)
            ->with(Mockery::type(Character::class), AttackTypeValue::ATTACK, false, true)
            ->andReturn($fightData);

        $result = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation->refresh(),
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $message = 'You tried to kill ' . $bountyMonster->name . ' 10 times and failed to do so. The NPC: ' . $this->factionLoyaltyNpc->npc->real_name . ', is now infuriated. Check your gear child. Go to Faction Loyalty.';

        $this->assertEquals(AutomatedFightResultType::BOUNTY_STALLED_MAX_ATTEMPTS_REACHED, $result->getResultType());
        $this->assertEquals(10, $result->getStalledAttempt());
        $this->assertTrue($result->hasEndedAutomation());
        $this->assertEquals([
            'message' => $message,
            'read' => false,
        ], $result->getWarningNotice());
        Event::assertDispatched(ServerMessageEvent::class, fn (ServerMessageEvent $event): bool => $event->message === $message);
    }

    public function testHandleReturnsNoTrainingMonsterFoundWhenBountyKillsCharacterAndNoTrainingMonsterExists(): void
    {
        Event::fake();

        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];

        Monster::query()
            ->where('game_map_id', $bountyMonster->game_map_id)
            ->where('id', '!=', $bountyMonster->id)
            ->delete();

        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->once()
            ->andReturn([
                'health' => [
                    'current_character_health' => 0,
                    'current_monster_health' => 5,
                ],
            ]);

        $this->battleEventHandler
            ->shouldReceive('processRevive')
            ->once()
            ->with(Mockery::type(Character::class))
            ->andReturn($this->character);

        $result = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation,
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertEquals(AutomatedFightResultType::NO_TRAINING_MONSTER_FOUND, $result->getResultType());
        $this->assertEquals($bountyMonster->id, $this->factionLoyaltyAutomation->refresh()->failed_bounty_monster_id);
        $this->assertTrue($result->hasEndedAutomation());
    }

    public function testHandleReturnsDiedDuringTrainingWhenTrainingMonsterKillsCharacter(): void
    {
        Event::fake();

        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];
        $trainingMonster = $this->factionLoyaltyFactory->getTrainingMonstersForMap($bountyMonster->gameMap)[0];

        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->once()
            ->with(Mockery::type(Character::class), [
                'selected_monster_id' => $bountyMonster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ], true)
            ->andReturn([
                'health' => [
                    'current_character_health' => 0,
                    'current_monster_health' => 5,
                ],
            ]);
        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->once()
            ->with(Mockery::type(Character::class), [
                'selected_monster_id' => $trainingMonster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ], true)
            ->andReturn([
                'health' => [
                    'current_character_health' => 0,
                    'current_monster_health' => 5,
                ],
            ]);

        $this->battleEventHandler
            ->shouldReceive('processRevive')
            ->once()
            ->andReturn($this->character);

        $result = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation,
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertEquals(AutomatedFightResultType::DIED_DURING_TRAINING, $result->getResultType());
        $this->assertTrue($result->isTraining());
        $this->assertTrue($result->hasCharacterDied());
    }

    public function testHandleReturnsInvalidStateWhenTrainingFightDoesNotResolve(): void
    {
        Event::fake();

        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];
        $trainingMonster = $this->factionLoyaltyFactory->getTrainingMonstersForMap($bountyMonster->gameMap)[0];

        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->once()
            ->with(Mockery::type(Character::class), [
                'selected_monster_id' => $bountyMonster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ], true)
            ->andReturn([
                'health' => [
                    'current_character_health' => 0,
                    'current_monster_health' => 5,
                ],
            ]);
        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->once()
            ->with(Mockery::type(Character::class), [
                'selected_monster_id' => $trainingMonster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ], true)
            ->andReturn([
                'health' => [
                    'current_character_health' => 10,
                ],
            ]);

        $this->battleEventHandler
            ->shouldReceive('processRevive')
            ->once()
            ->andReturn($this->character);

        $result = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation,
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertEquals(AutomatedFightResultType::NOT_ENOUGH_HEALTH_OR_INVALID_STATE, $result->getResultType());
        $this->assertTrue($result->isTraining());
    }

    public function testHandleReturnsTrainingStalledRetryWhenTrainingAttackLimitIsReached(): void
    {
        Event::fake();

        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];
        $trainingMonster = $this->factionLoyaltyFactory->getTrainingMonstersForMap($bountyMonster->gameMap)[0];
        $fightData = [
            'health' => [
                'current_character_health' => 10,
                'current_monster_health' => 5,
            ],
        ];

        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->once()
            ->with(Mockery::type(Character::class), [
                'selected_monster_id' => $bountyMonster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ], true)
            ->andReturn([
                'health' => [
                    'current_character_health' => 0,
                    'current_monster_health' => 5,
                ],
            ]);
        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->once()
            ->with(Mockery::type(Character::class), [
                'selected_monster_id' => $trainingMonster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ], true)
            ->andReturn($fightData);
        $this->monsterFightService
            ->shouldReceive('fightMonster')
            ->times(100)
            ->with(Mockery::type(Character::class), AttackTypeValue::ATTACK, false, true)
            ->andReturn($fightData);

        $this->battleEventHandler
            ->shouldReceive('processRevive')
            ->once()
            ->andReturn($this->character);

        $result = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation,
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertEquals(AutomatedFightResultType::TRAINING_STALLED_RETRY, $result->getResultType());
        $this->assertEquals(1, $result->getStalledAttempt());
        $this->assertTrue($result->isTraining());
        $this->assertFalse($result->hasEndedAutomation());
    }

    public function testHandleYieldsRecoveryTrainingAfterBoundedKillCount(): void
    {
        Event::fake();

        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];
        $trainingMonster = $this->factionLoyaltyFactory->getTrainingMonstersForMap($bountyMonster->gameMap)[0];

        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->once()
            ->with(Mockery::type(Character::class), [
                'selected_monster_id' => $bountyMonster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ], true)
            ->andReturn([
                'health' => [
                    'current_character_health' => 0,
                    'current_monster_health' => 5,
                ],
            ]);
        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->once()
            ->with(Mockery::type(Character::class), [
                'selected_monster_id' => $trainingMonster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ], true)
            ->andReturn([
                'health' => [
                    'current_character_health' => 10,
                    'current_monster_health' => 0,
                ],
            ]);
        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->times(24)
            ->with(Mockery::type(Character::class), [
                'selected_monster_id' => $trainingMonster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ], true, false, true)
            ->andReturn([
                'health' => [
                    'current_character_health' => 10,
                    'current_monster_health' => 0,
                ],
            ]);

        $this->battleEventHandler
            ->shouldReceive('processRevive')
            ->once()
            ->andReturn($this->character);

        $this->characterRewardService
            ->shouldReceive('setCharacter')
            ->times(25)
            ->andReturnSelf();
        $this->characterRewardService
            ->shouldReceive('fetchXpForMonster')
            ->times(25)
            ->with(Mockery::on(fn (Monster $monster): bool => $monster->id === $trainingMonster->id))
            ->andReturn(10);

        $this->skillService
            ->shouldReceive('setSkillInTraining')
            ->times(25)
            ->andReturnSelf();
        $this->skillService
            ->shouldReceive('getXpForSkillIntraining')
            ->times(25)
            ->with(Mockery::type(Character::class), $trainingMonster->xp)
            ->andReturn(5);

        $this->battleEventHandler
            ->shouldReceive('processMonsterDeath')
            ->once()
            ->with($this->character->id, $trainingMonster->id, [
                'total_creatures' => 25,
                'total_xp' => 250,
                'total_faction_points' => 0,
                'total_skill_xp' => 125,
                'skip_faction_loyalty_update_event' => true,
            ]);

        $result = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation,
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertEquals(AutomatedFightResultType::TRAINING_BATCH_YIELDED, $result->getResultType());
        $this->assertEquals(25, $result->getTrainingKills());
        $this->assertFalse($result->hasEndedAutomation());
    }

    public function testYieldedRecoveryTrainingFightResumesWithoutFreshMonsterSetup(): void
    {
        Event::fake();

        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];
        $trainingMonster = $this->factionLoyaltyFactory->getTrainingMonstersForMap($bountyMonster->gameMap)[0];
        $clockCalls = 0;
        $handler = new AutomatedBountyFightHandler(
            $this->monsterFightService,
            $this->battleEventHandler,
            $this->characterRewardService,
            $this->skillService,
            new AutomatedFightResult,
            function () use (&$clockCalls): float {
                $clockCalls++;

                return $clockCalls <= 2 ? 0.0 : 91.0;
            },
        );
        $this->factionLoyaltyAutomation->update([
            'failed_bounty_monster_id' => $bountyMonster->id,
            'last_fight_outcome' => AutomatedFightResultType::TRAINING_FIGHT_YIELDED->value,
            'last_fight_monster_id' => $trainingMonster->id,
            'last_fight_was_bounty_target' => false,
            'last_fight_was_training' => true,
            'last_fight_stalled_attempt' => 0,
        ]);
        $this->monsterFightService
            ->shouldNotReceive('setupMonster');
        $this->monsterFightService
            ->shouldReceive('fightMonster')
            ->once()
            ->with(Mockery::type(Character::class), AttackTypeValue::ATTACK, false, true)
            ->andReturn([
                'health' => [
                    'current_character_health' => 10,
                    'current_monster_health' => 0,
                ],
            ]);
        $this->characterRewardService
            ->shouldReceive('setCharacter')
            ->once()
            ->andReturnSelf();
        $this->characterRewardService
            ->shouldReceive('fetchXpForMonster')
            ->once()
            ->with(Mockery::on(fn (Monster $monster): bool => $monster->id === $trainingMonster->id))
            ->andReturn(10);
        $this->skillService
            ->shouldReceive('setSkillInTraining')
            ->once()
            ->andReturnSelf();
        $this->skillService
            ->shouldReceive('getXpForSkillIntraining')
            ->once()
            ->with(Mockery::type(Character::class), $trainingMonster->xp)
            ->andReturn(5);
        $this->battleEventHandler
            ->shouldReceive('processMonsterDeath')
            ->once()
            ->with($this->character->id, $trainingMonster->id, [
                'total_creatures' => 1,
                'total_xp' => 10,
                'total_faction_points' => 0,
                'total_skill_xp' => 5,
                'skip_faction_loyalty_update_event' => true,
            ]);

        $result = $handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation->refresh(),
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertSame(AutomatedFightResultType::TRAINING_BATCH_YIELDED, $result->getResultType());
        $this->assertSame(1, $result->getTrainingKills());
    }

    public function testHandleEndsAutomationWhenBountyKillsCharacterAfterCompletedTraining(): void
    {
        Event::fake();

        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];

        $this->factionLoyaltyAutomation->update([
            'failed_bounty_monster_id' => $bountyMonster->id,
            'trained_failed_bounty_monster_id' => $bountyMonster->id,
        ]);

        $this->monsterFightService
            ->shouldReceive('setupMonster')
            ->once()
            ->andReturn([
                'health' => [
                    'current_character_health' => 0,
                    'current_monster_health' => 5,
                ],
            ]);

        $result = $this->handler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation->refresh(),
                $this->factionLoyaltyNpc,
                [
                    'monster_id' => $bountyMonster->id,
                    'required_amount' => 1,
                    'current_amount' => 0,
                ],
                AttackTypeValue::ATTACK,
                $this->fightLogger,
            )
            ->handle();

        $this->assertEquals(AutomatedFightResultType::DIED_TO_BOUNTY_AFTER_TRAINING, $result->getResultType());
        $this->assertTrue($result->isBountyTarget());
        $this->assertTrue($result->hasCharacterDied());
        $this->assertTrue($result->hasEndedAutomation());
    }
}
