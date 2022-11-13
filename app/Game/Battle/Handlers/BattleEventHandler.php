<?php

namespace App\Game\Battle\Handlers;

use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Battle\Events\CharacterRevive;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Mercenaries\Services\MercenaryService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\Monster;
use App\Game\Battle\Services\BattleRewardProcessing;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class BattleEventHandler {

    /**
     * @var BattleRewardProcessing $battleRewardProcessing
     */
    private BattleRewardProcessing $battleRewardProcessing;

    /**
     * @var MercenaryService $mercenaryService
     */
    private MercenaryService $mercenaryService;


    /**
     * @param BattleRewardProcessing $battleRewardProcessing
     * @param MercenaryService $mercenaryService
     */
    public function __construct(BattleRewardProcessing $battleRewardProcessing, MercenaryService $mercenaryService) {
        $this->battleRewardProcessing = $battleRewardProcessing;
        $this->mercenaryService       = $mercenaryService;
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
     * @param bool $isAutomation
     * @return void
     */
    public function processMonsterDeath(int $characterId, int $monsterId, bool $isAutomation = false): void {
        $monster   = Monster::find($monsterId);
        $character = Character::find($characterId);

        $this->battleRewardProcessing->handleMonster($character, $monster, $isAutomation);

        $this->mercenaryService->giveXpToMercenaries($character);
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
}
