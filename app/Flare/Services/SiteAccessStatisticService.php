<?php

namespace App\Flare\Services;

use App\Flare\Models\UserSiteAccessStatistics;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class SiteAccessStatisticService
{
    private int $daysPast;

    private string $attribute;

    /**
     * Set the number of days past.
     */
    public function setDaysPast(int $daysPast): self
    {
        $this->daysPast = $daysPast;

        return $this;
    }

    /**
     * Set the attribute to be used.
     */
    public function setAttribute(string $attribute): self
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * Get registered user statistics.
     *
     * @throws Exception
     */
    public function getRegistered(): array
    {
        $statistics = $this->getQuery();

        $labels = $this->formatLabels($statistics);
        $data = $this->getLastRecordForTimeFrame($statistics);

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get signed-in user statistics.
     *
     * @throws Exception
     */
    public function getSignedIn(): array
    {
        $statistics = $this->getQuery();

        $labels = $this->formatLabels($statistics);
        $data = $this->getLastRecordForTimeFrame($statistics);

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Retrieve the last record for each hour or day.
     */
    private function getLastRecordForTimeFrame(Collection $statistics): array
    {
        if ($this->daysPast === 0) {
            return collect(range(0, 23))->map(function ($hour) use ($statistics) {
                return $statistics
                    ->filter(fn ($stat) => Carbon::parse($stat->created_at)->hour === $hour)
                    ->last()[$this->attribute] ?? 0;
            })->toArray();
        }

        $start = $this->calculateStartDate();

        return collect(range(0, $this->daysPast))->map(function ($day) use ($start, $statistics) {
            $date = $start->copy()->addDays($day);

            return $statistics
                ->filter(fn ($stat) => Carbon::parse($stat->created_at)->isSameDay($date))
                ->last()[$this->attribute] ?? 0;
        })->toArray();
    }

    /**
     * Format labels for hours or days.
     */
    private function formatLabels(Collection $statistics): array
    {
        if ($this->daysPast === 0) {
            return collect(range(0, 23))->map(fn ($hour) => Carbon::createFromTime($hour)->format('g A'))->toArray();
        }

        $start = $this->calculateStartDate();

        return collect(range(0, $this->daysPast))->map(fn ($day) => $start->copy()->addDays($day)->format('Y-m-d'))->toArray();
    }

    /**
     * Retrieve statistics from the database.
     *
     * @throws Exception
     */
    private function getQuery(): Collection
    {
        $start = $this->calculateStartDate();
        $end = Carbon::today()->endOfDay();

        return UserSiteAccessStatistics::whereNotNull($this->attribute)
            ->whereBetween('created_at', [$start, $end])
            ->select($this->attribute, 'created_at')
            ->get();
    }

    /**
     * Calculate the start date based on the number of days past.
     */
    private function calculateStartDate(): Carbon
    {
        return match ($this->daysPast) {
            0 => Carbon::today()->startOfDay(),
            7, 14 => Carbon::now()->subDays($this->daysPast)->startOfDay(),
            31 => Carbon::today()->subMonth(),
            default => Carbon::today(),
        };
    }
}
