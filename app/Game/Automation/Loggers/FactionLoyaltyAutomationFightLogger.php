<?php

namespace App\Game\Automation\Loggers;

use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationLog;
use App\Game\Automation\Values\AutomatedFightResult;

class FactionLoyaltyAutomationFightLogger
{
    private FactionLoyaltyAutomation $factionLoyaltyAutomation;

    /**
     * Set up the logger.
     */
    public function setUp(FactionLoyaltyAutomation $factionLoyaltyAutomation): FactionLoyaltyAutomationFightLogger
    {
        $this->factionLoyaltyAutomation = $factionLoyaltyAutomation;

        return $this;
    }

    /**
     * Log the automated fight result.
     */
    public function log(AutomatedFightResult $automatedFightResult): void
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

        $fightLogs = $factionLoyaltyAutomationLog->fight_logs ?? [];

        $fightLogs[] = [
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
            'fight_data' => $automatedFightResult->getFightData(),
            'stalled_attempt' => $automatedFightResult->getStalledAttempt(),
            'warning_notice' => $automatedFightResult->getWarningNotice(),
            'created_at' => now()->toDateTimeString(),
        ];

        $factionLoyaltyAutomationLog->update([
            'fight_logs' => $fightLogs,
        ]);
    }
}
