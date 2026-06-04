<?php

namespace App\Game\Automation\Loggers;

use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationLog;
use App\Game\Automation\Contracts\AutomatedCraftingLogger;
use App\Game\Automation\Enums\FactionLoyaltyCoordinatorAction;
use App\Game\Automation\Values\AutomatedCraftingResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FactionLoyaltyAutomationCraftingLogger implements AutomatedCraftingLogger
{
    private FactionLoyaltyAutomation $factionLoyaltyAutomation;

    /**
     * Set up the logger.
     */
    public function setUp(FactionLoyaltyAutomation $factionLoyaltyAutomation): FactionLoyaltyAutomationCraftingLogger
    {
        $this->factionLoyaltyAutomation = $factionLoyaltyAutomation;

        return $this;
    }

    /**
     * Log the automated crafting result.
     */
    public function log(AutomatedCraftingResult $automatedCraftingResult): void
    {
        $factionLoyaltyAutomationLog = FactionLoyaltyAutomationLog::query()
            ->where('faction_loyalty_automation_id', $this->factionLoyaltyAutomation->id)
            ->select('id')
            ->first();

        if (is_null($factionLoyaltyAutomationLog)) {
            $factionLoyaltyAutomationLog = FactionLoyaltyAutomationLog::create([
                'faction_loyalty_automation_id' => $this->factionLoyaltyAutomation->id,
                'fight_logs' => [],
                'crafting_logs' => [],
            ]);
        }

        $logEntryId = (string) Str::uuid();
        $automatedCraftingResult->setLogEntryId($logEntryId);

        $this->appendCraftingLog($factionLoyaltyAutomationLog->id, [
            'log_entry_id' => $logEntryId,
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
        ]);

        $this->factionLoyaltyAutomation->update([
            'last_automation_action' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'last_automation_action_at' => now(),
        ]);
    }

    /**
     * Append a crafting log entry without hydrating the full JSON log.
     */
    private function appendCraftingLog(int $factionLoyaltyAutomationLogId, array $craftingLog): void
    {
        DB::update(
            "UPDATE faction_loyalty_automation_logs SET crafting_logs = JSON_ARRAY_APPEND(COALESCE(crafting_logs, JSON_ARRAY()), '$', JSON_EXTRACT(?, '$')), updated_at = ? WHERE id = ?",
            [
                json_encode($craftingLog),
                now(),
                $factionLoyaltyAutomationLogId,
            ]
        );
    }
}
