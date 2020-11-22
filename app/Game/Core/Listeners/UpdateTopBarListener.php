<?php

namespace App\Game\Core\Listeners;

use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Flare\Transformers\CharacterSheetTransformer;
use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Events\UpdateTopBarBroadcastEvent;

class UpdateTopBarListener
{

    /**
     * @var Manager $manager
     */
    private $manager;

    /**
     * @var CharacterSheetTransformer $characterSheetTransformer
     */
    private $characterSheetTransformer;

    /**
     * Constructor
     * 
     * @param Manager $manager
     * @param CharacterSheetTransformer $characterSheetTransformer
     * @return void
     */
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
