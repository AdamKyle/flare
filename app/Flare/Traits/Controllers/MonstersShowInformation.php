<?php

namespace App\Flare\Traits\Controllers;

use App\Flare\Models\Monster;
use App\Flare\Models\Quest;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

trait MonstersShowInformation
{
    use ItemsShowInformation;

    /**
     * Renders the monster show.
     */
    public function renderMonsterShow(Monster $monster, $viewName = 'admin.monsters.monster'): View|Factory
    {
        $quest = null;
        $questItem = null;

        if (! is_null($monster->questItem)) {
            $quest = Quest::where('item_id', $monster->questItem->id)->first();
            $questItem = $this->itemShowDetails($monster->questItem);
        }

        return view($viewName, [
            'monster' => $monster,
            'quest' => $quest,
            'questItem' => $questItem,
        ]);
    }
}
