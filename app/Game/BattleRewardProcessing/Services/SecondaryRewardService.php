<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\ClassRanks\Services\ClassRankService;
use Facades\App\Game\Skills\Handlers\UpdateItemSkill;
use Throwable;

class SecondaryRewardService
{
    use FetchEquipped;

    private ClassRankService $classRankService;

    /**
     * @param ClassRankService $classRankService
     */
    public function __construct(ClassRankService $classRankService)
    {
        $this->classRankService = $classRankService;
    }

    /**
     * Return the invalid whole-XP-per-kill calculation failure recorded during the most recent
     * secondary reward operation, if one occurred, so the caller can fail the owning reward
     * operation instead of treating a corrupted calculation as a silent or extreme XP reward.
     *
     * @return ?Throwable
     */
    public function secondaryRewardCalculationFailure(): ?Throwable
    {
        return $this->classRankService->classRankXpCalculationFailure();
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
     * @param ?float $classRankGemBonus
     * @param ?float $classSpecialtyGemBonus
     * @return void
     */
    public function handleSecondaryRewards(Character $character, int $killCount = 1, bool $dispatchTopBarEvent = true, ?float $classRankGemBonus = null, ?float $classSpecialtyGemBonus = null): void
    {

        $this->classRankService->giveXpToClassRank($character, $killCount, $classRankGemBonus);

        if (! is_null($this->classRankService->classRankXpCalculationFailure())) {
            return;
        }

        $this->classRankService->giveXpToMasteries($character, $killCount);

        $this->classRankService->giveXpToEquippedClassSpecialties($character, $killCount, $classSpecialtyGemBonus);

        if (! is_null($this->classRankService->classRankXpCalculationFailure())) {
            return;
        }

        $this->handleItemSkillUpdate($character, $killCount);

        if ($dispatchTopBarEvent && $character->isLoggedIn()) {
            event(new UpdateCharacterBaseDetailsEvent($character));
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
