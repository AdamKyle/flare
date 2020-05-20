<?php

namespace App\Game\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;

class CharacterSheetController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function index() {
        $character     = auth()->user()->character;
        $characterInfo = resolve(CharacterInformationBuilder::class)->setCharacter($character);

        return view ('game.core.character.sheet', [
            'character' => $character,
            'characterInfo' => [
                'maxAttack' => $characterInfo->buildAttack(),
                'maxHealth' => $characterInfo->buildHealth(),
            ],
        ]);
    }
}
