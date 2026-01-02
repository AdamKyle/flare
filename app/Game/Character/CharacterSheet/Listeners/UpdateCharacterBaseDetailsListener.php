<?php

namespace App\Game\Character\CharacterSheet\Listeners;

use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsBroadcastEvent;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Character\CharacterSheet\Transformers\CharacterBaseDetailsTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class UpdateCharacterBaseDetailsListener
{
    private Manager $manager;

    private CharacterBaseDetailsTransformer $characterTopBarTransformer;

    /**
     * @param Manager $manager
     * @param CharacterBaseDetailsTransformer $characterTopBarTransformer
     */
    public function __construct(Manager $manager, CharacterBaseDetailsTransformer $characterTopBarTransformer)
    {
        $this->manager = $manager;
        $this->characterTopBarTransformer = $characterTopBarTransformer;
    }

    /**
     * Handle the event.
     */
    public function handle(UpdateCharacterBaseDetailsEvent $event): void
    {
        $character = new Item($event->character, $this->characterTopBarTransformer);
        $character = $this->manager->createData($character)->toArray();

        broadcast(new UpdateCharacterBaseDetailsBroadcastEvent($character, $event->character->user));
    }
}
