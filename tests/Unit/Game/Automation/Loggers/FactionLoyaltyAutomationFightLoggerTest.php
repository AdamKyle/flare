<?php

namespace Tests\Unit\Game\Automation\Loggers;

use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationLog;
use App\Game\Automation\Enums\AutomatedFightResultType;
use App\Game\Automation\Loggers\FactionLoyaltyAutomationFightLogger;
use App\Game\Automation\Values\AutomatedFightResult;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;

class FactionLoyaltyAutomationFightLoggerTest extends TestCase
{
    use RefreshDatabase;

    private ?Character $character = null;

    private ?FactionLoyaltyFactory $factionLoyaltyFactory = null;

    private ?FactionLoyaltyAutomation $factionLoyaltyAutomation = null;

    private ?FactionLoyaltyAutomationFightLogger $fightLogger = null;

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
        $this->fightLogger = resolve(FactionLoyaltyAutomationFightLogger::class)
            ->setUp($this->factionLoyaltyAutomation);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        $this->character = null;
        $this->factionLoyaltyFactory = null;
        $this->factionLoyaltyAutomation = null;
        $this->fightLogger = null;

        parent::tearDown();
    }

    public function test_log_creates_faction_loyalty_automation_log_row_when_none_exists(): void
    {
        $this->factionLoyaltyAutomation->log()->delete();

        $now = Carbon::parse('2026-03-04 05:06:07');

        Carbon::setTestNow($now);

        $automatedFightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::MONSTER_NOT_FOUND);

        $this->fightLogger->log($automatedFightResult);

        $factionLoyaltyAutomationLog = FactionLoyaltyAutomationLog::query()
            ->where('faction_loyalty_automation_id', $this->factionLoyaltyAutomation->id)
            ->first();

        $this->assertNotNull($factionLoyaltyAutomationLog);
        $this->assertEquals($this->factionLoyaltyAutomation->id, $factionLoyaltyAutomationLog->faction_loyalty_automation_id);
        $this->assertEquals([], $factionLoyaltyAutomationLog->crafting_logs);
        $this->assertEquals([
            [
                'outcome' => AutomatedFightResultType::MONSTER_NOT_FOUND->value,
                'monster_id' => null,
                'monster_name' => null,
                'is_bounty_target' => false,
                'is_training' => false,
                'failed_bounty_monster_id' => null,
                'trained_for_failed_bounty' => false,
                'kills' => 0,
                'training_kills' => 0,
                'bounty_kills' => 0,
                'total_creatures' => 0,
                'total_xp' => 0,
                'total_skill_xp' => 0,
                'total_faction_points' => 0,
                'character_died' => false,
                'ended_automation' => false,
                'fight_data' => [],
                'stalled_attempt' => 0,
                'warning_notice' => null,
                'created_at' => $now->toDateTimeString(),
            ],
        ], $factionLoyaltyAutomationLog->fight_logs);
    }

    public function test_log_appends_fight_log_when_log_row_already_exists(): void
    {
        $existingFightLog = [
            'outcome' => AutomatedFightResultType::INVALID_TASK->value,
            'monster_id' => 10,
            'created_at' => '2026-01-01 00:00:00',
        ];

        $this->factionLoyaltyFactory->getFactionLoyaltyAutomationLog()->update([
            'fight_logs' => [$existingFightLog],
        ]);

        $automatedFightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::TRAINING_BATCH_COMPLETED)
            ->setMonsterId(20)
            ->setMonsterName('Training Monster');

        $this->fightLogger->log($automatedFightResult);

        $factionLoyaltyAutomationLog = $this->factionLoyaltyAutomation->log()->first();

        $this->assertCount(2, $factionLoyaltyAutomationLog->fight_logs);
        $this->assertEquals($existingFightLog, $factionLoyaltyAutomationLog->fight_logs[0]);
        $this->assertEquals(AutomatedFightResultType::TRAINING_BATCH_COMPLETED->value, $factionLoyaltyAutomationLog->fight_logs[1]['outcome']);
        $this->assertEquals(20, $factionLoyaltyAutomationLog->fight_logs[1]['monster_id']);
        $this->assertEquals('Training Monster', $factionLoyaltyAutomationLog->fight_logs[1]['monster_name']);
    }

    public function test_log_preserves_existing_crafting_logs_when_adding_fight_logs(): void
    {
        $existingCraftingLogs = [
            [
                'result' => 'crafted_target_item',
                'target_item_id' => 100,
                'attempts' => 3,
            ],
        ];

        $this->factionLoyaltyFactory->getFactionLoyaltyAutomationLog()->update([
            'fight_logs' => [],
            'crafting_logs' => $existingCraftingLogs,
        ]);

        $automatedFightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::BOUNTY_COMPLETED);

        $this->fightLogger->log($automatedFightResult);

        $factionLoyaltyAutomationLog = $this->factionLoyaltyAutomation->log()->first();

        $this->assertEquals($existingCraftingLogs, $factionLoyaltyAutomationLog->crafting_logs);
        $this->assertCount(1, $factionLoyaltyAutomationLog->fight_logs);
    }

    public function test_log_stores_every_fight_payload_field(): void
    {
        $now = Carbon::parse('2026-04-05 06:07:08');

        Carbon::setTestNow($now);

        $this->factionLoyaltyFactory->getFactionLoyaltyAutomationLog()->update([
            'fight_logs' => [],
        ]);

        $fightData = [
            'health' => [
                'current_character_health' => 15,
                'current_monster_health' => 0,
            ],
            'rewards' => [
                'gold' => 25,
            ],
        ];

        $automatedFightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::DIED_TO_BOUNTY_AFTER_TRAINING)
            ->setMonsterId(400)
            ->setMonsterName('Bounty Monster')
            ->setBountyTarget(true)
            ->setTraining(true)
            ->setFailedBountyMonsterId(401)
            ->setTrainedForFailedBounty(true)
            ->setKills(6)
            ->setTrainingKills(4)
            ->setBountyKills(2)
            ->setTotalCreatures(8)
            ->setTotalXp(1200)
            ->setTotalSkillXp(300)
            ->setTotalFactionPoints(45)
            ->setCharacterDied(true)
            ->setEndedAutomation(true)
            ->setFightData($fightData)
            ->setStalledAttempt(10)
            ->setWarningNotice([
                'message' => 'Warning message.',
                'read' => false,
            ]);

        $this->fightLogger->log($automatedFightResult);

        $factionLoyaltyAutomationLog = $this->factionLoyaltyAutomation->log()->first();

        $this->assertEquals([
            [
                'outcome' => AutomatedFightResultType::DIED_TO_BOUNTY_AFTER_TRAINING->value,
                'monster_id' => 400,
                'monster_name' => 'Bounty Monster',
                'is_bounty_target' => true,
                'is_training' => true,
                'failed_bounty_monster_id' => 401,
                'trained_for_failed_bounty' => true,
                'kills' => 6,
                'training_kills' => 4,
                'bounty_kills' => 2,
                'total_creatures' => 8,
                'total_xp' => 1200,
                'total_skill_xp' => 300,
                'total_faction_points' => 45,
                'character_died' => true,
                'ended_automation' => true,
                'fight_data' => $fightData,
                'stalled_attempt' => 10,
                'warning_notice' => [
                    'message' => 'Warning message.',
                    'read' => false,
                ],
                'created_at' => $now->toDateTimeString(),
            ],
        ], $factionLoyaltyAutomationLog->fight_logs);
    }

    public function test_log_uses_empty_fight_logs_when_existing_fight_logs_are_null(): void
    {
        $this->factionLoyaltyFactory->getFactionLoyaltyAutomationLog()->update([
            'fight_logs' => null,
        ]);

        $automatedFightResult = (new AutomatedFightResult)
            ->setUp(AutomatedFightResultType::ERROR)
            ->setMonsterId(500);

        $this->fightLogger->log($automatedFightResult);

        $factionLoyaltyAutomationLog = $this->factionLoyaltyAutomation->log()->first();

        $this->assertCount(1, $factionLoyaltyAutomationLog->fight_logs);
        $this->assertEquals(AutomatedFightResultType::ERROR->value, $factionLoyaltyAutomationLog->fight_logs[0]['outcome']);
        $this->assertEquals(500, $factionLoyaltyAutomationLog->fight_logs[0]['monster_id']);
    }
}
