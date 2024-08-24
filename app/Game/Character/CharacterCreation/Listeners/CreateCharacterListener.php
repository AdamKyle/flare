<?php

namespace App\Game\Character\CharacterCreation\Listeners;

use App\Game\Character\CharacterCreation\Events\CreateCharacterEvent;
use App\Game\Character\CharacterCreation\Services\CharacterBuilderService;
use Exception;

class CreateCharacterListener
{
    private CharacterBuilderService $characterBuilder;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(CharacterBuilderService $characterBuilder)
    {
        $this->characterBuilder = $characterBuilder;
    }

    /**
     * Handle the event.
     *
     * @return void
     *
     * @throws Exception
     */
    public function handle(CreateCharacterEvent $event)
    {
        $this->characterBuilder->setRace($event->race)
            ->setClass($event->class)
            ->createCharacter($event->user, $event->map, $event->characterName)
            ->assignSkills()
            ->assignPassiveSkills()
            ->buildCharacterCache();
    }
}
