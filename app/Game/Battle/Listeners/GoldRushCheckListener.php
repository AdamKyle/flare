<?php

namespace App\Game\Battle\Listeners;

use Illuminate\Database\Eloquent\Collection;
use App\Game\Battle\Events\GoldRushCheckEvent;
use App\Flare\Events\ServerMessageEvent;

class GoldRushCheckListener
{

    public function __construct() {}

    /**
     * Handle the event.
     *
     * @param  \App\Game\Battle\UpdateCharacterEvent  $event
     * @return void
     */
    public function handle(GoldRushCheckEvent $event)
    {
        $lootingChance = $event->character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
        $hasGoldRush   = (rand(1, 100) + $lootingChance) > ($event->monster->drop_check * 10);

        if ($hasGoldRush) {
            $drops    = $event->monster->drops;
            $goldRush = rand(0, 1000) + 1000;

            $event->character->gold += $goldRush;
            $event->character->save();

            event(new ServerMessageEvent($event->character->user, 'gold_rush', $goldRush));
        }
    }


}
