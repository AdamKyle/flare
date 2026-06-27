<?php

namespace App\Admin\Services;

use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
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
                + (int) $counts->get(BattleRewardRequestStatus::PROCESSING->value, 0)
                + (int) $counts->get(BattleRewardRequestStatus::RESUMABLE->value, 0),
            'pending' => (int) $counts->get(BattleRewardRequestStatus::PENDING->value, 0),
            'processing' => (int) $counts->get(BattleRewardRequestStatus::PROCESSING->value, 0),
            'resumable' => (int) $counts->get(BattleRewardRequestStatus::RESUMABLE->value, 0),
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
            ->selectRaw($dateExpression.' as period, status, COUNT(*) as aggregate')
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
                fn (Builder $query) => $query->where('characters.name', 'like', '%'.$request->string('search').'%'),
            )
            ->selectRaw(
                'characters.id as character_id, characters.name as character_name, '
                .'SUM(CASE WHEN source_type IN (?, ?, ?) THEN 1 ELSE 0 END) as battle_requests, '
                .'SUM(CASE WHEN source_type IN (?, ?, ?) THEN 1 ELSE 0 END) as quest_requests, '
                .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_count, '
                .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as processing_count, '
                .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as resumable_count, '
                .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed_count, '
                .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_count, '
                .'MAX(character_battle_reward_requests.created_at) as last_request_at',
                [
                    BattleRewardRequestSourceType::BATTLE->value,
                    BattleRewardRequestSourceType::EXPLORATION->value,
                    BattleRewardRequestSourceType::AUTOMATION->value,
                    BattleRewardRequestSourceType::QUEST->value,
                    BattleRewardRequestSourceType::GUIDE_QUEST->value,
                    BattleRewardRequestSourceType::RAID_QUEST->value,
                    BattleRewardRequestStatus::PENDING->value,
                    BattleRewardRequestStatus::PROCESSING->value,
                    BattleRewardRequestStatus::RESUMABLE->value,
                    BattleRewardRequestStatus::FAILED->value,
                    BattleRewardRequestStatus::COMPLETED->value,
                ],
            )
            ->groupBy('characters.id', 'characters.name')
            ->orderByDesc('last_request_at')
            ->paginate(min($request->integer('per_page', 10), 100));
    }

    public function requests(Request $request, ?int $characterId = null): LengthAwarePaginator
    {
        $requests = $this->filteredRequests($request, $characterId)
            ->with('character:id,name')
            ->with(['steps' => fn ($query) => $query->orderBy('id')])
            ->withCount([
                'steps as completed_step_count' => fn (Builder $query) => $query->where('status', BattleRewardStepStatus::COMPLETED),
                'steps as total_step_count',
                'messages as un_emitted_message_count' => fn (Builder $query) => $query->unemitted(),
            ])
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 10), 100));

        return $requests;
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
        return $this->processingQueueRows(onlyStale: true);
    }

    public function interruptedQueues(): array
    {
        return $this->processingQueueRows(onlyStale: false);
    }

    private function processingQueueRows(bool $onlyStale): array
    {
        $query = CharacterBattleRewardQueueState::query()
            ->with('character:id,name')
            ->where('is_processing', true)
            ->orderBy('heartbeat_at');

        if ($onlyStale) {
            $query->stale($this->queueManager->staleCutoff());
        }

        return $query->get()
            ->map(function (CharacterBattleRewardQueueState $state): array {
                $requests = CharacterBattleRewardRequest::forCharacter($state->character_id)
                    ->queued()
                    ->orderBy('id')
                    ->limit(50)
                    ->withCount([
                        'steps as completed_step_count' => fn (Builder $query) => $query->where('status', BattleRewardStepStatus::COMPLETED),
                        'steps as total_step_count',
                        'messages as un_emitted_message_count' => fn (Builder $query) => $query->unemitted(),
                    ])
                    ->with(['steps' => fn ($query) => $query->orderBy('id')])
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

                $processingRequest = $requests->first(fn (CharacterBattleRewardRequest $request): bool => $request->status === BattleRewardRequestStatus::PROCESSING);
                $currentRequest = $requests->first(fn (CharacterBattleRewardRequest $request): bool => in_array($request->status, [
                    BattleRewardRequestStatus::PROCESSING,
                    BattleRewardRequestStatus::RESUMABLE,
                ], true));
                $currentStep = $currentRequest?->steps->first(fn ($step): bool => $step->status !== BattleRewardStepStatus::COMPLETED);

                $isLocked = $this->queueManager->isProcessorLocked($state->character_id);
                $processingCount = CharacterBattleRewardRequest::forCharacter($state->character_id)->processing()->count();
                $isRecoverable = $processingCount > 0 && ! $isLocked;

                return [
                    'character_id' => $state->character_id,
                    'character_name' => $state->character?->name,
                    'queue_state_id' => $state->id,
                    'started_at' => $state->started_at,
                    'heartbeat_at' => $state->heartbeat_at,
                    'heartbeat_age_seconds' => is_null($state->heartbeat_at)
                        ? null
                        : (int) $state->heartbeat_at->diffInSeconds(now()),
                    'stale_age_seconds' => is_null($state->heartbeat_at)
                        ? null
                        : (int) $state->heartbeat_at->diffInSeconds(now()),
                    'processor_lock_held' => $isLocked,
                    'is_recoverable' => $isRecoverable,
                    'pending_request_count' => CharacterBattleRewardRequest::forCharacter($state->character_id)->pending()->count(),
                    'processing_request_count' => $processingCount,
                    'resumable_request_count' => CharacterBattleRewardRequest::forCharacter($state->character_id)->resumable()->count(),
                    'failed_request_count' => CharacterBattleRewardRequest::forCharacter($state->character_id)->failed()->count(),
                    'current_request_id' => $currentRequest?->id,
                    'current_request_source_type' => $currentRequest?->source_type?->value,
                    'current_request_source_id' => $currentRequest?->source_id,
                    'current_ledger_step' => $currentStep?->step_name?->value,
                    'current_ledger_step_status' => $currentStep?->status?->value,
                    'current_ledger_step_heartbeat_at' => $currentStep?->heartbeat_at,
                    'ledger_completed_count' => $processingRequest?->steps->filter(fn ($step): bool => $step->status === BattleRewardStepStatus::COMPLETED)->count() ?? 0,
                    'ledger_total_count' => $processingRequest?->steps->count() ?? 0,
                    'checkpoint_age_seconds' => is_null($currentStep?->checkpoint_json) || is_null($currentStep?->heartbeat_at)
                        ? null
                        : (int) $currentStep->heartbeat_at->diffInSeconds(now()),
                    'un_emitted_message_count' => $requests->sum('un_emitted_message_count'),
                    'resumable_count' => CharacterBattleRewardRequest::forCharacter($state->character_id)->resumable()->count(),
                    'oldest_pending_request_created_at' => CharacterBattleRewardRequest::forCharacter($state->character_id)
                        ->pending()
                        ->min('created_at'),
                    'oldest_processing_request_created_at' => CharacterBattleRewardRequest::forCharacter($state->character_id)
                        ->processing()
                        ->min('created_at'),
                    'requests' => $requests->map(fn (CharacterBattleRewardRequest $rewardRequest): array => $this->requestPayload($rewardRequest))->values(),
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
            ->when($request->filled('failed_reason'), fn (Builder $query) => $query->where('failed_reason', 'like', '%'.$request->string('failed_reason').'%'))
            ->when($request->filled('source_id'), fn (Builder $query) => $query->where('source_id', 'like', '%'.$request->string('source_id').'%'))
            ->when($request->filled('character_name'), function (Builder $query) use ($request): void {
                $query->whereHas(
                    'character',
                    fn (Builder $characterQuery) => $characterQuery->where('name', 'like', '%'.$request->string('character_name').'%'),
                );
            });
    }

    private function requestPayload(CharacterBattleRewardRequest $request): array
    {
        $currentStep = $request->relationLoaded('steps')
            ? $request->steps->first(fn ($step): bool => $step->status !== BattleRewardStepStatus::COMPLETED)
            : null;

        return [
            'id' => $request->id,
            'character' => $request->relationLoaded('character') && ! is_null($request->character)
                ? ['name' => $request->character->name]
                : null,
            'status' => $request->status?->value,
            'priority' => $request->priority?->value,
            'source_type' => $request->source_type?->value,
            'source_id' => $request->source_id,
            'failed_reason' => $request->failed_reason,
            'created_at' => $request->created_at,
            'updated_at' => $request->updated_at,
            'current_step_name' => $currentStep?->step_name?->value,
            'current_step_status' => $currentStep?->status?->value,
            'completed_step_count' => (int) ($request->completed_step_count ?? 0),
            'total_step_count' => (int) ($request->total_step_count ?? 0),
            'un_emitted_message_count' => (int) ($request->un_emitted_message_count ?? 0),
        ];
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
                    'resumable' => (int) $statuses->get(BattleRewardRequestStatus::RESUMABLE->value, 0),
                    'completed' => (int) $statuses->get(BattleRewardRequestStatus::COMPLETED->value, 0),
                    'failed' => (int) $statuses->get(BattleRewardRequestStatus::FAILED->value, 0),
                ];
            })
            ->values()
            ->all();
    }
}
