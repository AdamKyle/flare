<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Character;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GameTopsController extends Controller
{
    public function tops(): View
    {
        return view('game.tops.characters');
    }

    public function characterStats(Character $character): View
    {
        return view('game.tops.character-info', [
            'character' => $character,
        ]);
    }

    public function oldCharacterStats(Character $character): RedirectResponse
    {
        return redirect()->route('game.tops.character.profile', ['character' => $character]);
    }

    public function exploration(): View
    {
        return view('game.tops.exploration');
    }

    public function delve(): View
    {
        return view('game.tops.delve');
    }

    public function factionLoyalty(): View
    {
        return view('game.tops.faction-loyalty');
    }

    public function kingdoms(): View
    {
        return view('game.tops.kingdoms');
    }
}
