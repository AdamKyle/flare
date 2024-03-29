<?php

namespace App\Game\Core\Listeners;

use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\CharacterTopBarTransformer;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Events\UpdateTopBarBroadcastEvent;

class UpdateTopBarListener {

    /**
     * @var Manager $manager
     */
    private Manager $manager;

    /**
     * @var CharacterTopBarTransformer $characterTopBarTransformer
     */
    private CharacterTopBarTransformer $characterTopBarTransformer;

    /**
     * @param Manager $manager
     * @param CharacterTopBarTransformer $characterTopBarTransformer
     * @param CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer
     */
    public function __construct(Manager $manager, CharacterTopBarTransformer $characterTopBarTransformer) {
        $this->manager                           = $manager;
        $this->characterTopBarTransformer        = $characterTopBarTransformer;
    }

    /**
     * Handle the event.
     *
     * @param UpdateTopBarEvent $event
     * @return void
     */
    public function handle(UpdateTopBarEvent $event): void {
        $character = new Item($event->character, $this->characterTopBarTransformer);
        $character = $this->manager->createData($character)->toArray();

        broadcast(new UpdateTopBarBroadcastEvent($character, $event->character->user));
    }
}
