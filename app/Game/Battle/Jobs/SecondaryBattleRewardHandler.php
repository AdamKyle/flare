<?php

namespace App\Game\Battle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Flare\Models\Character;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Game\Mercenaries\Services\MercenaryService;
use Facades\App\Game\Skills\Handlers\UpdateItemSkill;
use App\Flare\Builders\Character\Traits\FetchEquipped;

class SecondaryBattleRewardHandler implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FetchEquipped;
    
    /**
     * @var Character $character
     */
    private Character $character;

    /**
     * @param Character $character
     */
    public function __construct(Character $character) {
        $this->character = $character;
    }

    /**
     * Handles secondary rewards
     *
     * @param MercenaryService $mercenaryService
     * @param ClassRankService $classRankService
     * @return void
     */
    public function handle(MercenaryService $mercenaryService, ClassRankService $classRankService): void {
        $mercenaryService->giveXpToMercenaries($this->character);

        $classRankService->giveXpToClassRank($this->character);

        $classRankService->giveXpToMasteries($this->character);

        $classRankService->giveXpToEquippedClassSpecialties($this->character);

        $this->handleItemSkillUpdate($this->character);

        event(new UpdateTopBarEvent($this->character->refresh()));
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
