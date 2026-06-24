<?php

namespace App\Admin\Services;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Services\BattleRewardProcessingQueueManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BattleRewardQueueAdminService
{
    public function __construct(
        private readonly BattleRewardProcessingQueueManager $queueManager,
    ) {}

    public function summary(): array
    {
        $counts = CharacterBattleRewardRequest::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'queued' => (int) $counts->get(BattleRewardRequestStatus::PENDING->value, 0)
                + (int) $counts->get(BattleRewardRequestStatus::PROCESSING->value, 0),
            'pending' => (int) $counts->get(BattleRewardRequestStatus::PENDING->value, 0),
            'processing' => (int) $counts->get(BattleRewardRequestStatus::PROCESSING->value, 0),
            'completed' => (int) $counts->get(BattleRewardRequestStatus::COMPLETED->value, 0),
            'failed' => (int) $counts->get(BattleRewardRequestStatus::FAILED->value, 0),
        ];
    }

    public function chart(int $days = 1, bool $hourly = false, bool $previousPeriod = false): array
    {
        $end = $previousPeriod ? now()->subDays($days)->endOfDay() : now();
        $start = $end->copy()->subDays($days);
        $dateExpression = $hourly
            ? "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')"
            : 'DATE(created_at)';

        $rows = CharacterBattleRewardRequest::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw($dateExpression . ' as period, status, COUNT(*) as aggregate')
            ->groupBy('period', 'status')
            ->orderBy('period')
            ->get();

        return $this->formatChartRows($rows);
    }

    public function lastHourChart(): array
    {
        $rows = CharacterBattleRewardRequest::query()
            ->where('created_at', '>=', now()->subHour())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:00') as period, status, COUNT(*) as aggregate")
            ->groupBy('period', 'status')
            ->orderBy('period')
            ->get();

        return $this->formatChartRows($rows);
    }

    public function characters(Request $request): LengthAwarePaginator
    {
        return CharacterBattleRewardRequest::query()
            ->join('characters', 'characters.id', '=', 'character_battle_reward_requests.character_id')
            ->when(
                $request->filled('search'),
                fn (Builder $query) => $query->where('characters.name', 'like', '%' . $request->string('search') . '%'),
            )
            ->selectRaw(
                'characters.id as character_id, characters.name as character_name, '
                . 'SUM(CASE WHEN source_type IN (?, ?, ?) THEN 1 ELSE 0 END) as battle_requests, '
                . 'SUM(CASE WHEN source_type IN (?, ?, ?) THEN 1 ELSE 0 END) as quest_requests, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_count, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as processing_count, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed_count, '
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_count, '
                . 'MAX(character_battle_reward_requests.created_at) as last_request_at',
                [
                    BattleRewardRequestSourceType::BATTLE->value,
                    BattleRewardRequestSourceType::EXPLORATION->value,
                    BattleRewardRequestSourceType::AUTOMATION->value,
                    BattleRewardRequestSourceType::QUEST->value,
                    BattleRewardRequestSourceType::GUIDE_QUEST->value,
                    BattleRewardRequestSourceType::RAID_QUEST->value,
                    BattleRewardRequestStatus::PENDING->value,
                    BattleRewardRequestStatus::PROCESSING->value,
                    BattleRewardRequestStatus::FAILED->value,
                    BattleRewardRequestStatus::COMPLETED->value,
                ],
            )
            ->groupBy('characters.id', 'characters.name')
            ->orderByDesc('last_request_at')
            ->paginate(min($request->integer('per_page', 25), 100));
    }

    public function requests(Request $request, ?int $characterId = null): LengthAwarePaginator
    {
        return $this->filteredRequests($request, $characterId)
            ->with('character:id,name')
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 25), 100));
    }

    public function statusBreakdown(Request $request, ?int $characterId = null): array
    {
        $days = $this->validatedDays($request->integer('days', 7));
        $query = CharacterBattleRewardRequest::query()
            ->where('created_at', '>=', now()->subDays($days));

        if (! is_null($characterId)) {
            $query->forCharacter($characterId);
        }

        $rows = $query
            ->selectRaw('DATE(created_at) as period, status, COUNT(*) as aggregate')
            ->groupBy('period', 'status')
            ->orderBy('period')
            ->get();

        return $this->formatChartRows($rows);
    }

    public function staleQueues(): array
    {
        return CharacterBattleRewardQueueState::query()
            ->with('character:id,name')
            ->stale($this->queueManager->staleCutoff())
            ->orderBy('heartbeat_at')
            ->get()
            ->map(function (CharacterBattleRewardQueueState $state): array {
                $requests = CharacterBattleRewardRequest::forCharacter($state->character_id)
                    ->queued()
                    ->orderBy('id')
                    ->limit(50)
                    ->get([
                        'id',
                        'character_id',
                        'status',
                        'priority',
                        'source_type',
                        'source_id',
                        'failed_reason',
                        'created_at',
                        'updated_at',
                    ]);

                return [
                    'character_id' => $state->character_id,
                    'character_name' => $state->character?->name,
                    'queue_state_id' => $state->id,
                    'started_at' => $state->started_at,
                    'heartbeat_at' => $state->heartbeat_at,
                    'stale_age_seconds' => is_null($state->heartbeat_at)
                        ? null
                        : $state->heartbeat_at->diffInSeconds(now()),
                    'pending_request_count' => CharacterBattleRewardRequest::forCharacter($state->character_id)->pending()->count(),
                    'processing_request_count' => CharacterBattleRewardRequest::forCharacter($state->character_id)->processing()->count(),
                    'failed_request_count' => CharacterBattleRewardRequest::forCharacter($state->character_id)->failed()->count(),
                    'oldest_pending_request_created_at' => CharacterBattleRewardRequest::forCharacter($state->character_id)
                        ->pending()
                        ->min('created_at'),
                    'oldest_processing_request_created_at' => CharacterBattleRewardRequest::forCharacter($state->character_id)
                        ->processing()
                        ->min('created_at'),
                    'requests' => $requests,
                ];
            })
            ->all();
    }

    private function filteredRequests(Request $request, ?int $characterId): Builder
    {
        return CharacterBattleRewardRequest::query()
            ->when(! is_null($characterId), fn (Builder $query) => $query->forCharacter($characterId))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn (Builder $query) => $query->where('priority', $request->string('priority')))
            ->when($request->filled('source_type'), fn (Builder $query) => $query->where('source_type', $request->string('source_type')))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->string('date_to')))
            ->when($request->filled('failed_reason'), fn (Builder $query) => $query->where('failed_reason', 'like', '%' . $request->string('failed_reason') . '%'))
            ->when($request->filled('source_id'), fn (Builder $query) => $query->where('source_id', 'like', '%' . $request->string('source_id') . '%'))
            ->when($request->filled('character_name'), function (Builder $query) use ($request): void {
                $query->whereHas(
                    'character',
                    fn (Builder $characterQuery) => $characterQuery->where('name', 'like', '%' . $request->string('character_name') . '%'),
                );
            });
    }

    private function validatedDays(int $days): int
    {
        return in_array($days, [1, 7, 14, 30, 60, 120, 365], true) ? $days : 7;
    }

    private function formatChartRows(Collection $rows): array
    {
        return $rows
            ->groupBy('period')
            ->map(function (Collection $periodRows, string $period): array {
                $statuses = $periodRows->mapWithKeys(function (CharacterBattleRewardRequest $request): array {
                    $status = $request->status instanceof BattleRewardRequestStatus
                        ? $request->status->value
                        : $request->status;

                    return [$status => $request->aggregate];
                });

                return [
                    'period' => $period,
                    'pending' => (int) $statuses->get(BattleRewardRequestStatus::PENDING->value, 0),
                    'processing' => (int) $statuses->get(BattleRewardRequestStatus::PROCESSING->value, 0),
                    'completed' => (int) $statuses->get(BattleRewardRequestStatus::COMPLETED->value, 0),
                    'failed' => (int) $statuses->get(BattleRewardRequestStatus::FAILED->value, 0),
                ];
            })
            ->values()
            ->all();
    }
}
