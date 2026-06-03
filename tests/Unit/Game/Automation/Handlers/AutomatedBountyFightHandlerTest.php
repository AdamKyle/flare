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

    protected function setUp(): void
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

    protected function tearDown(): void
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

    public function test_automated_bounty_fight_handler_resolves_from_container(): void
    {
        $this->assertInstanceOf(
            AutomatedBountyFightHandler::class,
            resolve(AutomatedBountyFightHandler::class),
        );
    }

    public function test_handle_returns_invalid_task_when_task_is_missing_required_fields(): void
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

    public function test_handle_returns_monster_not_found_when_bounty_monster_does_not_exist(): void
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

    public function test_handle_returns_bounty_completed_when_task_already_has_enough_kills(): void
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

    public function test_handle_completes_bounty_when_bounty_monster_dies(): void
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

    public function test_handle_returns_invalid_state_when_fight_setup_returns_empty_data(): void
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

    public function test_handle_returns_bounty_stalled_retry_when_attack_limit_is_reached(): void
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

    public function test_handle_retries_cached_bounty_fight_without_setting_up_monster_when_bounty_stalled(): void
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

    public function test_handle_ends_automation_and_dispatches_warning_when_bounty_stalled_max_attempts_is_reached(): void
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

        $message = 'You tried to kill '.$bountyMonster->name.' 10 times and failed to do so. The NPC: '.$this->factionLoyaltyNpc->npc->real_name.', is now infuriated. Check your gear child. Go to Faction Loyalty.';

        $this->assertEquals(AutomatedFightResultType::BOUNTY_STALLED_MAX_ATTEMPTS_REACHED, $result->getResultType());
        $this->assertEquals(10, $result->getStalledAttempt());
        $this->assertTrue($result->hasEndedAutomation());
        $this->assertEquals([
            'message' => $message,
            'read' => false,
        ], $result->getWarningNotice());
        Event::assertDispatched(ServerMessageEvent::class, fn (ServerMessageEvent $event): bool => $event->message === $message);
    }

    public function test_handle_returns_no_training_monster_found_when_bounty_kills_character_and_no_training_monster_exists(): void
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

    public function test_handle_returns_died_during_training_when_training_monster_kills_character(): void
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

    public function test_handle_returns_invalid_state_when_training_fight_does_not_resolve(): void
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

    public function test_handle_returns_training_stalled_retry_when_training_attack_limit_is_reached(): void
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

    public function test_handle_completes_training_batch_when_fifty_training_monsters_die(): void
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

        $this->assertEquals(AutomatedFightResultType::TRAINING_BATCH_COMPLETED, $result->getResultType());
        $this->assertEquals(50, $result->getTrainingKills());
        $this->assertTrue($result->hasTrainedForFailedBounty());
    }

    public function test_handle_ends_automation_when_bounty_kills_character_after_completed_training(): void
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
