<?php

namespace App\Game\Core\Listeners;

use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\CharacterTopBarTransformer;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
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
     * @var CharacterTopBarTransformer $characterTopBarTransformer
     */
    private $characterTopBarTransformer;

    private $characterSheetBaseInfoTransformer;

    /**
     * @param Manager $manager
     * @param CharacterTopBarTransformer $characterTopBarTransformer
     */
    public function __construct(Manager $manager, CharacterTopBarTransformer $characterTopBarTransformer, CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer) {
        $this->manager                           = $manager;
        $this->characterTopBarTransformer        = $characterTopBarTransformer;
        $this->characterSheetBaseInfoTransformer = $characterSheetBaseInfoTransformer;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Game\Battle\UpdateCharacterEvent  $event
     * @return void
     */
    public function handle(UpdateTopBarEvent $event)
    {
        $character = new Item($event->character, $this->characterTopBarTransformer);
        $character = $this->manager->createData($character)->toArray();

        broadcast(new UpdateTopBarBroadcastEvent($character, $event->character->user));
    }
}
