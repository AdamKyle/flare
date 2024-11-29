<?php

namespace App\Game\Core\Listeners;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Transformers\CharacterInventoryCountTransformer;
use App\Game\Character\CharacterInventory\Events\CharacterInventoryCountUpdateBroadcaseEvent;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;

class UpdateCharacterInventoryCountListener
{
    private Manager $manager;

    private CharacterInventoryCountTransformer $characterInventoryCountTransformer;

    /**
     * @param  Manager $manager
     * @param  CharacterInventoryCountTransformer $characterInventoryCountTransformer
     */
    public function __construct(Manager $manager, CharacterInventoryCountTransformer $characterInventoryCountTransformer)
    {
        $this->manager = $manager;
        $this->characterInventoryCountTransformer = $characterInventoryCountTransformer;
    }

    /**
     * Handle the event.
     */
    public function handle(UpdateCharacterInventoryCountEvent $event): void
    {
        $characterInventoryCount = new Item($event->character, $this->characterInventoryCountTransformer);
        $characterInventoryCount = $this->manager->createData($characterInventoryCount)->toArray();

        broadcast(new CharacterInventoryCountUpdateBroadcaseEvent($characterInventoryCount, $event->character->user));
    }
}
