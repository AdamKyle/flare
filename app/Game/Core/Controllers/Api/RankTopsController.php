<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\RankFight;
use App\Flare\Models\RankFightTop;
use App\Game\Core\Requests\GetRankTopRequest;
use App\Http\Controllers\Controller;

class RankTopsController extends Controller {

    public function loadRankTops() {

        $ranks = RankFightTop::orderBy('rank_achievement_date', 'asc')->take(50)->get()->sortByDesc('current_rank');

        return response()->json([
            'character_tops_chart' => [
                'labels' => $ranks->pluck('character.name'),
                'data'   => $ranks->pluck('current_rank'),
            ],
            'current_rank' => RankFight::first()->current_rank
        ]);
    }

    public function loadSpecificTop(GetRankTopRequest $request) {
        $ranks = RankFightTop::where('current_rank', $request->rank)->take(100)->get()->transform(function($rank) {
            $rank->date = $rank->rank_achievement_date->setTimezone(env('TIME_ZONE'))->format('Y-m-d H:i:s');

            return $rank;
        })->sortBy('date');

        $rankData = [];

        foreach ($ranks as $rank) {
            $rankData[] = [
                'character_name' => $rank->character->name,
                'character_id'   => $rank->character->id,
                'date'           => $rank->date,
            ];
        }

        return response()->json([
            'rank_data' => $rankData
        ]);
    }
}
