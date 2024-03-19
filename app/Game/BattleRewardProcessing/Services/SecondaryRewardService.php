<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Models\Character;
use App\Game\Core\Events\UpdateTopBarEvent;
use Exception;
use Facades\App\Game\Skills\Handlers\UpdateItemSkill;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Game\Mercenaries\Services\MercenaryService;


class SecondaryRewardService {

    use FetchEquipped;

    /**
     * @var ClassRankService $classRankService
     */
    private ClassRankService $classRankService;

    /**
     * @param ClassRankService $classRankService
     */
    public function __construct(ClassRankService $classRankService) {
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
     * @return void
     * @throws Exception
     */
    public function handleSecondaryRewards(Character $character) {
        $this->classRankService->giveXpToClassRank($character);

        $this->classRankService->giveXpToMasteries($character);

        $this->classRankService->giveXpToEquippedClassSpecialties($character);

        $this->handleItemSkillUpdate($character);

        if ($character->isLoggedIn()) {
            event (new UpdateTopBarEvent($character->refresh()));
        }
    }

    /**
     * Handle item skill updates for artifacts that are equipped with skill trees.
     *
     * @param Character $character
     * @return void
     */
    protected function handleItemSkillUpdate(Character $character): void {
        $equippedItems = $this->fetchEquipped($character);

        if (is_null($equippedItems)) {
            return;
        }

        $equippedItem = $equippedItems->filter(function($slot) {
            return $slot->item->type === 'artifact';
        })->first();

        if (is_null($equippedItem)) {
            return;
        }

        UpdateItemSkill::updateItemSkill($character, $equippedItem->item);
    }
}
