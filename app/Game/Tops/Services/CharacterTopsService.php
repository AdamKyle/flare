<?php

namespace App\Game\Tops\Services;

use App\Flare\Models\Character;
use App\Flare\Models\UserLoginDuration;
use App\Game\Tops\Services\Concerns\BuildsTopsResponses;
use Illuminate\Http\Request;

class CharacterTopsService
{
    use BuildsTopsResponses;

    public function __construct(private readonly TopsPeriodService $topsPeriodService) {}

    public function leaderboard(Request|array $request = []): array
    {
        $parameters = $request instanceof Request ? $request->query() : $request;
        $period = $this->topsPeriodService->resolve($parameters['period'] ?? null);
        $metric = 'progression';
        $metrics = [['key' => 'progression', 'label' => 'Progression']];

        $query = Character::with(['race', 'class', 'map.gameMap', 'user']);

        $rows = $query->get()->map(function (Character $character) {
            $lastActivity = UserLoginDuration::where('user_id', $character->user_id)
                ->orderByRaw('COALESCE(last_activity, logged_in_at) desc')
                ->first();

            return [
                'rank' => 0,
                'character_id' => $character->id,
                'character_name' => $character->name,
                'character_profile_url' => $this->characterProfileUrl($character->id),
                'level' => $character->level,
                'xp' => $character->xp,
                'times_reincarnated' => $character->times_reincarnated ?? 0,
                'race' => $character->race?->name,
                'class' => $character->class?->name,
                'map' => $character->map?->gameMap?->name,
                'gold' => $character->gold,
                'last_active_at' => $lastActivity?->last_activity?->toISOString() ?? $lastActivity?->logged_in_at?->toISOString(),
                'online' => $character->isLoggedIn(),
            ];
        });

        $rows = $this->applySearch($rows, $parameters['search'] ?? null);
        $rows = $rows->when(! empty($parameters['race']), fn ($rows) => $rows->where('race', $parameters['race']));
        $rows = $rows->when(! empty($parameters['class']), fn ($rows) => $rows->where('class', $parameters['class']));
        $rows = $rows->when(! empty($parameters['map']), fn ($rows) => $rows->where('map', $parameters['map']));
        $rows = $rows->when(($parameters['online_only'] ?? null) === '1', fn ($rows) => $rows->where('online', true));

        $rows = $rows->sortByDesc('gold')
            ->sortByDesc('xp')
            ->sortByDesc('level')
            ->sortByDesc('times_reincarnated')
            ->values()
            ->all();

        return $this->response('characters', $metric, $period, $rows, $metrics, [
            'search' => $parameters['search'] ?? null,
            'race' => $parameters['race'] ?? null,
            'class' => $parameters['class'] ?? null,
            'map' => $parameters['map'] ?? null,
            'online_only' => $parameters['online_only'] ?? null,
        ]);
    }
}
