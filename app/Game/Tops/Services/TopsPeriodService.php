<?php

namespace App\Game\Tops\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class TopsPeriodService
{
    public function resolve(?string $period): array
    {
        if ($period === 'all_time') {
            return [
                'key' => 'all_time',
                'label' => 'All Time',
                'start' => null,
                'end' => null,
                'is_archived_month' => false,
            ];
        }

        if (! is_null($period) && preg_match('/^\d{4}-\d{2}$/', $period) === 1) {
            $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            return [
                'key' => $period,
                'label' => $start->format('F Y'),
                'start' => $start,
                'end' => $end,
                'is_archived_month' => $start->lt(now()->startOfMonth()),
            ];
        }

        $start = now()->startOfMonth();

        return [
            'key' => 'current_month',
            'label' => 'Current Month',
            'start' => $start,
            'end' => now()->endOfMonth(),
            'is_archived_month' => false,
        ];
    }

    public function availablePeriods(): array
    {
        return [
            ['key' => 'current_month', 'label' => 'Current Month'],
            ['key' => 'all_time', 'label' => 'All Time'],
            ['key' => now()->subMonthNoOverflow()->format('Y-m'), 'label' => now()->subMonthNoOverflow()->format('F Y')],
        ];
    }

    public function previousCompletedMonth(?string $periodStart = null, ?string $periodEnd = null): array
    {
        if (! is_null($periodStart) && ! is_null($periodEnd)) {
            return [
                Carbon::parse($periodStart)->startOfDay(),
                Carbon::parse($periodEnd)->endOfDay(),
            ];
        }

        $start = now()->subMonthNoOverflow()->startOfMonth();

        return [$start, $start->copy()->endOfMonth()];
    }

    public function periodScope(Collection $rows, array $period, string $dateKey): Collection
    {
        if ($period['key'] === 'all_time') {
            return $rows;
        }

        return $rows->filter(function (array $row) use ($period, $dateKey) {
            if (empty($row[$dateKey])) {
                return false;
            }

            $date = Carbon::parse($row[$dateKey]);

            return $date->betweenIncluded($period['start'], $period['end']);
        });
    }
}
