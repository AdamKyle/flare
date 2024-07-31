<?php

namespace App\Game\Character\CharacterAttack\Listeners;

use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Values\MaxDamageForItemValue;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackBroadcastEvent;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class UpdateCharacterAttackListener
{

    /**
     * @var Manager $manager
     */
    private $manager;

    /**
     * @var CharacterAttackTransformer $characterAttackTransformer
     */
    private CharacterAttackTransformer $characterAttackTransformer;

    /**
     * Constructor
     *
     * @param Manager $manager
     * @param CharacterAttackTransformer $characterAttackTransformer
     * @return void
     */
    public function __construct(Manager $manager, CharacterAttackTransformer $characterAttackTransformer) {
        $this->manager                    = $manager;
        $this->characterAttackTransformer = $characterAttackTransformer;
    }

    /**
     * @param UpdateCharacterAttackEvent $event
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
