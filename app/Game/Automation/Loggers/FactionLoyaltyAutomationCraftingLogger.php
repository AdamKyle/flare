<?php

namespace App\Game\Automation\Loggers;

use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationLog;
use App\Game\Automation\Contracts\AutomatedCraftingLogger;
use App\Game\Automation\Values\AutomatedCraftingResult;

class FactionLoyaltyAutomationCraftingLogger implements AutomatedCraftingLogger
{
    private FactionLoyaltyAutomation $factionLoyaltyAutomation;

    /**
     * Set up the logger.
     *
     * @param FactionLoyaltyAutomation $factionLoyaltyAutomation
     * @return FactionLoyaltyAutomationCraftingLogger
     */
    public function setUp(FactionLoyaltyAutomation $factionLoyaltyAutomation): FactionLoyaltyAutomationCraftingLogger
    {
        $this->factionLoyaltyAutomation = $factionLoyaltyAutomation;

        return $this;
    }

    /**
     * Log the automated crafting result.
     *
     * @param AutomatedCraftingResult $automatedCraftingResult
     * @return void
     */
    public function log(AutomatedCraftingResult $automatedCraftingResult): void
    {
        $factionLoyaltyAutomationLog = FactionLoyaltyAutomationLog::firstOrCreate(
            [
                'faction_loyalty_automation_id' => $this->factionLoyaltyAutomation->id,
            ],
            [
                'fight_logs' => [],
                'crafting_logs' => [],
            ]
        );

        $craftingLogs = $factionLoyaltyAutomationLog->crafting_logs ?? [];

        $craftingLogs[] = [
            'result' => $automatedCraftingResult->getResultType()->value,
            'target_item_id' => $automatedCraftingResult->getTargetItemId(),
            'crafted_item_id' => $automatedCraftingResult->getCraftedItemId(),
            'crafted_item_name' => $automatedCraftingResult->getCraftedItemName(),
            'crafting_type' => $automatedCraftingResult->getCraftingType(),
            'target_item_level' => $automatedCraftingResult->getTargetItemLevel(),
            'current_skill_level' => $automatedCraftingResult->getCurrentSkillLevel(),
            'started_below_target_level' => $automatedCraftingResult->hasStartedBelowTargetLevel(),
            'crafted_target_item' => $automatedCraftingResult->hasCraftedTargetItem(),
            'successful_target_crafts' => $automatedCraftingResult->getSuccessfulTargetCrafts(),
            'successful_training_crafts' => $automatedCraftingResult->getSuccessfulTrainingCrafts(),
            'attempts' => $automatedCraftingResult->getAttempts(),
            'failed_rolls' => $automatedCraftingResult->getFailedRolls(),
            'gold_spent' => $automatedCraftingResult->getGoldSpent(),
            'created_at' => now()->toDateTimeString(),
        ];

        $factionLoyaltyAutomationLog->update([
            'crafting_logs' => $craftingLogs,
        ]);
    }
}