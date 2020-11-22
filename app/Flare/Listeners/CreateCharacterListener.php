<?php

namespace App\Flare\Listeners;

use App\Flare\Events\CreateCharacterEvent;
use App\Flare\Builders\CharacterBuilder;

class CreateCharacterListener
{

    /**
     * @var CharacterBuilder $characterBuilder
     */
    private $characterBuilder;

    /**
     * Constructor
     * 
     * @param CharacterBuilder $characterBuilder
     * @return void
     */
    public function __construct(CharacterBuilder $characterBuilder) {
        $this->characterBuilder = $characterBuilder;
    }

    /**
     * Handle the event.
     *
     * @param  CreateCharacterEvent $event
     * @return void
     */
    public function handle(CreateCharacterEvent $event)
    {
        $this->characterBuilder->setRace($event->race)
                               ->setClass($event->class)
                               ->createCharacter($event->user, $event->map, $event->characterName)
                               ->assignSkills();
    }
}
