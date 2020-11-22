<?php

namespace App\Game\Core\Listeners;

use Illuminate\Database\Eloquent\Collection;
use App\Game\Core\Events\GoldRushCheckEvent;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use Facades\App\Flare\Calculators\GoldRushCheckCalculator;

class GoldRushCheckListener
{

    /**
     * Handle the event.
     *
     * @param  \App\Game\Battle\UpdateCharacterEvent  $event
     * @return void
     */
    public function handle(GoldRushCheckEvent $event)
    {
        $lootingChance  = $event->character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
        
        $hasGoldRush    = GoldRushCheckCalculator::fetchGoldRushChance($event->monster, $lootingChance, $event->adventure);

        if ($hasGoldRush) {
            $goldRush = rand(0, 1000) + 1000;

            $event->character->gold += $goldRush;
            $event->character->save();

            $character = $event->character->refresh();

            event(new ServerMessageEvent($character->user, 'gold_rush', $goldRush));
            event(new UpdateTopBarEvent($character));
        }
    }

    


}
