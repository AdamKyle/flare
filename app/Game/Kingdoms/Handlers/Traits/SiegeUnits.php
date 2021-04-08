<?php

namespace App\Game\Kingdoms\Handlers\Traits;

use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Kingdom;

trait SiegeUnits {

    /**
     * Get the defenders siege units.
     *
     * @param Kingdom $defender
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDefenderSiegeUnits(Kingdom $defender): Collection {
        return $defender->units()->join('game_units', function($join) {
            $join->on('game_units.id', 'kingdom_units.game_unit_id')
                ->where('siege_weapon', true)
                ->where('defender', true);
        })->get();
    }

}
