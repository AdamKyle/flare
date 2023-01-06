<?php

namespace App\Admin\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\User;
use App\Flare\Values\SiteAccessStatisticValue;
use App\Http\Controllers\Controller;

class SiteAccessStatisticsController extends Controller {


    public function fetchLoggedInAllTime() {
        return response()->json(['stats' => SiteAccessStatisticValue::getSignedIn(),], 200);
    }

    public function fetchRegisteredAllTime() {
        return response()->json(['stats' => SiteAccessStatisticValue::getRegistered()], 200);
    }

    public function fetchCharactersGold() {
        $charactersWithHighGold = Character::where('gold', '>=', 1000000000)->get();

        return response()->json([
            'stats' => [
                'labels' => $charactersWithHighGold->pluck('name')->toArray(),
                'data'   => $charactersWithHighGold->pluck('gold')->toArray(),
            ]
        ]);
    }

    public function fetchReincarnationChart() {
        $charactersWithHighGold = Character::where('times_reincarnated', '>=', 1)->get();

        return response()->json([
            'stats' => [
                'labels' => $charactersWithHighGold->pluck('name')->toArray(),
                'data'   => $charactersWithHighGold->pluck('times_reincarnated')->toArray(),
            ]
        ]);
    }

    public function otherDetails() {
        return response()->json([
            'averageCharacterLevel'       => number_format(Character::avg('level'), 2),
            'averageCharacterGold'        => number_format(Character::avg('gold')),
            'characterKingdomCount'       => number_format(Kingdom::whereNotNull('character_id')->count()),
            'npcKingdomCount'             => number_format(Kingdom::whereNull('character_id')->count()),
            'richestCharacter'            => Character::orderBy('gold', 'desc')->select('name', 'gold')->first(),
            'highestLevelCharacter'       => Character::orderBy('gold', 'desc')->select('name', 'level')->first(),
            'kingdomHolders'              => $this->fetchKingdomHolders(),
            'lastLoggedInCount'           => User::whereDate('last_logged_in', now())->count(),
            'lastFiveMonthsLoggedInCount' => User::whereBetween('last_logged_in', [now()->subMonths(5), now()])->count(),
            'neverLoggedInCount'          => User::whereNull('last_logged_in')->count(),
            'totalCharactersRegistered'   => User::count(),
            'willBeDeletedCount'          => User::where('will_be_deleted', true)->count(),
        ]);
    }

    protected function fetchKingdomHolders(): array {
        $onlyCharactersWithKingdoms = Character::whereHas('kingdoms')->get();

        $array = [];

        foreach ($onlyCharactersWithKingdoms as $characterWithKingdom) {
            $array[$characterWithKingdom->name] = $characterWithKingdom->kingdoms_count;
        }

        arsort($array);

        return $array;
    }

    public function getTotalGoldIncludingKingdomsForCharacters() {
        $data = Character::select('characters.name as character_name',
             \DB::raw('characters.gold + SUM(kingdoms.treasury) + SUM(kingdoms.gold_bars) * 2000000000 as total_gold')
         )->leftJoin('kingdoms', 'kingdoms.character_id', '=', 'characters.id')
          ->where('kingdoms.id', '!=', null)
          ->having('total_gold', '>', 2000000000000)
          ->orderBy('total_gold', 'asc')
          ->groupBy('characters.id')
          ->get();

        return response()->json([
            'data' => $data->pluck('total_gold')->toArray(),
            'labels' => $data->pluck('character_name')->toArray()
        ]);
    }
}
