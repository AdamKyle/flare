<?php

namespace App\Game\Core\Services;

use App\Flare\Models\UserLoginDuration;
use App\Game\Core\Traits\ResponseBuilder;
use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;


class CharactersOnline {

    use ResponseBuilder;

    private int $filterType = 0;

    public function setFilterType(int $filterType):CharactersOnline {
        $this->filterType = $filterType;

        return $this;
    }

    public function getCharacterOnlineData(): array {

        $onlineLogins = $this->buildBaseQuery();
        $onlineLogins = $this->applyFilterToQuery($onlineLogins);

        $onlineCharacter = $this->formatCharacterData($onlineLogins);

        return $this->successResult([
            'characters_online' => $onlineCharacter,
        ]);
    }

    private function buildBaseQuery(): EloquentBuilder {
        if ($this->filterType > 0) {
            $onlineLogins = UserLoginDuration::where('duration_in_seconds', '>', 0);

            $onlineLogins = $onlineLogins->selectRaw('user_id, SUM(duration_in_seconds) as total_duration')
                                         ->groupBy('user_id');
        } else {
            $onlineLogins = UserLoginDuration::whereNull('duration_in_seconds');
        }

        return $onlineLogins;
    }

    private function applyFilterToQuery(Builder $onlineLogins): Collection {
        $onlineLogins = match ($this->filterType) {
            0 => $onlineLogins->whereDate('logged_in_at', Carbon::today()),
            7 => $onlineLogins->whereBetween('logged_in_at', [Carbon::now()->subDays(7), Carbon::now()]),
            14 => $onlineLogins->whereBetween('logged_in_at', [Carbon::now()->subDays(14), Carbon::now()]),
            31 => $onlineLogins->whereBetween('logged_in_at', [Carbon::now()->subDays(31), Carbon::now()]),
            default => $onlineLogins->whereDate('logged_in_at', Carbon::today()),
        };

        return $onlineLogins->get();
    }

    private function formatCharacterData(Collection $onlineLogins): array {
        $onlineCharacters = [];

        foreach ($onlineLogins as $login) {
            $timeLoggedIn = $this->filterType > 0 ? $login->total_duration : 0;

            if (!$this->filterType > 0) {
                $lastActivity = $login->last_activity;
                $lastHeartbeat = $login->last_heart_beat;
                $timeLoggedIn = $lastActivity->gt($lastHeartbeat) ? $lastActivity->diffInSeconds($login->logged_in_at) : $lastHeartbeat->diffInSeconds($login->logged_in_at);
            }

            $character = $login->user->character;

            if ($character) {
                $onlineCharacters[] = [
                    'name' => $character->name,
                    'duration' => $timeLoggedIn,
                    'currently_exploring' => $this->filterType > 0 ? false : $character->is_auto_battling,
                ];
            }
        }

        return $onlineCharacters;
    }

}
