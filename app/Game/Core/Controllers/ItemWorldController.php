<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Character;
use App\Http\Controllers\Controller;


class ItemWorldController extends Controller {

    public function show(Character $character) {
        return view ('game.item-world.item-world', [
            'character' => $character
        ]);
    }
}
