<?php

namespace App\Game\Battle\Listeners;

use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Flare\Transformers\CharacterSheetTransformer;
use App\Game\Battle\Events\UpdateTopBarEvent;
use App\Game\Battle\Events\UpdateTopBarBroadcastEvent;

class UpdateTopBarListener
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
     * @param  \App\Game\Battle\UpdateCharacterEvent  $event
     * @return void
     */
    public function handle(UpdateTopBarEvent $event)
    {
        $character = new Item($event->character, $this->characterSheetTransformer);
        $character = $this->manager->createData($character)->toArray();

        broadcast(new UpdateTopBarBroadcastEvent($character, $event->character->user));
    }
}
