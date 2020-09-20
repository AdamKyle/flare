<?php

namespace App\Game\Core\Listeners;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Services\CharacterRewardService;
use App\Game\Core\Events\UpdateCharacterEvent;
use App\Game\Core\Services\CharacterService;

class UpdateCharacterListener
{

    private $characterService;

    public function __construct(CharacterService $characterService) {
        $this->characterService = $characterService;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Game\Battle\UpdateCharacterEvent  $event
     * @return void
     */
    public function handle(UpdateCharacterEvent $event)
    {

        $characterRewardService = resolve(CharacterRewardService::class, [
            'character' => $event->character,
        ]);

        $characterRewardService->distributeGoldAndXp($event->monster, $event->adventure);

        $character = $characterRewardService->getCharacter();
        dump($character->xp, $event->character->xp_next);
        if ($character->xp >= $event->character->xp_next) {
            $this->characterService->levelUpCharacter($character);

            $character->refresh();

            event(new ServerMessageEvent($character->user, 'level_up'));
            event(new UpdateTopBarEvent($character));
            event(new UpdateCharacterAttackEvent($character));

        } else {
            event(new UpdateTopBarEvent($character));
        }
    }
}
