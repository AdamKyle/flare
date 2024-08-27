<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Requests\CompletedQuestsStatisticsRequest;
use App\Admin\Requests\SiteAccessStatisticsRequest;
use App\Admin\Services\SiteStatisticsService;
use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Quest;
use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use App\Flare\Values\SiteAccessStatisticValue;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiteAccessStatisticsController extends Controller
{

    public function __construct(private readonly SiteStatisticsService $siteStatisticsService) {
    }

    public function fetchLoggedInAllTime(SiteAccessStatisticsRequest $request)
    {
        return response()->json(['stats' => SiteAccessStatisticValue::getSignedIn($request->daysPast)], 200);
    }

    public function fetchRegisteredAllTime(SiteAccessStatisticsRequest $request)
    {
        return response()->json(['stats' => SiteAccessStatisticValue::getRegistered($request->daysPast)], 200);
    }

    public function fetchCompletedQuests(CompletedQuestsStatisticsRequest $request)
    {
        $type = $request->type ?? 'quest';
        $limit = $request->limit ?? 10;
        $filter = $request->filter ?? 'most';

        $this->siteStatisticsService->fetchCompletedQuestsStatistics($type, $filter, $limit);

        return response()->json([
            'stats' => [
                'labels' => $this->siteStatisticsService->labels(),
                'data' => $this->siteStatisticsService->data(),
            ],
        ]);
    }

    public function fetchReincarnationChart()
    {
        $charactersWithHighGold = Character::where('times_reincarnated', '>=', 1)->get();

        return response()->json([
            'stats' => [
                'labels' => $charactersWithHighGold->pluck('name')->toArray(),
                'data' => $charactersWithHighGold->pluck('times_reincarnated')->toArray(),
            ],
        ]);
    }

    public function otherDetails()
    {
        $averageRegularQuestsCompleted = Character::query()
            ->join('quests_completed', 'characters.id', '=', 'quests_completed.character_id')
            ->whereNotNull('quests_completed.quest_id')
            ->select('characters.id', DB::raw('COUNT(DISTINCT quests_completed.quest_id) as quest_count'))
            ->groupBy('characters.id')
            ->get()
            ->avg('quest_count');

        $averageGuideQuestsCompleted = Character::query()
            ->join('quests_completed', 'characters.id', '=', 'quests_completed.character_id')
            ->whereNotNull('quests_completed.guide_quest_id')
            ->select('characters.id', DB::raw('COUNT(DISTINCT quests_completed.guide_quest_id) as guide_quest_count'))
            ->groupBy('characters.id')
            ->get()
            ->avg('guide_quest_count');

        return response()->json([
            'averageCharacterLevel' => number_format(Character::avg('level')),
            'averageCharacterGold' => number_format(Character::avg('gold')),
            'averageRegularQuestsCompleted' => number_format($averageRegularQuestsCompleted),
            'averageGuideQuestsCompleted' => number_format($averageGuideQuestsCompleted),
            'characterKingdomCount' => number_format(Kingdom::whereNotNull('character_id')->count()),
            'npcKingdomCount' => number_format(Kingdom::whereNull('character_id')->count()),
            'richestCharacter' => Character::orderBy('gold', 'desc')->select('name', 'gold')->first(),
            'highestLevelCharacter' => Character::orderBy('gold', 'desc')->select('name', 'level')->first(),
            'kingdomHolders' => $this->fetchKingdomHolders(),
            'lastLoggedInCount' => User::whereDate('last_logged_in', now())->count(),
            'lastFiveMonthsLoggedInCount' => User::whereBetween('last_logged_in', [now()->subMonths(5), now()])->count(),
            'neverLoggedInCount' => User::whereNull('last_logged_in')->count(),
            'totalCharactersRegistered' => User::count(),
            'willBeDeletedCount' => User::where('will_be_deleted', true)->count(),
        ]);
    }

    protected function fetchKingdomHolders(): array
    {
        $onlyCharactersWithKingdoms = Character::whereHas('kingdoms')->get();

        $array = [];

        foreach ($onlyCharactersWithKingdoms as $characterWithKingdom) {
            $array[$characterWithKingdom->name] = $characterWithKingdom->kingdoms_count;
        }

        arsort($array);

        return $array;
    }

    public function getTotalGoldIncludingKingdomsForCharacters()
    {
        $data = Character::select(
            'characters.name as character_name',
            \DB::raw('characters.gold + SUM(kingdoms.treasury) + SUM(kingdoms.gold_bars) * 2000000000 as total_gold')
        )->leftJoin('kingdoms', 'kingdoms.character_id', '=', 'characters.id')
            ->where('kingdoms.id', '!=', null)
            ->having('total_gold', '>', 2000000000000)
            ->orderBy('total_gold', 'asc')
            ->groupBy('characters.id')
            ->get();

        return response()->json([
            'data' => $data->pluck('total_gold')->toArray(),
            'labels' => $data->pluck('character_name')->toArray(),
        ]);
    }

    public function getLoginDurationDetails(Request $request) {
        $filter = $request->daysPast ?? 0;

        $this->siteStatisticsService->getLogInDurationStatistics($filter);

        return response()->json([
            'stats' => [
                'labels' => $this->siteStatisticsService->labels(),
                'data' => $this->siteStatisticsService->data(),
            ],
        ]);
    }

    public function getUsersCurrentlyOnline() {
        $now = Carbon::now();

        $onlineLogins = UserLoginDuration::whereNull('duration_in_seconds')
            ->whereDate('logged_in_at', Carbon::today())
            ->get();

        $onlineCharacters = [];

        foreach ($onlineLogins as $login) {
            $lastActivity = $login->last_activity;
            $timeLoggedIn = $lastActivity->diffInSeconds($login->logged_in_at);
            $character = $login->user->character;

            if ($character) {
                $onlineCharacters[] = [
                    'name' => $character->name,
                    'duration' => $timeLoggedIn
                ];
            }
        }

        return [
            'characters_online' => $onlineCharacters,
        ];
    }

}
