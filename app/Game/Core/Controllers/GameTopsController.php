<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Character;
use App\Game\Character\CharacterSheet\Transformers\CharacterStatDetailsTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GameTopsController extends Controller
{
    public function tops(): View
    {
        return view('game.tops.characters');
    }

    public function characterStats(Character $character, CharacterStatDetailsTransformer $characterStatDetailsTransformer): View
    {
        $stats = $characterStatDetailsTransformer->transform($character);

        return view('game.tops.character-info', [
            'character' => $character,
            'attackData' => [
                'attack' => [
                    'weapon_damage' => $stats['weapon_attack'],
                    'ring_damage' => $stats['ring_damage'],
                ],
                'cast' => [
                    'spell_damage' => $stats['spell_damage'],
                    'heal_for' => $stats['healing_amount'],
                ],
            ],
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
