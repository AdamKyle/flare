<?php

namespace App\Game\Skills\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\ItemSkillProgression;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class UpdateItemSkill
{
    public function __construct(private UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes, private BattleMessageHandler $battleMessageHandler) {}

    public function updateItemSkill(Character $character, Item $item, int $killCount = 1): void
    {

        if ($killCount === 1) {
            $skillProgressionToUpdate = $item->itemSkillProgressions->where('is_training', true)->first();

            if (is_null($skillProgressionToUpdate)) {
                return;
            }

            if ($skillProgressionToUpdate->current_level >= $skillProgressionToUpdate->itemSkill->max_level) {
                return;
            }

            $skillProgressionToUpdate->update([
                'current_kill' => $skillProgressionToUpdate->current_kill + 1,
            ]);

            $skillProgressionToUpdate = $skillProgressionToUpdate->refresh();

            $this->battleMessageHandler->handleItemKillCountMessage($character->user, $item->affix_name, $skillProgressionToUpdate->itemSkill->name, $skillProgressionToUpdate->current_kill, $skillProgressionToUpdate->itemSkill->total_kills_needed);

            $this->levelUpSkill($character, $skillProgressionToUpdate);

            return;
        }

        $skillProgressionToUpdate = $item->itemSkillProgressions->where('is_training', true)->first();

        if (is_null($skillProgressionToUpdate)) {
            return;
        }

        if ($skillProgressionToUpdate->current_level >= $skillProgressionToUpdate->itemSkill->max_level) {
            return;
        }

        $totalKillsNeeded = (int) $skillProgressionToUpdate->itemSkill->total_kills_needed;

        if ($totalKillsNeeded <= 0) {
            return;
        }

        $startingLevel = (int) $skillProgressionToUpdate->current_level;
        $maxLevel = (int) $skillProgressionToUpdate->itemSkill->max_level;

        $currentKill = (int) $skillProgressionToUpdate->current_kill;

        if ($currentKill < 0) {
            $currentKill = 0;
        }

        [$newLevel, $newKillCount, $levelsGained] = $this->applyKillCountToItemSkill(
            $startingLevel,
            $currentKill,
            $totalKillsNeeded,
            $killCount,
            $maxLevel
        );

        $skillProgressionToUpdate->update([
            'current_level' => $newLevel,
            'current_kill' => $newKillCount,
        ]);

        $skillProgressionToUpdate = $skillProgressionToUpdate->refresh();

        $this->battleMessageHandler->handleItemKillCountMessage(
            $character->user,
            $item->affix_name,
            $skillProgressionToUpdate->itemSkill->name,
            $skillProgressionToUpdate->current_kill,
            $skillProgressionToUpdate->itemSkill->total_kills_needed
        );

        if ($levelsGained > 0) {
            $character = $character->refresh();

            $this->updateCharacterAttackTypes->updateCache($character->refresh());

            for ($gainedLevelIndex = 0; $gainedLevelIndex < $levelsGained; $gainedLevelIndex++) {
                $newMessageLevel = $startingLevel + $gainedLevelIndex + 1;

                if ($newMessageLevel > $maxLevel) {
                    break;
                }

                ServerMessageHandler::sendBasicMessage(
                    $character->user,
                    'Your equipped artifacts: ' . $skillProgressionToUpdate->item->affix_name . '\'s Skill: ' . $skillProgressionToUpdate->itemSkill->name . ' has gained a new level and is now level: ' . $newMessageLevel . '.'
                );
            }
        }
    }

    protected function levelUpSkill(Character $character, ItemSkillProgression $itemSkillProgression)
    {
        if ($itemSkillProgression->current_kill >= $itemSkillProgression->itemSkill->total_kills_needed) {
            $itemSkillProgression->update([
                'current_level' => $itemSkillProgression->current_level + 1,
                'current_kill' => 0,
            ]);

            $character = $character->refresh();
            $itemSkillProgression = $itemSkillProgression->refresh();

            $this->updateCharacterAttackTypes->updateCache($character->refresh());

            ServerMessageHandler::sendBasicMessage(
                $character->user,
                'Your equipped artifacts: '.$itemSkillProgression->item->affix_name.'\'s Skill: '.$itemSkillProgression->itemSkill->name.' has gained a new level and is now level: '.$itemSkillProgression->current_level.'.'
            );
        }
    }

    private function applyKillCountToItemSkill(
        int $currentLevel,
        int $currentKill,
        int $killsNeededPerLevel,
        int $killCount,
        int $maxLevel
    ): array {
        if ($killCount <= 0) {
            return [$currentLevel, $currentKill, 0];
        }

        if ($currentLevel >= $maxLevel) {
            return [$maxLevel, 0, 0];
        }

        if ($killsNeededPerLevel <= 0) {
            return [$currentLevel, $currentKill, 0];
        }

        $startingLevel = $currentLevel;

        $killsToFirstLevel = $killsNeededPerLevel - $currentKill;

        if ($killsToFirstLevel <= 0) {
            $killsToFirstLevel = 1;
        }

        if ($killCount < $killsToFirstLevel) {
            return [$currentLevel, $currentKill + $killCount, 0];
        }

        $currentLevel++;

        if ($currentLevel >= $maxLevel) {
            return [$maxLevel, 0, $maxLevel - $startingLevel];
        }

        $remainingKills = $killCount - $killsToFirstLevel;

        $levelsAvailable = $maxLevel - $currentLevel;

        $additionalLevels = intdiv($remainingKills, $killsNeededPerLevel);

        if ($additionalLevels > $levelsAvailable) {
            $additionalLevels = $levelsAvailable;
        }

        $currentLevel += $additionalLevels;

        $remainingKills -= $additionalLevels * $killsNeededPerLevel;

        if ($currentLevel >= $maxLevel) {
            return [$maxLevel, 0, $maxLevel - $startingLevel];
        }

        return [$currentLevel, $remainingKills, $currentLevel - $startingLevel];
    }
}
