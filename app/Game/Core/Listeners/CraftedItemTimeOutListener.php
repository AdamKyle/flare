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

        $timeOut = 10;

        if (!is_null($event->extraTime)) {
            switch($event->extraTime) {
                case 'double':
                    $timeOut = 20;
                    return;
                case 'tripple':
                    $timeOut = 30;
                    return;
            }
        }

        broadcast(new ShowCraftingTimeOutEvent($event->character->user, true, false, $timeOut));

        CraftTimeOutJob::dispatch($event->character)->delay(now()->addSeconds($timeOut));
    }
}
