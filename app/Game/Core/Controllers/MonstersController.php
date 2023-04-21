<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use App\Flare\Traits\Controllers\MonstersShowInformation;
use App\Http\Controllers\Controller;

class MonstersController extends Controller {

    use MonstersShowInformation;

    public function show(Monster $monster) {
        return $this->renderMonsterShow($monster);
    }
}
