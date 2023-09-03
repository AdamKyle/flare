<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Models\Character;
use App\Game\Core\Events\UpdateTopBarEvent;
use Facades\App\Game\Skills\Handlers\UpdateItemSkill;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Game\Mercenaries\Services\MercenaryService;


class SecondaryRewardService {

    use FetchEquipped;

    /**
     * @var MercenaryService $mercenaryService
     */
    private MercenaryService $mercenaryService;

    /**
     * @var ClassRankService $classRankService
     */
    private ClassRankService $classRankService;

    /**
     * @param MercenaryService $mercenaryService
     * @param ClassRankService $classRankService
     */
    public function __construct(MercenaryService $mercenaryService, ClassRankService $classRankService) {
        $this->mercenaryService = $mercenaryService;
        $this->classRankService = $classRankService;
    }

    /**
     * Handle secondary rewards such as mercenaries and class ranks.
     *
     * - Gives XP to Mercenaries
     * - Give XP to class Rank
     * - Give XP to equipped class specials
     * - Handle character skill progression
     *
     * @param Character $character
     * @return void
     */
    public function handleSecondaryRewards(Character $character) {
        $this->mercenaryService->giveXpToMercenaries($character);

        $this->classRankService->giveXpToClassRank($character);

        $this->classRankService->giveXpToMasteries($character);

        $this->classRankService->giveXpToEquippedClassSpecialties($character);

        $this->handleItemSkillUpdate($character);

        if ($character->isLoggedIn()) {
            event (new UpdateTopBarEvent($character->refresh()));

            return;
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
