<?php

namespace App\Admin\Controllers;

use App\Flare\Models\User;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;

class StatisticsController extends Controller {

    public function index() {

        return view('admin.statistics.dashboard', [
            'averageCharacterLevel'       => number_format(Character::avg('level'), 2),
            'averageCharacterGold'        => number_format(Character::avg('gold')),
            'kingdomCount'                => number_format(Kingdom::count()),
            'richestCharacter'            => Character::orderBy('gold', 'desc')->first(),
            'highestLevelCharacter'       => Character::orderBy('gold', 'desc')->first(),
            'topTenKingdoms'              => $this->fetchTopKingdomHolders(),
            'lastLoggedInCount'           => User::whereDate('last_logged_in', now())->count(),
            'lastFiveMonthsLoggedInCount' => User::whereBetween('last_logged_in', [now()->subMonths(5), now()])->count(),
            'neverLoggedInCount'          => User::whereNull('last_logged_in')->count(),
            'totalLoggedInAllTime'        => User::whereNotNull('last_logged_in')->count(),
            'willBeDeletedCount'          => User::where('will_be_deleted', true)->count(),
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
