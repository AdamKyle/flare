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

    public function testHandleReturnsInvalidStateWhenAttackLimitIsReached(): void
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

        $this->assertEquals(AutomatedFightResultType::NOT_ENOUGH_HEALTH_OR_INVALID_STATE, $result->getResultType());
        $this->assertTrue($result->hasEndedAutomation());
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

    public function testHandleCompletesTrainingBatchWhenFiftyTrainingMonstersDie(): void
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
            ->times(50)
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

        $this->battleEventHandler
            ->shouldReceive('processRevive')
            ->once()
            ->andReturn($this->character);

        $this->characterRewardService
            ->shouldReceive('setCharacter')
            ->times(50)
            ->andReturnSelf();
        $this->characterRewardService
            ->shouldReceive('fetchXpForMonster')
            ->times(50)
            ->with(Mockery::on(fn (Monster $monster): bool => $monster->id === $trainingMonster->id))
            ->andReturn(10);

        $this->skillService
            ->shouldReceive('setSkillInTraining')
            ->times(50)
            ->andReturnSelf();
        $this->skillService
            ->shouldReceive('getXpForSkillIntraining')
            ->times(50)
            ->with(Mockery::type(Character::class), $trainingMonster->xp)
            ->andReturn(5);

        $this->battleEventHandler
            ->shouldReceive('processMonsterDeath')
            ->once()
            ->with($this->character->id, $trainingMonster->id, [
                'total_creatures' => 50,
                'total_xp' => 500,
                'total_faction_points' => 0,
                'total_skill_xp' => 250,
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

        $this->assertEquals(AutomatedFightResultType::TRAINING_BATCH_COMPLETED, $result->getResultType());
        $this->assertEquals(50, $result->getTrainingKills());
        $this->assertTrue($result->hasTrainedForFailedBounty());
    }

    public function testHandleEndsAutomationWhenBountyKillsCharacterAfterCompletedTraining(): void
    {
        Event::fake();

        $bountyMonster = $this->factionLoyaltyFactory->getBountyMonstersForNpc($this->factionLoyaltyNpc)[0];

        $this->factionLoyaltyAutomation->update([
            'failed_bounty_monster_id' => $bountyMonster->id,
        ]);
        $this->factionLoyaltyAutomation->log()->update([
            'fight_logs' => [
                [
                    'failed_bounty_monster_id' => $bountyMonster->id,
                    'outcome' => AutomatedFightResultType::TRAINING_BATCH_COMPLETED->value,
                ],
            ],
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
