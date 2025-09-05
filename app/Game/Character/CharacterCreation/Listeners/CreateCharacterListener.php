<?php

namespace App\Game\Character\CharacterCreation\Listeners;

use App\Game\Character\CharacterCreation\Events\CreateCharacterEvent;
use App\Game\Character\CharacterCreation\Pipeline\CharacterCreationPipeline;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;

class CreateCharacterListener
{
    public function __construct(
        private readonly CharacterCreationPipeline $pipeline,
        private readonly CharacterBuildState $state
    ) {
    }

    public function handle(CreateCharacterEvent $event): void
    {
        $this->state
            ->setUser($event->user)
            ->setRace($event->race)
            ->setClass($event->class)
            ->setMap($event->map)
            ->setCharacterName($event->characterName)
            ->setNow(now());

        $this->pipeline->run($this->state);
    }
}
