<?php

namespace Tests\Unit\Game\Automation\Loggers;

use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationLog;
use App\Game\Automation\Enums\AutomatedCraftingResultType;
use App\Game\Automation\Loggers\FactionLoyaltyAutomationCraftingLogger;
use App\Game\Automation\Values\AutomatedCraftingResult;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;

class FactionLoyaltyAutomationCraftingLoggerTest extends TestCase
{
    use RefreshDatabase;

    private ?Character $character = null;

    private ?FactionLoyaltyFactory $factionLoyaltyFactory = null;

    private ?FactionLoyaltyAutomation $factionLoyaltyAutomation = null;

    private ?FactionLoyaltyAutomationCraftingLogger $craftingLogger = null;

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
        $this->craftingLogger = resolve(FactionLoyaltyAutomationCraftingLogger::class)
            ->setUp($this->factionLoyaltyAutomation);
    }

    public function tearDown(): void
    {
        Carbon::setTestNow();

        $this->character = null;
        $this->factionLoyaltyFactory = null;
        $this->factionLoyaltyAutomation = null;
        $this->craftingLogger = null;

        parent::tearDown();
    }

    public function testLogCreatesFactionLoyaltyAutomationLogRowWhenNoneExists(): void
    {
        $this->factionLoyaltyAutomation->log()->delete();

        $now = Carbon::parse('2026-01-15 10:11:12');

        Carbon::setTestNow($now);

        $automatedCraftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::ITEM_NOT_FOUND, 123);

        $this->craftingLogger->log($automatedCraftingResult);

        $factionLoyaltyAutomationLog = FactionLoyaltyAutomationLog::query()
            ->where('faction_loyalty_automation_id', $this->factionLoyaltyAutomation->id)
            ->first();

        $this->assertNotNull($factionLoyaltyAutomationLog);
        $this->assertEquals($this->factionLoyaltyAutomation->id, $factionLoyaltyAutomationLog->faction_loyalty_automation_id);
        $this->assertEquals([], $factionLoyaltyAutomationLog->fight_logs);
        $this->assertNotNull($factionLoyaltyAutomationLog->crafting_logs[0]['log_entry_id']);

        $logEntryId = $factionLoyaltyAutomationLog->crafting_logs[0]['log_entry_id'];

        $this->assertEquals([
            [
                'log_entry_id' => $logEntryId,
                'result' => AutomatedCraftingResultType::ITEM_NOT_FOUND->value,
                'target_item_id' => 123,
                'crafted_item_id' => null,
                'crafted_item_name' => null,
                'crafting_type' => '',
                'target_item_level' => 0,
                'current_skill_level' => 0,
                'started_below_target_level' => false,
                'crafted_target_item' => false,
                'successful_target_crafts' => 0,
                'successful_training_crafts' => 0,
                'attempts' => 0,
                'failed_rolls' => 0,
                'gold_spent' => 0,
                'created_at' => $now->toDateTimeString(),
            ],
        ], $factionLoyaltyAutomationLog->crafting_logs);
    }

    public function testLogAppendsCraftingLogWhenLogRowAlreadyExists(): void
    {
        $existingCraftingLog = [
            'result' => AutomatedCraftingResultType::NO_CRAFTING_SKILL->value,
            'target_item_id' => 50,
            'created_at' => '2026-01-01 00:00:00',
        ];

        $this->factionLoyaltyFactory->getFactionLoyaltyAutomationLog()->update([
            'crafting_logs' => [$existingCraftingLog],
        ]);

        $automatedCraftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::CRAFTED_TRAINING_ITEM, 75)
            ->setCraftedItemId(76)
            ->setCraftedItemName('Training Sword');

        $this->craftingLogger->log($automatedCraftingResult);

        $factionLoyaltyAutomationLog = $this->factionLoyaltyAutomation->log()->first();

        $this->assertCount(2, $factionLoyaltyAutomationLog->crafting_logs);
        $this->assertEquals($existingCraftingLog, $factionLoyaltyAutomationLog->crafting_logs[0]);
        $this->assertEquals(AutomatedCraftingResultType::CRAFTED_TRAINING_ITEM->value, $factionLoyaltyAutomationLog->crafting_logs[1]['result']);
        $this->assertEquals(75, $factionLoyaltyAutomationLog->crafting_logs[1]['target_item_id']);
        $this->assertEquals(76, $factionLoyaltyAutomationLog->crafting_logs[1]['crafted_item_id']);
        $this->assertEquals('Training Sword', $factionLoyaltyAutomationLog->crafting_logs[1]['crafted_item_name']);
    }

    public function testLogPreservesExistingFightLogsWhenAddingCraftingLogs(): void
    {
        $existingFightLogs = [
            [
                'outcome' => 'training_batch_completed',
                'monster_id' => 100,
                'kills' => 4,
            ],
        ];

        $this->factionLoyaltyFactory->getFactionLoyaltyAutomationLog()->update([
            'fight_logs' => $existingFightLogs,
            'crafting_logs' => [],
        ]);

        $automatedCraftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::NO_TRAINING_ITEM, 90);

        $this->craftingLogger->log($automatedCraftingResult);

        $factionLoyaltyAutomationLog = $this->factionLoyaltyAutomation->log()->first();

        $this->assertEquals($existingFightLogs, $factionLoyaltyAutomationLog->fight_logs);
        $this->assertCount(1, $factionLoyaltyAutomationLog->crafting_logs);
    }

    public function testLogStoresEveryCraftingPayloadField(): void
    {
        $now = Carbon::parse('2026-02-03 04:05:06');

        Carbon::setTestNow($now);

        $this->factionLoyaltyFactory->getFactionLoyaltyAutomationLog()->update([
            'crafting_logs' => [],
        ]);

        $automatedCraftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::CRAFTED_TARGET_ITEM, 200)
            ->setCraftedItemId(201)
            ->setCraftedItemName('Forged Blade')
            ->setCraftingType('weapon')
            ->setTargetItemLevel(15)
            ->setCurrentSkillLevel(13)
            ->setStartedBelowTargetLevel(true)
            ->setCraftedTargetItem(true)
            ->setSuccessfulTargetCrafts(2)
            ->setSuccessfulTrainingCrafts(3)
            ->setAttempts(5)
            ->setFailedRolls(1)
            ->setGoldSpent(1250);

        $this->craftingLogger->log($automatedCraftingResult);

        $factionLoyaltyAutomationLog = $this->factionLoyaltyAutomation->log()->first();

        $this->assertNotNull($factionLoyaltyAutomationLog->crafting_logs[0]['log_entry_id']);

        $logEntryId = $factionLoyaltyAutomationLog->crafting_logs[0]['log_entry_id'];

        $this->assertEquals([
            [
                'log_entry_id' => $logEntryId,
                'result' => AutomatedCraftingResultType::CRAFTED_TARGET_ITEM->value,
                'target_item_id' => 200,
                'crafted_item_id' => 201,
                'crafted_item_name' => 'Forged Blade',
                'crafting_type' => 'weapon',
                'target_item_level' => 15,
                'current_skill_level' => 13,
                'started_below_target_level' => true,
                'crafted_target_item' => true,
                'successful_target_crafts' => 2,
                'successful_training_crafts' => 3,
                'attempts' => 5,
                'failed_rolls' => 1,
                'gold_spent' => 1250,
                'created_at' => $now->toDateTimeString(),
            ],
        ], $factionLoyaltyAutomationLog->crafting_logs);
    }

    public function testLogUsesEmptyCraftingLogsWhenExistingCraftingLogsAreNull(): void
    {
        $this->factionLoyaltyFactory->getFactionLoyaltyAutomationLog()->update([
            'crafting_logs' => null,
        ]);

        $automatedCraftingResult = (new AutomatedCraftingResult)
            ->setUp(AutomatedCraftingResultType::MAX_ATTEMPTS_REACHED, 300);

        $this->craftingLogger->log($automatedCraftingResult);

        $factionLoyaltyAutomationLog = $this->factionLoyaltyAutomation->log()->first();

        $this->assertCount(1, $factionLoyaltyAutomationLog->crafting_logs);
        $this->assertEquals(AutomatedCraftingResultType::MAX_ATTEMPTS_REACHED->value, $factionLoyaltyAutomationLog->crafting_logs[0]['result']);
        $this->assertEquals(300, $factionLoyaltyAutomationLog->crafting_logs[0]['target_item_id']);
    }
}
