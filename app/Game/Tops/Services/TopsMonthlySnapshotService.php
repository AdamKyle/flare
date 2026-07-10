<?php

namespace App\Game\Tops\Services;

use App\Flare\Models\TopsMonthlySnapshot;
use Carbon\Carbon;

class TopsMonthlySnapshotService
{
    public function __construct(
        private readonly CharacterTopsService $characterTopsService,
        private readonly ExplorationTopsService $explorationTopsService,
        private readonly DelveTopsService $delveTopsService,
        private readonly FactionLoyaltyTopsService $factionLoyaltyTopsService,
        private readonly KingdomTopsService $kingdomTopsService,
    ) {}

    public function snapshot(Carbon $periodStart, Carbon $periodEnd): int
    {
        $period = $periodStart->format('Y-m');
        $count = 0;

        $count += $this->upsertRows('characters', 'progression', $periodStart, $periodEnd, $this->characterTopsService->leaderboard(['period' => 'all_time'])['rows']);
        $count += $this->upsertRows('exploration', 'kills', $periodStart, $periodEnd, $this->explorationTopsService->leaderboard(['period' => 'all_time', 'metric' => 'kills'])['rows']);
        $count += $this->upsertRows('delve', 'survived_duration_seconds', $periodStart, $periodEnd, $this->delveTopsService->leaderboard(['period' => $period, 'metric' => 'survived_duration_seconds'])['rows']);
        $count += $this->upsertRows('faction-loyalty', 'highest_faction_level', $periodStart, $periodEnd, $this->factionLoyaltyTopsService->leaderboard(['period' => 'all_time', 'metric' => 'highest_faction_level'])['rows']);
        $count += $this->upsertRows('kingdoms', 'kingdom_count', $periodStart, $periodEnd, $this->kingdomTopsService->leaderboard(['period' => 'all_time', 'metric' => 'kingdom_count'])['rows']);

        return $count;
    }

    private function upsertRows(string $board, string $metric, Carbon $periodStart, Carbon $periodEnd, array $rows): int
    {
        foreach ($rows as $row) {
            TopsMonthlySnapshot::updateOrCreate(
                [
                    'board_type' => $board,
                    'metric_key' => $metric,
                    'period_start' => $periodStart->toDateString(),
                    'rank' => $row['rank'],
                ],
                [
                    'period_end' => $periodEnd->toDateString(),
                    'character_id' => $row['character_id'] ?? null,
                    'subject_type' => isset($row['character_id']) ? 'character' : null,
                    'subject_id' => $row['character_id'] ?? null,
                    'score_integer' => $this->scoreInteger($row, $metric),
                    'score_decimal' => $this->scoreDecimal($row, $metric),
                    'snapshot_data' => $row,
                ]
            );
        }

        return count($rows);
    }

    private function scoreInteger(array $row, string $metric): ?int
    {
        if (! isset($row[$metric]) || is_float($row[$metric])) {
            return null;
        }

        return (int) $row[$metric];
    }

    private function scoreDecimal(array $row, string $metric): ?float
    {
        if (! isset($row[$metric]) || ! is_numeric($row[$metric])) {
            return null;
        }

        return (float) $row[$metric];
    }
}
