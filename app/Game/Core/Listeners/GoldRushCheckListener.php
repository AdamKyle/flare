<?php

namespace App\Game\Core\Listeners;

use Illuminate\Database\Eloquent\Collection;
use App\Game\Core\Events\GoldRushCheckEvent;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Adventure;

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
        $adventureBonus = $this->getAdventureGoldrushChance($event->adventure);
        $hasGoldRush    = (rand(1, 100) * (1 + ($lootingChance + $adventureBonus))) > (100 - (100 * $event->monster->drop_check));

        if ($hasGoldRush) {
            $drops    = $event->monster->drops;
            $goldRush = rand(0, 1000) + 1000;

            $event->character->gold += $goldRush;
            $event->character->save();

            event(new ServerMessageEvent($event->character->user, 'gold_rush', $goldRush));
        }
    }

    protected function getAdventureGoldrushChance(Adventure $adventure = null): float {
        if (!is_null($adventure)) {
            return $adventure->gold_rush_chance;
        }

        return 0.0;
    }


}
