<?php

namespace App\Game\Core\Listeners;

use App\Flare\Services\CharacterRewardService;
use App\Game\Core\Events\CharacterLevelUpEvent;
use App\Game\Core\Events\UpdateCharacterEvent;

class UpdateCharacterListener {

    /**
     * Handle the event.
     *
     * @param UpdateCharacterEvent $event
     * @return void
     */
    public function handle(UpdateCharacterEvent $event) {
        $characterRewardService = resolve(CharacterRewardService::class, [
            'character' => $event->character,
        ]);

        $characterRewardService->distributeGoldAndXp($event->monster);

        $character = $characterRewardService->getCharacter();

        event(new CharacterLevelUpEvent($character));
    }
}
