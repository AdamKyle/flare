<?php

namespace App\Game\Core\Listeners;

use App\Flare\Models\Item;
use App\Flare\Events\UpdateCharacterSheetEvent;
use App\Flare\Events\UpdateCharacterInventoryEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Events\SellItemEvent;

class SellItemListener
{

    public function __construct() {}

    public function handle(SellItemEvent $event)
    {
        $item = $event->inventorySlot->item;

        $goldGained = ($item->cost - ($item->cost * 0.25)) +
            $this->costForAffixes($item) + $this->costForArtifact($item);


        $event->character->gold += $goldGained;
        $event->character->save();

        $event->inventorySlot->delete();

        $event->character->refresh();

        event(new UpdateTopBarEvent($event->character));
        event(new UpdateCharacterInventoryEvent($event->character));
        event(new UpdateCharacterSheetEvent($event->character));
    }

    protected function costForAffixes(Item $item): int {
        if ($item->itemAffixes->isEmpty()) {
            return 0;
        }

        if ($item->itemAffixes->count() === 2) {
            return 200;
        }

        return 100;
    }

    protected function costForArtifact(Item $item): int {
        if (is_null($item->artifactProperty)) {
            return 0;
        }

        return 500;
    }
}
