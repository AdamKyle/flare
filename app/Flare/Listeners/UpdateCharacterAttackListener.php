<?php

namespace App\Flare\Listeners;

use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Events\UpdateCharacterAttackBroadcastEvent;
use App\Flare\Values\MaxDamageForItemValue;

class UpdateCharacterAttackListener
{

    private $manager;

    private $characterAttackTransformer;

    public function __construct(Manager $manager, CharacterAttackTransformer $characterAttackTransformer) {
        $this->manager                    = $manager;
        $this->characterAttackTransformer = $characterAttackTransformer;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Flare\Events\UpdateCharacterSheetEvent $event
     * @return void
     */
    public function handle(UpdateCharacterAttackEvent $event)
    {

        $event->character->refresh();

        $attack = new Item($event->character, $this->characterAttackTransformer);
        $attack = $this->manager->createData($attack)->toArray();

        broadcast(new UpdateCharacterAttackBroadcastEvent($attack, $event->character->user));
    }
}
