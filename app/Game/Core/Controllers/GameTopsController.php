<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Character;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class GameTopsController extends Controller {

    public function tops() {
        return view('game.tops.characters');
    }

    public function characterStats(Character $character) {
        return view('game.tops.character-info', [
            'character'  => $character,
            'attackData' => Cache::get('character-attack-data-' . $character->id)['attack_types'],
        ]);
    }

    public function rankedFightsTops() {
        return view('game.tops.ranked-fights-tops');
    }
}
