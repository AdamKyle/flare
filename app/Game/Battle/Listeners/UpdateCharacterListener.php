<?php

namespace App\Game\Battle\Listeners;

use App\Game\Battle\Events\UpdateCharacterEvent;
use App\Game\Battle\Services\CharacterService;

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
        $xp = $event->character->xp + $event->monster->xp;

        if ($xp >= $event->character->xp_next) {
            $this->characterService->levelUpCharacter($event->character);
        }

        // Implemenet drop mechanics

        // Implement Message for server - Action/Updates
    }
}
