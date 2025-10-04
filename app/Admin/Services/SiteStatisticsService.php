<?php

namespace App\Admin\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\Quest;
use App\Flare\Models\UserLoginDuration;
use Carbon\Carbon;
use DB;

class SiteStatisticsService
{
    private array $data = [];

    private array $labels = [];

    public function data(): array
    {
        return $this->data;
    }

    public function labels(): array
    {
        return $this->labels;
    }

    public function fetchCompletedQuestsStatistics(string $type, string $filter, int $limit): void
    {
        $totalCount = $type === 'guide_quest' ? GuideQuest::count() : Quest::count();

        $query = Character::query()
            ->whereHas('questsCompleted', function ($query) use ($type) {
                $query->whereNotNull($type.'_id');
            })
            ->withCount(['questsCompleted as quests_count' => function ($query) use ($type) {
                $query->whereNotNull($type.'_id');
            }]);

        if ($filter === 'most') {
            $threshold = $totalCount / 2;
            $query->having('quests_count', '>=', $threshold);
        } elseif ($filter === 'some') {
            $threshold = $type === 'guide_quest' ? 5 : 25;
            $query->having('quests_count', '>', $threshold);
        } elseif ($filter === 'least') {
            $query->having('quests_count', '<', 5);
        } else {
            $query->orderByDesc('quests_count');
        }

        $charactersWithQuests = $query->take($limit)->get();

        $this->data = $charactersWithQuests->pluck('quests_count')->toArray();
        $this->labels = $charactersWithQuests->pluck('name')->toArray();
    }

    public function getLogInDurationStatistics(int $filter): void
    {
        switch ($filter) {
            case '0':
                $this->getTodayLoginDurationStats();
                break;
            case '7':
            case '14':
            case '31':
                $this->getRange($filter);
                break;
            default:
                throw new \Exception('Unknown filter for login duration.');
        }
    }

    private function getTodayLoginDurationStats(): void
    {
        $today = Carbon::today();
        $this->getDurationsForDay($today);
    }

    private function getDurationsForDay(Carbon $day): void
    {
        $durations = UserLoginDuration::whereDate('logged_in_at', $day)
            ->whereNotNull('duration_in_seconds')
            ->where('duration_in_seconds', '>', 0)
            ->get();

        $hoursOfDay = [];
        $totalDurationsInHours = array_fill(0, 24, 0);

        for ($i = 0; $i < 24; $i++) {
            $hoursOfDay[] = Carbon::createFromTime($i, 0)->format('ga');
        }

        foreach ($durations as $duration) {
            $loggedInHour = $duration->logged_in_at->hour;
            $loggedOutHour = $duration->logged_out_at ? $duration->logged_out_at->hour : null;

            if ($loggedOutHour !== null) {
                for ($i = $loggedInHour; $i <= $loggedOutHour; $i++) {
                    $totalDurationsInHours[$i] += $duration->duration_in_seconds / 60;
                }
            } else {
                $totalDurationsInHours[$loggedInHour] += $duration->duration_in_seconds / 60;
            }
        }

        $this->labels = $hoursOfDay;
        $this->data = array_values($totalDurationsInHours);
    }

    private function getRange(int $days): void
    {
        $endDate = Carbon::today();

        if ($days >= 30) {
            $startDate = $endDate->copy()->subMonth();
        } else {
            $startDate = $endDate->copy()->subDays($days);
        }

        $durations = UserLoginDuration::whereBetween('logged_in_at', [$startDate, $endDate])
            ->whereNotNull('duration_in_seconds')
            ->where('duration_in_seconds', '>', 0)
            ->select(
                'user_id',
                DB::raw('AVG(duration_in_seconds) as average_duration'),
                DB::raw('MAX(logged_in_at) as first_login_at') // or MAX() for latest
            )
            ->groupBy('user_id')
            ->get();

        $dailyDurations = [];
        $daysLabels = [];

        for ($i = 0; $i <= $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $daysLabels[] = $date;
            $dailyDurations[$date] = 0;
        }

        foreach ($durations as $duration) {
            $dateKey = Carbon::parse($duration->first_login_at)->format('Y-m-d');
            if (isset($dailyDurations[$dateKey])) {
                $dailyDurations[$dateKey] += $duration->average_duration / 60;
            }
        }

        $this->labels = $daysLabels;
        $this->data = array_values($dailyDurations);
    }
}
