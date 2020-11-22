<?php

namespace App\Game\Core\Listeners;

use App\Game\Core\Events\ShowCraftingTimeOutEvent;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Jobs\CraftTimeOutJob;

class CraftedItemTimeOutListener
{
    public function handle(CraftedItemTimeOutEvent $event)
    {
        $event->character->update([
            'can_craft'          => false,
            'can_craft_again_at' => now()->addSeconds(10),
        ]);

        broadcast(new ShowCraftingTimeOutEvent($event->character->user, true, false, 10));

        CraftTimeOutJob::dispatch($event->character)->delay(now()->addSeconds(10));
    }
}
