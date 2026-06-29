<?php

namespace App\Admin\Services;

use App\Flare\Models\BatchCrafting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BatchCraftingMonitoringService
{
    public function activeCharacters(): array
    {
        return BatchCrafting::whereNull('completed_at')
            ->whereNull('cancelled_at')
            ->with('character:id,name')
            ->get()
            ->map(function (BatchCrafting $batch): array {
                return [
                    'character_id' => $batch->character_id,
                    'character_name' => $batch->character?->name,
                    'batch_type' => $batch->batch_type,
                    'disposition' => $batch->disposition,
                    'started_at' => $batch->started_at?->toDateTimeString(),
                    'ends_at' => $batch->ends_at?->toDateTimeString(),
                    'crafted_count' => $batch->crafted_count ?? 0,
                    'failed_count' => $batch->failed_count ?? 0,
                    'kept_count' => $batch->kept_count ?? 0,
                    'sold_count' => $batch->sold_count ?? 0,
                ];
            })
            ->all();
    }

    public function recentRuns(Request $request): LengthAwarePaginator
    {
        return $this->filteredQuery($request)
            ->with('character:id,name')
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function summary(Request $request): array
    {
        $days = $this->validatedDays($request->integer('days', 7));
        $query = BatchCrafting::where('started_at', '>=', now()->subDays($days));

        return [
            'total_runs' => $query->clone()->count(),
            'active' => BatchCrafting::whereNull('completed_at')->whereNull('cancelled_at')->count(),
            'completed' => $query->clone()->whereNotNull('completed_at')->count(),
            'cancelled' => $query->clone()->whereNotNull('cancelled_at')->count(),
            'total_crafted' => (int) $query->clone()->sum('crafted_count'),
            'total_failed' => (int) $query->clone()->sum('failed_count'),
        ];
    }

    public function chart(Request $request): array
    {
        $days = $this->validatedDays($request->integer('days', 7));

        return BatchCrafting::where('started_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(started_at) as period, COUNT(*) as runs, SUM(crafted_count) as crafted, SUM(failed_count) as failed')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row): array => [
                'period' => $row->period,
                'runs' => (int) $row->runs,
                'crafted' => (int) $row->crafted,
                'failed' => (int) $row->failed,
            ])
            ->all();
    }

    private function filteredQuery(Request $request): Builder
    {
        return BatchCrafting::query()
            ->when($request->filled('character_name'), function (Builder $query) use ($request): void {
                $query->whereHas(
                    'character',
                    fn (Builder $q) => $q->where('name', 'like', '%' . $request->string('character_name') . '%'),
                );
            })
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('started_at', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('started_at', '<=', $request->string('date_to')))
            ->when($request->string('status')->toString() === 'active', fn (Builder $q) => $q->whereNull('completed_at')->whereNull('cancelled_at'))
            ->when($request->string('status')->toString() === 'completed', fn (Builder $q) => $q->whereNotNull('completed_at'))
            ->when($request->string('status')->toString() === 'cancelled', fn (Builder $q) => $q->whereNotNull('cancelled_at'))
            ->when($request->filled('batch_type'), fn (Builder $q) => $q->where('batch_type', $request->string('batch_type')->toString()));
    }

    private function validatedDays(int $days): int
    {
        return in_array($days, [1, 7, 14, 30, 180, 365], true) ? $days : 7;
    }
}
