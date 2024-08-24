<?php

namespace App\Game\Character\CharacterAttack\Listeners;

use App\Flare\Transformers\CharacterAttackTransformer;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackBroadcastEvent;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class UpdateCharacterAttackListener
{
    /**
     * @var Manager
     */
    private $manager;

    private CharacterAttackTransformer $characterAttackTransformer;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(Manager $manager, CharacterAttackTransformer $characterAttackTransformer)
    {
        $this->manager = $manager;
        $this->characterAttackTransformer = $characterAttackTransformer;
    }

    /**
     * @return void
     */
    public function handle(UpdateCharacterAttackEvent $event)
    {

        $event->character->refresh();

        $this->characterAttackTransformer->setIgnoreReductions($event->ignoreReductions);

        $attack = new Item($event->character, $this->characterAttackTransformer);
        $attack = $this->manager->createData($attack)->toArray();

        event(new UpdateCharacterAttackBroadcastEvent($attack, $event->character->user));
    }
}
