<?php

namespace App\Game\Core\Services;

use App\Flare\Models\UserLoginDuration;
use App\Game\Core\Traits\ResponseBuilder;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CharactersOnline
{
    use ResponseBuilder;

    private int $filterType = 0;

    public function setFilterType(int $filterType): CharactersOnline
    {
        $this->filterType = $filterType;

        return $this;
    }

    public function getCharacterOnlineData(): array
    {

        $onlineLogins = $this->buildBaseQuery();
        $onlineLogins = $this->applyFilterToQuery($onlineLogins);

        $onlineCharacter = $this->formatCharacterData($onlineLogins);

        return $this->successResult([
            'characters_online' => $onlineCharacter,
        ]);
    }

    public function getPublicCharacterOnlineData(): array
    {
        $result = $this->getCharacterOnlineData();

        $result['characters_online'] = collect($result['characters_online'])->map(function (array $character): array {
            return [
                'name' => $character['name'],
                'level' => $character['level'],
                'map' => $character['map'],
                'duration' => $character['duration'],
                'currently_exploring' => $character['currently_exploring'],
                'last_activity' => $character['last_activity'],
                'last_heart_beat' => $character['last_heart_beat'],
            ];
        })->all();

        return $result;
    }

    private function buildBaseQuery(): EloquentBuilder
    {
        if ($this->filterType > 0) {
            $onlineLogins = UserLoginDuration::where('duration_in_seconds', '>', 0);

            $onlineLogins = $onlineLogins->selectRaw('user_id, SUM(duration_in_seconds) as total_duration')
                ->groupBy('user_id');
        } else {
            $onlineLogins = UserLoginDuration::with(['user.character.map.gameMap'])
                ->whereNull('duration_in_seconds')
                ->whereNull('logged_out_at')
                ->whereNotNull('last_heart_beat')
                ->where('last_heart_beat', '>=', Carbon::now()->subMinutes(30));
        }

        return $onlineLogins;
    }

    private function applyFilterToQuery(Builder $onlineLogins): Collection
    {
        $onlineLogins = match ($this->filterType) {
            0 => $onlineLogins,
            7 => $onlineLogins->whereBetween('logged_in_at', [Carbon::now()->subDays(7), Carbon::now()]),
            14 => $onlineLogins->whereBetween('logged_in_at', [Carbon::now()->subDays(14), Carbon::now()]),
            31 => $onlineLogins->whereBetween('logged_in_at', [Carbon::now()->subDays(31), Carbon::now()]),
            default => $onlineLogins,
        };

        return $onlineLogins->get();
    }

    private function formatCharacterData(Collection $onlineLogins): array
    {
        $onlineCharacters = [];

        foreach ($onlineLogins as $login) {
            if (is_null($login->user)) {
                continue;
            }

            $character = $login->user->character;

            if ($character) {
                $onlineCharacters[] = [
                    'name' => $character->name,
                    'level' => (int) $character->level,
                    'map' => $character->map?->gameMap?->name,
                    'duration' => $this->durationForLogin($login),
                    'currently_exploring' => $this->filterType === 0 ? (bool) $character->is_auto_battling : false,
                    'last_activity' => $login->last_activity?->toIso8601String(),
                    'last_heart_beat' => $login->last_heart_beat?->toIso8601String(),
                ];
            }
        }

        return $onlineCharacters;
    }

    private function durationForLogin(UserLoginDuration $login): int
    {
        if ($this->filterType > 0) {
            return (int) $login->total_duration;
        }

        if (is_null($login->logged_in_at)) {
            return 0;
        }

        return (int) $login->logged_in_at->diffInSeconds(now());
    }
}
