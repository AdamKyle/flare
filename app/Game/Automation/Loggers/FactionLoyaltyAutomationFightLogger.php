<?php

namespace App\Game\Automation\Loggers;

use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationLog;
use App\Game\Automation\Enums\FactionLoyaltyCoordinatorAction;
use App\Game\Automation\Values\AutomatedFightResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FactionLoyaltyAutomationFightLogger
{
    private FactionLoyaltyAutomation $factionLoyaltyAutomation;

    /**
     * Set up the logger.
     *
     * @param FactionLoyaltyAutomation $factionLoyaltyAutomation
     * @return FactionLoyaltyAutomationFightLogger
     */
    public function setUp(FactionLoyaltyAutomation $factionLoyaltyAutomation): FactionLoyaltyAutomationFightLogger
    {
        $this->factionLoyaltyAutomation = $factionLoyaltyAutomation;

        return $this;
    }

    /**
     * Log the automated fight result.
     *
     * @param AutomatedFightResult $automatedFightResult
     * @return void
     */
    public function log(AutomatedFightResult $automatedFightResult): void
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
        $automatedFightResult->setLogEntryId($logEntryId);

        $this->appendFightLog($factionLoyaltyAutomationLog->id, [
            'log_entry_id' => $logEntryId,
            'outcome' => $automatedFightResult->getResultType()->value,
            'monster_id' => $automatedFightResult->getMonsterId(),
            'monster_name' => $automatedFightResult->getMonsterName(),
            'is_bounty_target' => $automatedFightResult->isBountyTarget(),
            'is_training' => $automatedFightResult->isTraining(),
            'failed_bounty_monster_id' => $automatedFightResult->getFailedBountyMonsterId(),
            'trained_for_failed_bounty' => $automatedFightResult->hasTrainedForFailedBounty(),
            'kills' => $automatedFightResult->getKills(),
            'training_kills' => $automatedFightResult->getTrainingKills(),
            'bounty_kills' => $automatedFightResult->getBountyKills(),
            'total_creatures' => $automatedFightResult->getTotalCreatures(),
            'total_xp' => $automatedFightResult->getTotalXp(),
            'total_skill_xp' => $automatedFightResult->getTotalSkillXp(),
            'total_faction_points' => $automatedFightResult->getTotalFactionPoints(),
            'character_died' => $automatedFightResult->hasCharacterDied(),
            'ended_automation' => $automatedFightResult->hasEndedAutomation(),
            'stalled_attempt' => $automatedFightResult->getStalledAttempt(),
            'warning_notice' => $automatedFightResult->getWarningNotice(),
            'created_at' => now()->toDateTimeString(),
        ]);

        $automationState = [
            'last_automation_action' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'last_automation_action_at' => now(),
            'last_fight_monster_id' => $automatedFightResult->getMonsterId(),
            'last_fight_outcome' => $automatedFightResult->getResultType()->value,
            'last_fight_was_bounty_target' => $automatedFightResult->isBountyTarget(),
            'last_fight_was_training' => $automatedFightResult->isTraining(),
            'last_fight_stalled_attempt' => $automatedFightResult->getStalledAttempt(),
        ];

        if ($automatedFightResult->hasTrainedForFailedBounty()) {
            $automationState['trained_failed_bounty_monster_id'] = $automatedFightResult->getFailedBountyMonsterId();
        }

        $this->factionLoyaltyAutomation->update($automationState);
    }

    /**
     * Append a fight log entry without hydrating the full JSON log.
     *
     * @param int $factionLoyaltyAutomationLogId
     * @param array $fightLog
     * @return void
     */
    private function appendFightLog(int $factionLoyaltyAutomationLogId, array $fightLog): void
    {
        DB::update(
            "UPDATE faction_loyalty_automation_logs SET fight_logs = JSON_ARRAY_APPEND(COALESCE(fight_logs, JSON_ARRAY()), '$', JSON_EXTRACT(?, '$')), updated_at = ? WHERE id = ?",
            [
                json_encode($fightLog),
                now(),
                $factionLoyaltyAutomationLogId,
            ]
        );
    }
}
