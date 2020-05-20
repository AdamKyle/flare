<?php

namespace App\Flare\Listeners;

use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Flare\Transformers\CharacterInventoryTransformer;
use App\Flare\Events\UpdateCharacterInventoryEvent;
use App\Flare\Events\UpdateCharacterInventoryBroadcastEvent;
use App\Flare\Values\MaxDamageForItemValue;

class UpdateCharacterInventoryListener
{

    private $manager;

    private $characterInventoryTransformer;

    public function __construct(Manager $manager, CharacterInventoryTransformer $characterInventoryTransformer) {
        $this->manager                       = $manager;
        $this->characterInventoryTransformer = $characterInventoryTransformer;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Flare\Events\UpdateCharacterSheetEvent $event
     * @return void
     */
    public function handle(UpdateCharacterInventoryEvent $event)
    {

        $event->character->refresh();

        $inventory = [
            'inventory' => $characterInventory,
            'equipment' => $event->character->inventory->slots
                                            ->load(['item', 'item.itemAffixes', 'item.artifactProperty'])
                                            ->transform(function($equippedItem) {
                                                $equippedItem->actions          = null;
                                                $equippedItem->item->max_damage = resolve(MaxDamageForItemValue::class)
                                                                                    ->fetchMaxDamage($equippedItem->item);

                                                return $equippedItem;
                                            }),
            'quest_items' => $event->character->inventory->questItemSlots->load(['item', 'item.itemAffixes', 'item.artifactProperty']),
        ];
    }
}
