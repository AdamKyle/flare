<?php

namespace App\Game\Core\Listeners;

use Illuminate\Database\Eloquent\Collection;
use App\Game\Core\Events\GoldRushCheckEvent;
use App\Flare\Events\ServerMessageEvent;
use Facades\App\Flare\Calculators\GoldRushCheckCalculator;

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
        $lootingChance  = $event->character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
        
        $hasGoldRush    = GoldRushCheckCalculator::fetchGoldRushChance($event->monster, $lootingChance, $event->adventure);

        if ($hasGoldRush) {
            $goldRush = rand(0, 1000) + 1000;

            $event->character->gold += $goldRush;
            $event->character->save();

            event(new ServerMessageEvent($event->character->user, 'gold_rush', $goldRush));
        }
    }

    


}
