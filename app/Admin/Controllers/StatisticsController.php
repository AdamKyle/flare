<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;

class StatisticsController extends Controller {

    public function index() {

        return view('admin.statistics.dashboard', [
            'averageCharacterLevel'     => number_format(Character::avg('level'), 2),
            'averageCharacterGold'      => number_format(Character::avg('gold')),
            'kingdomCount'              => number_format(Kingdom::count()),
            'richestCharacter'          => Character::orderBy('gold', 'desc')->first(),
            'highestLevelCharacter'     => Character::orderBy('gold', 'desc')->first(),
            'topTenKingdoms'            => $this->fetchTopKingdomHolders()
        ]);
    }

    protected function fetchTopKingdomHolders(): array {
        $onlyCharactersWithKingdoms = Character::whereHas('kingdoms')->get();

        $array = [];

        foreach ($onlyCharactersWithKingdoms as $characterWithKingdom) {
            $array[$characterWithKingdom->name] = $characterWithKingdom->kingdoms_count;
        }

        arsort($array);

        return array_slice($array, 0, 10);
    }
}
