<?php

namespace App\Game\Core\Controllers;

use App\Game\Battle\Values\MaxLevel;
use App\Game\Core\Events\GlobalTimeOut;
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

        return view ('game.character.sheet', [
            'character' => $character,
            'maxLevel'  => number_format(MaxLevel::MAX_LEVEL),
            'characterInfo' => [
                'maxAttack' => number_format($characterInfo->buildAttack()),
                'maxHealth' => number_format($characterInfo->buildHealth()),
                'maxHeal'   => number_format($characterInfo->buildHealFor()),
                'maxAC'     => number_format($characterInfo->buildDefence()),
                'str'       => number_format($characterInfo->statMod('str')),
                'dur'       => number_format($characterInfo->statMod('dur')),
                'dex'       => number_format($characterInfo->statMod('dex')),
                'chr'       => number_format($characterInfo->statMod('chr')),
                'int'       => number_format($characterInfo->statMod('int')),
                'agi'       => number_format($characterInfo->statMod('agi')),
                'focus'     => number_format($characterInfo->statMod('focus')),
            ],
        ]);
    }
}
