<?php

namespace App\Game\Character\CharacterCreation\Listeners;

use App\Game\Character\CharacterCreation\Events\CreateCharacterEvent;
use App\Game\Character\CharacterCreation\Services\CharacterBuilderService;
use Exception;

class CreateCharacterListener {

    /**
     * @var CharacterBuilderService $characterBuilder
     */
    private CharacterBuilderService $characterBuilder;

    /**
     * Constructor
     *
     * @param CharacterBuilderService $characterBuilder
     * @return void
     */
    public function __construct(CharacterBuilderService $characterBuilder) {
        $this->characterBuilder = $characterBuilder;
    }

    /**
     * Handle the event.
     *
     * @param CreateCharacterEvent $event
     * @return void
     * @throws Exception
     */
    public function handle(CreateCharacterEvent $event) {
        $this->characterBuilder->setRace($event->race)
                               ->setClass($event->class)
                               ->createCharacter($event->user, $event->map, $event->characterName)
                               ->assignSkills()
                               ->assignPassiveSkills()
                               ->buildCharacterCache();
    }
}
