<?php

namespace App\Game\Core\Listeners;

use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\CharacterTopBarTransformer;
use App\Game\Core\Events\UpdateTopBarBroadcastEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class UpdateTopBarListener
{
    private Manager $manager;

    private CharacterTopBarTransformer $characterTopBarTransformer;

    /**
     * @param  CharacterSheetBaseInfoTransformer  $characterSheetBaseInfoTransformer
     */
    public function __construct(Manager $manager, CharacterTopBarTransformer $characterTopBarTransformer)
    {
        $this->manager = $manager;
        $this->characterTopBarTransformer = $characterTopBarTransformer;
    }

    /**
     * Handle the event.
     */
    public function handle(UpdateTopBarEvent $event): void
    {
        $character = new Item($event->character, $this->characterTopBarTransformer);
        $character = $this->manager->createData($character)->toArray();

        broadcast(new UpdateTopBarBroadcastEvent($character, $event->character->user));
    }
}
