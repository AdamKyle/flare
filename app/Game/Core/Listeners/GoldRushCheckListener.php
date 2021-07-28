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
        $gameMap        = $event->character->map->gameMap;
        $gameMapBonus   = 0.0;

        if (!is_null($gameMap->drop_chance_bonus)) {
            $gameMapBonus = $gameMap->drop_chance_bonus;
        }

        $hasGoldRush    = GoldRushCheckCalculator::fetchGoldRushChance($event->monster, $lootingChance, $gameMapBonus, $event->adventure);

        if ($hasGoldRush) {
            $goldRush = rand(0, 10000) + 10000;

            $event->character->gold += $goldRush;
            $event->character->save();

            $character = $event->character->refresh();

            event(new ServerMessageEvent($character->user, 'gold_rush', $goldRush));
            event(new UpdateTopBarEvent($character));
        }
    }




}
