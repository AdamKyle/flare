<?php

namespace App\Admin\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\InactiveUserDeletionStatistic;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Quest;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use App\Flare\Models\UserSiteAccessStatistics;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminStatisticsDashboardService
{
    public function snapshot(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'summary' => $this->summary(),
            'login_chart' => $this->siteAccessChart('amount_signed_in'),
            'registration_chart' => $this->registrationChart(),
            'login_duration_chart' => $this->loginDurationChart(),
            'today_login_count_chart' => $this->todayLoginCountChart(),
            'login_participation_summary' => $this->loginParticipationSummary(),
            'login_participation_chart' => $this->loginParticipationChart(),
            'inactive_user_deletion_summary' => $this->inactiveUserDeletionSummary(),
            'online_characters' => $this->onlineCharacters(),
            'reincarnation_chart' => $this->reincarnationChart(),
            'quest_completion_chart' => $this->questCompletionChart(),
            'guide_quest_completion_chart' => $this->guideQuestCompletionChart(),
            'gold_chart' => $this->goldChart(),
            'kingdom_summary' => $this->kingdomSummary(),
            'top_kingdom_holders' => $this->topKingdomHolders(),
            'metric_definitions' => $this->metricDefinitions(),
        ];
    }

    private function summary(): array
    {
        $richestCharacter = Character::orderByDesc('gold')->first();
        $highestLevelCharacter = Character::orderByDesc('level')->first();

        return [
            'total_registered_users' => User::count(),
            'total_characters' => Character::count(),
            'average_character_level' => round((float) Character::avg('level'), 2),
            'average_character_gold' => round((float) Character::avg('gold'), 2),
            'richest_character' => $richestCharacter ? [
                'id' => $richestCharacter->id,
                'name' => $richestCharacter->name,
                'gold' => (int) $richestCharacter->gold,
            ] : null,
            'highest_level_character' => $highestLevelCharacter ? [
                'id' => $highestLevelCharacter->id,
                'name' => $highestLevelCharacter->name,
                'level' => (int) $highestLevelCharacter->level,
            ] : null,
            'completed_quests' => QuestsCompleted::whereNotNull('quest_id')->count(),
            'completed_guide_quests' => QuestsCompleted::whereNotNull('guide_quest_id')->count(),
        ];
    }

    private function registrationChart(): array
    {
        $chart = [
            'source' => 'users',
            'unit' => 'registrations',
            'points' => User::query()
                ->selectRaw('DATE(created_at) as label, COUNT(*) as value')
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('label')
                ->limit(31)
                ->get()
                ->map(fn (User $user) => [
                    'label' => (string) $user->label,
                    'value' => (float) $user->value,
                ])
                ->toArray(),
        ];

        return $this->withSeries($chart, 'Registrations');
    }

    private function siteAccessChart(string $attribute): array
    {
        $points = UserSiteAccessStatistics::query()
            ->whereNotNull($attribute)
            ->selectRaw('DATE(created_at) as label, MAX(' . $attribute . ') as value')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('label')
            ->limit(31)
            ->get()
            ->map(fn (UserSiteAccessStatistics $statistic) => [
                'label' => (string) $statistic->label,
                'value' => (float) $statistic->value,
            ])
            ->toArray();

        if ($attribute !== 'amount_signed_in') {
            return $this->withSeries([
                'source' => 'user_site_access_statistics',
                'unit' => 'snapshot count',
                'points' => $points,
            ], 'Login Activity');
        }

        return [
            'source' => 'user_site_access_statistics and inactive_user_deletion_statistics',
            'unit' => 'daily count',
            'points' => $points,
            'series' => [
                [
                    'label' => 'Logins',
                    'points' => $points,
                ],
                [
                    'label' => 'Inactive users deleted',
                    'points' => $this->inactiveUserDeletionPoints(),
                ],
            ],
        ];
    }

    private function loginDurationChart(): array
    {
        $chart = [
            'source' => 'user_login_durations',
            'unit' => 'average minutes',
            'points' => UserLoginDuration::query()
                ->whereNotNull('duration_in_seconds')
                ->where('duration_in_seconds', '>', 0)
                ->selectRaw('DATE(logged_in_at) as label, AVG(duration_in_seconds) / 60 as value')
                ->groupBy(DB::raw('DATE(logged_in_at)'))
                ->orderBy('label')
                ->limit(31)
                ->get()
                ->map(fn (UserLoginDuration $duration) => [
                    'label' => (string) $duration->label,
                    'value' => round((float) $duration->value, 2),
                ])
                ->toArray(),
        ];

        return $this->withSeries($chart, 'Average Login Duration');
    }

    private function loginParticipationSummary(): array
    {
        $totalUsers = $this->totalNonAdminUsers();

        return collect($this->loginParticipationWindows())
            ->map(function (array $window) use ($totalUsers) {
                $distinctLoginUsers = $this->distinctNonAdminLoginUsersSince($window['start']);

                return [
                    'label' => $window['label'],
                    'window' => $window['window'],
                    'distinct_login_users' => $distinctLoginUsers,
                    'total_users' => $totalUsers,
                    'percentage' => $totalUsers > 0 ? round(($distinctLoginUsers / $totalUsers) * 100, 2) : 0.0,
                ];
            })
            ->toArray();
    }

    private function loginParticipationChart(): array
    {
        $points = collect($this->loginParticipationSummary())
            ->map(fn (array $summary) => [
                'label' => $summary['label'],
                'value' => (float) $summary['percentage'],
            ])
            ->toArray();

        return $this->withSeries([
            'source' => 'user_login_durations and users',
            'unit' => 'percentage',
            'points' => $points,
        ], 'Login Participation');
    }

    private function todayLoginCountChart(): array
    {
        $start = Carbon::today();
        $points = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $hourStart = $start->copy()->addHours($hour);
            $hourEnd = $hourStart->copy()->addHour();

            $points[] = [
                'label' => $hourStart->format('g A'),
                'value' => (float) UserLoginDuration::where('logged_in_at', '>=', $hourStart)
                    ->where('logged_in_at', '<', $hourEnd)
                    ->distinct('user_id')
                    ->count('user_id'),
            ];
        }

        return $this->withSeries([
            'source' => 'user_login_durations.logged_in_at',
            'unit' => 'distinct users',
            'points' => $points,
        ], "Today's Login Count");
    }

    private function onlineCharacters(): array
    {
        return UserLoginDuration::with(['user.character.map.gameMap'])
            ->whereNull('duration_in_seconds')
            ->whereNull('logged_out_at')
            ->whereNotNull('last_heart_beat')
            ->where('last_heart_beat', '>=', Carbon::now()->subMinutes(30))
            ->latest('logged_in_at')
            ->get()
            ->filter(fn (UserLoginDuration $duration) => ! is_null($duration->user?->character))
            ->map(function (UserLoginDuration $duration) {
                $character = $duration->user->character;

                return [
                    'character_name' => $character->name,
                    'user_name' => $duration->user->email,
                    'level' => (int) $character->level,
                    'map' => $character->map?->gameMap?->name,
                    'logged_in_at' => $duration->logged_in_at?->toIso8601String(),
                    'last_activity' => $duration->last_activity?->toIso8601String(),
                    'last_heart_beat' => $duration->last_heart_beat?->toIso8601String(),
                ];
            })
            ->values()
            ->toArray();
    }

    private function reincarnationChart(): array
    {
        $chart = [
            'source' => 'characters',
            'unit' => 'times reincarnated',
            'points' => Character::where('times_reincarnated', '>', 0)
                ->orderByDesc('times_reincarnated')
                ->limit(10)
                ->get(['name', 'times_reincarnated'])
                ->map(fn (Character $character) => [
                    'label' => $character->name,
                    'value' => (float) $character->times_reincarnated,
                ])
                ->toArray(),
        ];

        return $this->withSeries($chart, 'Reincarnations');
    }

    private function questCompletionChart(): array
    {
        $chart = [
            'source' => 'quests_completed.quest_id',
            'unit' => 'completions',
            'points' => Quest::query()
                ->select('quests.name as label', DB::raw('COUNT(quests_completed.id) as value'))
                ->join('quests_completed', 'quests.id', '=', 'quests_completed.quest_id')
                ->groupBy('quests.id', 'quests.name')
                ->orderByDesc('value')
                ->limit(10)
                ->get()
                ->map(fn (Quest $quest) => [
                    'label' => $quest->label,
                    'value' => (float) $quest->value,
                ])
                ->toArray(),
        ];

        return $this->withSeries($chart, 'Quest Completions');
    }

    private function guideQuestCompletionChart(): array
    {
        $chart = [
            'source' => 'quests_completed.guide_quest_id',
            'unit' => 'completions',
            'points' => GuideQuest::query()
                ->select('guide_quests.name as label', DB::raw('COUNT(quests_completed.id) as value'))
                ->join('quests_completed', 'guide_quests.id', '=', 'quests_completed.guide_quest_id')
                ->groupBy('guide_quests.id', 'guide_quests.name')
                ->orderByDesc('value')
                ->limit(10)
                ->get()
                ->map(fn (GuideQuest $quest) => [
                    'label' => $quest->label,
                    'value' => (float) $quest->value,
                ])
                ->toArray(),
        ];

        return $this->withSeries($chart, 'Guide Quest Completions');
    }

    private function goldChart(): array
    {
        $chart = [
            'source' => 'characters.gold',
            'unit' => 'character-held gold',
            'points' => Character::where('gold', '>', 0)
                ->orderByDesc('gold')
                ->limit(10)
                ->get(['name', 'gold'])
                ->map(fn (Character $character) => [
                    'label' => $character->name,
                    'value' => (float) $character->gold,
                ])
                ->toArray(),
        ];

        return $this->withSeries($chart, 'Character Gold');
    }

    private function kingdomSummary(): array
    {
        return [
            'total_kingdoms' => Kingdom::count(),
            'kingdoms_with_owners' => Kingdom::whereNotNull('character_id')->count(),
            'npc_kingdoms' => Kingdom::whereNull('character_id')->count(),
        ];
    }

    private function topKingdomHolders(): array
    {
        return Character::withCount('kingdoms')
            ->having('kingdoms_count', '>', 0)
            ->orderByDesc('kingdoms_count')
            ->limit(10)
            ->get()
            ->map(fn (Character $character) => [
                'character_name' => $character->name,
                'kingdom_count' => (int) $character->kingdoms_count,
            ])
            ->toArray();
    }

    private function metricDefinitions(): array
    {
        return [
            'total_registered_users' => 'Raw database count of rows in the users table.',
            'new_registrations' => 'Raw database chart of user registrations grouped by users.created_at date.',
            'login_activity' => 'Snapshot-based login line using user_site_access_statistics.amount_signed_in grouped by snapshot date, with a database-backed inactive users deleted line from inactive_user_deletion_statistics tracked on the same date grouping.',
            'average_login_duration' => 'Raw database average from completed user_login_durations rows only; open sessions with null duration are excluded. Unit is minutes.',
            'login_participation' => 'Raw database percentage of distinct non-admin users with at least one user_login_durations.logged_in_at inside each window divided by the total non-admin user count.',
            'today_login_count' => 'Raw database count of distinct users with user_login_durations.logged_in_at grouped by hour for the current day.',
            'online_characters' => 'Raw database list based on open user_login_durations rows where duration_in_seconds and logged_out_at are null and last_heart_beat is within the last 30 minutes.',
            'inactive_user_deletions' => 'Raw database count of users deleted by the inactive-user cleanup command, recorded in inactive_user_deletion_statistics.',
            'reincarnation_stats' => 'Raw database top character list ordered by characters.times_reincarnated.',
            'quest_completion_stats' => 'Raw database top quest completions using quests_completed.quest_id.',
            'guide_quest_completion_stats' => 'Raw database top guide quest completions using quests_completed.guide_quest_id.',
            'character_gold_stats' => 'Raw database top character-held gold values from characters.gold. Characters without kingdoms are included.',
            'richest_character' => 'Raw database top character ordered by characters.gold; kingdom treasury is not included.',
            'highest_level_character' => 'Raw database top character ordered by characters.level.',
            'kingdom_summary' => 'Raw database kingdom counts from the kingdoms table.',
        ];
    }

    private function inactiveUserDeletionSummary(): array
    {
        return [
            'today' => (int) InactiveUserDeletionStatistic::where('tracked_at', '>=', Carbon::today())->sum('deleted_count'),
            'last_30_days' => (int) InactiveUserDeletionStatistic::where('tracked_at', '>=', Carbon::now()->subDays(30))->sum('deleted_count'),
            'all_time' => (int) InactiveUserDeletionStatistic::sum('deleted_count'),
        ];
    }

    private function inactiveUserDeletionPoints(): array
    {
        return InactiveUserDeletionStatistic::query()
            ->selectRaw('DATE(tracked_at) as label, SUM(deleted_count) as value')
            ->groupBy(DB::raw('DATE(tracked_at)'))
            ->orderBy('label')
            ->limit(31)
            ->get()
            ->map(fn (InactiveUserDeletionStatistic $statistic) => [
                'label' => (string) $statistic->label,
                'value' => (float) $statistic->value,
            ])
            ->toArray();
    }

    private function withSeries(array $chart, string $seriesLabel): array
    {
        $chart['series'] = [
            [
                'label' => $seriesLabel,
                'points' => $chart['points'],
            ],
        ];

        return $chart;
    }

    private function loginParticipationWindows(): array
    {
        return [
            ['label' => 'Today', 'window' => 'today', 'start' => Carbon::today()],
            ['label' => 'Last 7 Days', 'window' => '7_days', 'start' => now()->subDays(7)],
            ['label' => 'Last 14 Days', 'window' => '14_days', 'start' => now()->subDays(14)],
            ['label' => 'Last 30 Days', 'window' => '30_days', 'start' => now()->subDays(30)],
            ['label' => 'Last 6 Months', 'window' => '6_months', 'start' => now()->subMonths(6)],
            ['label' => 'Last 1 Year', 'window' => '1_year', 'start' => now()->subYear()],
        ];
    }

    private function totalNonAdminUsers(): int
    {
        return User::whereDoesntHave('roles', function ($query) {
            $query->where('name', 'Admin');
        })->count();
    }

    private function distinctNonAdminLoginUsersSince(Carbon $startDate): int
    {
        return UserLoginDuration::where('logged_in_at', '>=', $startDate)
            ->whereHas('user', function ($query) {
                $query->whereDoesntHave('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'Admin');
                });
            })
            ->distinct('user_id')
            ->count('user_id');
    }
}
