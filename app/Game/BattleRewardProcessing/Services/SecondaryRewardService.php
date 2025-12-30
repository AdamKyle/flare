<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Game\Core\Events\UpdateTopBarEvent;
use Exception;
use Facades\App\Game\Skills\Handlers\UpdateItemSkill;

class SecondaryRewardService
{
    use FetchEquipped;

    private ClassRankService $classRankService;

    public function __construct(ClassRankService $classRankService)
    {
        $this->classRankService = $classRankService;
    }

    /**
     * Handle secondary rewards such as mercenaries and class ranks.
     *
     * - Give XP to class Rank
     * - Give XP to equipped class specials
     * - Handle character skill progression
     *
     * @param Character $character
     * @param int $killCount
     * @param bool $dispatchTopBarEvent
     * @return void
     * @throws Exception
     */
    public function handleSecondaryRewards(Character $character, int $killCount = 1, bool $dispatchTopBarEvent = true): void
    {

        $this->classRankService->giveXpToClassRank($character, $killCount);

        $this->classRankService->giveXpToMasteries($character, $killCount);

        $this->classRankService->giveXpToEquippedClassSpecialties($character, $killCount);

        $this->handleItemSkillUpdate($character, $killCount);

        if ($dispatchTopBarEvent && $character->isLoggedIn()) {
            event(new UpdateTopBarEvent($character));
        }
    }

    /**
     * Handle item skill updates for artifacts that are equipped with skill trees.
     *
     * @param Character $character
     * @param int $killCount
     * @return void
     */
    private function handleItemSkillUpdate(Character $character, int $killCount = 1): void
    {

        $equippedItems = $this->fetchEquipped($character);

        if (is_null($equippedItems)) {
            return;
        }

        $equippedItem = $equippedItems->filter(function ($slot) {
            return $slot->item->type === 'artifact';
        })->first();

        if (is_null($equippedItem)) {
            return;
        }

        UpdateItemSkill::updateItemSkill($character, $equippedItem->item, $killCount);
    }
}
