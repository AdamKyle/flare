<?php

namespace App\Game\Automation\Delve\Services;

use App\Flare\Models\DelveExploration;
use App\Flare\Models\DelveLog;
use App\Game\Automation\Calculations\BattleMessageTotalsCalculator;
use App\Game\Automation\Delve\Enums\DelveOutcome;

class DelveTelemetryService
{
    /**
     * @param BattleMessageTotalsCalculator $battleMessageTotalsCalculator
     */
    public function __construct(private readonly BattleMessageTotalsCalculator $battleMessageTotalsCalculator) {}

    /**
     * Build the cumulative Delve telemetry from the persisted Delve round logs.
     *
     * @param DelveExploration $delve
     * @return array
     */
    public function telemetry(DelveExploration $delve): array
    {
        $cumulative = $this->baselinePoint($delve);
        $chartPoints = [$cumulative];

        $logs = $delve->delveLogs()->orderBy('created_at')->orderBy('id')->get();

        foreach ($logs as $log) {
            $cumulative = $this->appendLog($delve, $cumulative, $log);
            $chartPoints[] = $cumulative;
        }

        return [
            'totals' => [
                'rounds' => $cumulative['rounds'],
                'wins' => $cumulative['wins'],
                'timeouts' => $cumulative['timeouts'],
                'pack_size' => $delve->pack_size,
                'enemy_strength_increase' => $cumulative['enemy_strength_increase'],
            ],
            'damage' => [
                'weapon' => $cumulative['weapon_damage'],
                'spell' => $cumulative['spell_damage'],
            ],
            'healing' => $cumulative['healing'],
            'blocked' => $cumulative['blocked'],
            'chart_points' => $chartPoints,
        ];
    }

    /**
     * Build the zeroed chart point representing the start of the Delve.
     *
     * @param DelveExploration $delve
     * @return array
     */
    private function baselinePoint(DelveExploration $delve): array
    {
        return [
            'elapsed_seconds' => 0,
            'rounds' => 0,
            'wins' => 0,
            'timeouts' => 0,
            'enemy_strength_increase' => 0,
            'pack_size' => $delve->pack_size,
            'weapon_damage' => 0,
            'spell_damage' => 0,
            'healing' => 0,
            'blocked' => 0,
        ];
    }

    /**
     * Add one persisted Delve round log onto the previous cumulative chart point.
     *
     * @param DelveExploration $delve
     * @param array $previousPoint
     * @param DelveLog $log
     * @return array
     */
    private function appendLog(DelveExploration $delve, array $previousPoint, DelveLog $log): array
    {
        $outcome = DelveOutcome::tryFrom($log->outcome);
        $messageTotals = $this->battleMessageTotalsCalculator->totals($log->fight_data ?? []);

        return [
            'elapsed_seconds' => max(0, $log->created_at->getTimestamp() - $delve->started_at->getTimestamp()),
            'rounds' => $previousPoint['rounds'] + 1,
            'wins' => $previousPoint['wins'] + ($outcome === DelveOutcome::SURVIVED ? 1 : 0),
            'timeouts' => $previousPoint['timeouts'] + ($outcome === DelveOutcome::TIMEOUT ? 1 : 0),
            'enemy_strength_increase' => round(($log->increased_enemy_strength ?? 0) * 100, 2),
            'pack_size' => $log->pack_size,
            'weapon_damage' => $previousPoint['weapon_damage'] + $messageTotals['weapon_damage'],
            'spell_damage' => $previousPoint['spell_damage'] + $messageTotals['spell_damage'],
            'healing' => $previousPoint['healing'] + $messageTotals['healing_done'],
            'blocked' => $previousPoint['blocked'] + $messageTotals['damage_blocked'],
        ];
    }
}
