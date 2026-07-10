<?php

namespace App\Game\Tops\Services;

use App\Flare\Models\Character;
use App\Game\Tops\Services\Concerns\BuildsTopsResponses;
use Illuminate\Http\Request;

class KingdomTopsService
{
    use BuildsTopsResponses;

    public function __construct(private readonly TopsPeriodService $topsPeriodService) {}

    public function leaderboard(Request|array $request = []): array
    {
        $parameters = $request instanceof Request ? $request->query() : $request;
        $period = $this->topsPeriodService->resolve($parameters['period'] ?? null);
        $metric = $parameters['metric'] ?? 'kingdom_count';
        $metrics = [
            ['key' => 'kingdom_count', 'label' => 'Most Kingdoms'],
            ['key' => 'total_value', 'label' => 'Total Value'],
            ['key' => 'total_treasury', 'label' => 'Highest Treasury'],
            ['key' => 'total_gold_bars', 'label' => 'Most Gold Bars'],
            ['key' => 'total_current_population', 'label' => 'Highest Population'],
            ['key' => 'total_resources', 'label' => 'Most Resources'],
            ['key' => 'total_units', 'label' => 'Most Units'],
        ];
        $snapshot = $this->snapshotResponse('kingdoms', $metric, $period, $metrics);

        if (! is_null($snapshot)) {
            return $snapshot;
        }

        if ($period['is_archived_month']) {
            return $this->response('kingdoms', $metric, $period, [], $metrics, [], 'Historical kingdom state requires a monthly snapshot for this archived month.');
        }

        $rows = Character::whereHas('kingdoms', fn ($query) => $query->where('npc_owned', false))
            ->with(['kingdoms' => fn ($query) => $query->where('npc_owned', false)->with(['gameMap', 'units'])])
            ->get()
            ->map(function (Character $character) {
                $kingdoms = $character->kingdoms;
                $totalResources = $kingdoms->sum('current_stone') + $kingdoms->sum('current_wood') + $kingdoms->sum('current_clay') + $kingdoms->sum('current_iron') + $kingdoms->sum('current_steel');
                $totalUnits = $kingdoms->sum(fn ($kingdom) => $kingdom->units->sum('amount'));
                $totalValue = $kingdoms->sum('treasury') + $kingdoms->sum('gold_bars') + $kingdoms->sum('current_population') + $totalResources + $totalUnits;

                return [
                    'rank' => 0,
                    'character_id' => $character->id,
                    'character_name' => $character->name,
                    'character_profile_url' => $this->characterProfileUrl($character->id),
                    'total_value' => $totalValue,
                    'kingdom_count' => $kingdoms->count(),
                    'capital_count' => $kingdoms->where('is_capital', true)->count(),
                    'total_treasury' => $kingdoms->sum('treasury'),
                    'total_gold_bars' => $kingdoms->sum('gold_bars'),
                    'total_current_population' => $kingdoms->sum('current_population'),
                    'total_current_stone' => $kingdoms->sum('current_stone'),
                    'total_current_wood' => $kingdoms->sum('current_wood'),
                    'total_current_clay' => $kingdoms->sum('current_clay'),
                    'total_current_iron' => $kingdoms->sum('current_iron'),
                    'total_current_steel' => $kingdoms->sum('current_steel'),
                    'total_resources' => $totalResources,
                    'total_units' => $totalUnits,
                    'map_distribution' => $kingdoms->groupBy(fn ($kingdom) => $kingdom->gameMap?->name ?? 'Unknown')->map->count()->all(),
                ];
            });

        $rows = $this->applySearch($rows, $parameters['search'] ?? null)
            ->sortByDesc($metric)
            ->values()
            ->all();

        return $this->response('kingdoms', $metric, $period, $rows, $metrics, ['search' => $parameters['search'] ?? null]);
    }

    public function detail(Character $character): array
    {
        $character->load(['kingdoms' => fn ($query) => $query->where('npc_owned', false)->with(['gameMap', 'units'])]);
        $kingdoms = $character->kingdoms;

        return [
            'character' => ['id' => $character->id, 'name' => $character->name, 'profile_url' => $this->characterProfileUrl($character->id)],
            'kingdom_count' => $kingdoms->count(),
            'capital_count' => $kingdoms->where('is_capital', true)->count(),
            'total_treasury' => $kingdoms->sum('treasury'),
            'total_gold_bars' => $kingdoms->sum('gold_bars'),
            'resource_totals' => [
                'stone' => $kingdoms->sum('current_stone'),
                'wood' => $kingdoms->sum('current_wood'),
                'clay' => $kingdoms->sum('current_clay'),
                'iron' => $kingdoms->sum('current_iron'),
                'steel' => $kingdoms->sum('current_steel'),
            ],
            'population_total' => $kingdoms->sum('current_population'),
            'morale_average' => round((float) $kingdoms->avg('current_morale'), 2),
            'map_distribution' => $kingdoms->groupBy(fn ($kingdom) => $kingdom->gameMap?->name ?? 'Unknown')->map->count()->all(),
            'unit_totals' => $kingdoms->flatMap(fn ($kingdom) => $kingdom->units)->groupBy('game_unit_id')->map->sum('amount')->all(),
            'kingdoms' => $kingdoms->map(fn ($kingdom) => [
                'id' => $kingdom->id,
                'name' => $kingdom->name,
                'map' => $kingdom->gameMap?->name,
                'is_capital' => $kingdom->is_capital,
                'treasury' => $kingdom->treasury,
                'gold_bars' => $kingdom->gold_bars,
                'current_population' => $kingdom->current_population,
                'current_morale' => $kingdom->current_morale,
            ])->values()->all(),
        ];
    }
}
