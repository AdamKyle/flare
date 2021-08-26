<?php

namespace App\Flare\Traits\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory;
use App\Flare\Models\Monster;
use App\Flare\Models\Quest;

trait MonstersShowInformation {

    /**
     * Renders the monster show.
     *
     * @param Monster $monster
     * @return View|Factory
     */
    public function renderMonsterShow(Monster $monster, $viewName = 'admin.monsters.monster'): View|Factory {
        $quest = null;

        if (!is_null($monster->questItem)) {
            $quest = Quest::where('item_id', $monster->questItem->id)->first();
        }

        return view($viewName, [
            'monster' => $monster,
            'quest'   => $quest,
        ]);
    }
}
