<?php

namespace App\Game\Battle\Handlers;

use App\Flare\Builders\Character\Traits\FetchEquipped;
use Facades\App\Game\Skills\Handlers\UpdateItemSkill;
use App\Game\Battle\Events\CharacterRevive;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Game\Mercenaries\Services\MercenaryService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\Monster;
use App\Game\Battle\Services\BattleRewardProcessing;
use Exception;

class BattleEventHandler {

    use FetchEquipped;

    /**
     * @var BattleRewardProcessing $battleRewardProcessing
     */
    private BattleRewardProcessing $battleRewardProcessing;

    /**
     * @var MercenaryService $mercenaryService
     */
    private MercenaryService $mercenaryService;

    /**
     * @var ClassRankService $classRankService
     */
    private ClassRankService $classRankService;


    /**
     * @param BattleRewardProcessing $battleRewardProcessing
     * @param MercenaryService $mercenaryService
     * @param ClassRankService $classRankService
     */
    public function __construct(BattleRewardProcessing $battleRewardProcessing,
                                MercenaryService $mercenaryService,
                                ClassRankService $classRankService,
    ) {
        $this->battleRewardProcessing = $battleRewardProcessing;
        $this->mercenaryService       = $mercenaryService;
        $this->classRankService       = $classRankService;
    }

    /**
     * Process the fact the character has died.
     *
     * @param Character $character
     * @return void
     */
    public function processDeadCharacter(Character $character): void {
        $character->update(['is_dead' => true]);

        $character = $character->refresh();

        event(new AttackTimeOutEvent($character));

        event(new ServerMessageEvent($character->user, 'You are dead. Please revive yourself by clicking revive.'));
        event(new UpdateCharacterStatus($character));
    }

    /**
     * Process the fact the monster has died.
     *
     * @param int $characterId
     * @param int $monsterId
     * @return void
     * @throws Exception
     */
    public function processMonsterDeath(int $characterId, int $monsterId): void {
        $monster   = Monster::find($monsterId);
        $character = Character::find($characterId);

        if (is_null($monster)) {
            \Log::error('Missing Monster for id: ' . $monsterId);

            return;
        }

        $this->battleRewardProcessing->handleMonster($character, $monster);

        $this->mercenaryService->giveXpToMercenaries($character);

        $this->classRankService->giveXpToClassRank($character);

        $this->classRankService->giveXpToMasteries($character);

        $this->classRankService->giveXpToEquippedClassSpecialties($character);

        $this->handleItemSkillUpdate($character);
    }

    /**
     * Handle when a character revives.
     *
     * @param Character $character
     * @return Character
     */
    public function processRevive(Character $character): Character {
        $character->update([
            'is_dead' => false
        ]);

        $characterInCelestialFight = CharacterInCelestialFight::where('character_id', $character->id)->first();

        if (!is_null($characterInCelestialFight)) {
            $characterInCelestialFight->update([
                'character_current_health' => $character->getInformation()->buildHealth(),
            ]);
        }

        event(new CharacterRevive($character->user, $character->getInformation()->buildHealth()));

        event(new UpdateCharacterStatus($character));

        return $character->refresh();
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
