<?php

namespace App\Flare\Listeners;

use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Flare\Transformers\CharacterSheetTransformer;
use App\Flare\Events\UpdateCharacterSheetEvent;
use App\Flare\Events\UpdateCharacterSheetBroadcastEvent;

class UpdateCharacterSheetListener
{

    private $manager;

    private $characterSheetTransformer;

    public function __construct(Manager $manager, CharacterSheetTransformer $characterSheetTransformer) {
        $this->manager                   = $manager;
        $this->characterSheetTransformer = $characterSheetTransformer;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Flare\Events\UpdateCharacterSheetEvent $event
     * @return void
     */
    public function handle(UpdateCharacterSheetEvent $event)
    {
        $character = new Item($event->character, $this->characterSheetTransformer);
        $character = $this->manager->createData($character)->toArray();

        broadcast(new UpdateCharacterSheetBroadcastEvent($character, $event->character->user));
    }
}
