<?php

namespace App\Game\Automation\Exploration\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Flare\Models\Monster;
use App\Game\Automation\Exploration\Events\ExplorationOutputUpdated;
use App\Game\Automation\Exploration\Events\ExplorationWarningState;
use App\Game\Tops\Services\BroadcastTopsUpdateService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class ExplorationLogService
{
    /**
     * Create the Exploration log for a newly started automation run.
     *
     * @param Character $character The character starting Exploration.
     * @param CharacterAutomation $automation The character's Exploration automation record.
     * @return ExplorationLog The newly created Exploration log.
     */
    public function start(Character $character, CharacterAutomation $automation): ExplorationLog
    {
        $log = ExplorationLog::create([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'character_automation_id' => $automation->id,
            'monster_id' => $automation->monster_id,
            'attack_type' => $automation->attack_type,
            'starting_level' => $character->level,
            'started_at' => now(),
            'stopped_reason' => 'running',
        ]);

        $this->broadcastOutputForCharacter($character);

        return $log;
    }

    /**
     * Accumulate fight/kill/damage/currency totals onto the active Exploration log.
     *
     * @param ExplorationLog $log The Exploration log to update.
     * @param array $totals The round's fight/kill/damage/currency totals.
     * @param bool $broadcast Whether to broadcast the updated output.
     * @return void This method does not return a value.
     */
    public function recordFightTotals(ExplorationLog $log, array $totals, bool $broadcast = true): void
    {
        $log->refresh();

        $currenciesGained = $log->currencies_gained ?? [];

        foreach ($totals['currencies_gained'] ?? [] as $currency => $amount) {
            $currenciesGained[$currency] = ($currenciesGained[$currency] ?? 0) + $amount;
        }

        if (isset($totals['healing_done'])) {
            $currenciesGained['healing_done'] = ($currenciesGained['healing_done'] ?? 0) + $totals['healing_done'];
        }

        if (isset($totals['damage_blocked'])) {
            $currenciesGained['damage_blocked'] = ($currenciesGained['damage_blocked'] ?? 0) + $totals['damage_blocked'];
        }

        $updates = [
            'fights' => $log->fights + ($totals['fights'] ?? 0),
            'kills' => $log->kills + ($totals['kills'] ?? 0),
            'weapon_damage' => $log->weapon_damage + ($totals['weapon_damage'] ?? 0),
            'spell_damage' => $log->spell_damage + ($totals['spell_damage'] ?? 0),
            'xp_gained' => $log->xp_gained + ($totals['xp_gained'] ?? 0),
            'skill_xp_gained' => $log->skill_xp_gained + ($totals['skill_xp_gained'] ?? 0),
            'faction_points_gained' => $log->faction_points_gained + ($totals['faction_points_gained'] ?? 0),
            'currencies_gained' => $currenciesGained,
        ];

        if (isset($totals['monster']) && is_array($totals['monster'])) {
            $summary = $log->summary ?? [];
            $summary['monster'] = $totals['monster'];
            $updates['summary'] = $summary;
        }

        $log->update($updates);

        if ($broadcast) {
            $this->broadcastOutputForCharacter($log->character);
        }
    }

    /**
     * Store the current monster's stat snapshot on the Exploration log summary.
     *
     * @param ExplorationLog $log The Exploration log to update.
     * @param array $monster The monster snapshot to record.
     * @param bool $broadcast Whether to broadcast the updated output.
     * @return void This method does not return a value.
     */
    public function recordMonsterSnapshot(ExplorationLog $log, array $monster, bool $broadcast = true): void
    {
        $log->refresh();

        $summary = $log->summary ?? [];
        $summary['monster'] = $monster;

        $log->update([
            'summary' => $summary,
        ]);

        if ($broadcast) {
            $this->broadcastOutputForCharacter($log->character);
        }
    }

    /**
     * Store the current round's creature count on the Exploration log summary.
     *
     * @param ExplorationLog $log The Exploration log to update.
     * @param int $currentRoundCreatures The number of creatures in the current round.
     * @param bool $broadcast Whether to broadcast the updated output.
     * @return void This method does not return a value.
     */
    public function recordCurrentRoundCreatures(ExplorationLog $log, int $currentRoundCreatures, bool $broadcast = true): void
    {
        $log->refresh();

        $summary = $log->summary ?? [];
        $summary['current_round_creatures'] = $currentRoundCreatures;

        $log->update([
            'summary' => $summary,
        ]);

        if ($broadcast) {
            $this->broadcastOutputForCharacter($log->character);
        }
    }

    /**
     * End the Exploration log with its final summary and stop reason.
     *
     * @param ExplorationLog $log The Exploration log to finalize.
     * @param string|null $stoppedReason The reason Exploration ended.
     * @param bool $stoppedByPlayer Whether the player manually stopped Exploration.
     * @return void This method does not return a value.
     */
    public function finalize(ExplorationLog $log, ?string $stoppedReason = null, bool $stoppedByPlayer = false): void
    {
        $log->refresh();
        $summary = $log->summary ?? [];
        $summary = [
            ...$summary,
            'fights' => $log->fights,
            'kills' => $log->kills,
            'weapon_damage' => $log->weapon_damage,
            'spell_damage' => $log->spell_damage,
            'xp_gained' => $log->xp_gained,
            'skill_xp_gained' => $log->skill_xp_gained,
            'faction_points_gained' => $log->faction_points_gained,
            'currencies_gained' => $log->currencies_gained ?? [],
        ];

        $log->update([
            'ended_at' => now(),
            'stopped_reason' => $stoppedReason,
            'stopped_by_player' => $stoppedByPlayer,
            'summary' => $summary,
        ]);

        $this->broadcastOutputForCharacter($log->character);
        BroadcastTopsUpdateService::make()->broadcastExplorationCurrentMonth();
    }

    /**
     * Return the character's most recent Exploration log.
     *
     * @param Character $character The character to look up.
     * @return ExplorationLog|null The character's most recent Exploration log, if any.
     */
    public function latestForCharacter(Character $character): ?ExplorationLog
    {
        return ExplorationLog::where('character_id', $character->id)
            ->latest()
            ->first();
    }

    /**
     * Return the character's currently active (unended) Exploration log.
     *
     * @param Character $character The character to look up.
     * @return ExplorationLog|null The character's active Exploration log, if any.
     */
    public function activeForCharacter(Character $character): ?ExplorationLog
    {
        return ExplorationLog::where('character_id', $character->id)
            ->whereNull('ended_at')
            ->latest()
            ->first();
    }

    /**
     * Apply post-reward currency/xp deltas to the Exploration log and broadcast the updated output.
     *
     * @param ExplorationLog $log The Exploration log to update.
     * @param Character $character The character receiving rewards.
     * @param array $beforeSnapshot The character's currency/level values before rewards were applied.
     * @param array $context The xp/skill xp/faction point totals awarded.
     * @return void This method does not return a value.
     */
    public static function applyRewardContext(
        ExplorationLog $log,
        Character $character,
        array $beforeSnapshot,
        array $context
    ): void {
        $log->refresh();

        $currenciesGained = $log->currencies_gained ?? [];
        $currenciesGained = self::addCurrencyDelta($currenciesGained, 'gold', $character->gold, $beforeSnapshot['gold'] ?? 0);
        $currenciesGained = self::addCurrencyDelta($currenciesGained, 'gold_dust', $character->gold_dust, $beforeSnapshot['gold_dust'] ?? 0);
        $currenciesGained = self::addCurrencyDelta($currenciesGained, 'shards', $character->shards, $beforeSnapshot['shards'] ?? 0);
        $currenciesGained = self::addCurrencyDelta($currenciesGained, 'copper_coins', $character->copper_coins, $beforeSnapshot['copper_coins'] ?? 0);
        $currenciesGained = self::addCurrencyDelta($currenciesGained, 'levels_gained', $character->level, $beforeSnapshot['level'] ?? 0);

        $log->update([
            'xp_gained' => $log->xp_gained + ($context['total_xp'] ?? 0),
            'skill_xp_gained' => $log->skill_xp_gained + ($context['total_skill_xp'] ?? 0),
            'faction_points_gained' => $log->faction_points_gained + ($context['total_faction_points'] ?? 0),
            'currencies_gained' => $currenciesGained,
        ]);

        try {
            event(new ExplorationWarningState($character->user, false, []));
        } catch (\Throwable $throwable) {
            Log::warning('ExplorationLogService::applyRewardContext failed to broadcast ExplorationWarningState.', [
                'character_id' => $character->id,
                'exception_class' => $throwable::class,
                'exception_message' => $throwable->getMessage(),
            ]);
        }

        try {
            (new self)->broadcastOutputForCharacter($character);
            BroadcastTopsUpdateService::make()->broadcastExplorationCurrentMonth();
        } catch (\Throwable $throwable) {
            Log::warning('ExplorationLogService::applyRewardContext failed to broadcast exploration output.', [
                'character_id' => $character->id,
                'exception_class' => $throwable::class,
                'exception_message' => $throwable->getMessage(),
            ]);
        }
    }

    /**
     * Add the positive difference between the current and previous currency values to the totals.
     *
     * @param array $currenciesGained The currency totals to update.
     * @param string $currency The currency key being updated.
     * @param int $currentValue The character's current currency value.
     * @param int $previousValue The character's currency value before the reward.
     * @return array The updated currency totals.
     */
    private static function addCurrencyDelta(array $currenciesGained, string $currency, int $currentValue, int $previousValue): array
    {
        $delta = max(0, $currentValue - $previousValue);

        if ($delta <= 0) {
            return $currenciesGained;
        }

        $currenciesGained[$currency] = ($currenciesGained[$currency] ?? 0) + $delta;

        return $currenciesGained;
    }

    /**
     * Return and broadcast the character's current Exploration output panel.
     *
     * @param Character $character The character to resolve output for.
     * @return array The current Exploration output panel.
     */
    public function outputForCharacter(Character $character): array
    {
        $output = $this->resolveOutputForCharacter($character);

        event(new ExplorationOutputUpdated($character->user, $output['type'], $output['output']));

        return $output;
    }

    /**
     * Clear the character's active Exploration log or dismiss a specific warning.
     *
     * @param Character $character The character to clear output for.
     * @param ExplorationWarning|null $warning The specific warning to dismiss, or null to clear the active log.
     * @return void This method does not return a value.
     */
    public function clear(Character $character, ?ExplorationWarning $warning = null): void
    {
        if (is_null($warning)) {
            ExplorationLog::where('character_id', $character->id)
                ->whereNull('ended_at')
                ->delete();

            $this->broadcastOutputForCharacter($character);

            return;
        }

        $warning->update([
            'dismissed_at' => now(),
        ]);

        if (! is_null($warning->exploration_log_id)) {
            ExplorationLog::where('id', $warning->exploration_log_id)
                ->whereNull('panel_dismissed_at')
                ->update(['panel_dismissed_at' => now()]);
        }

        $this->broadcastOutputForCharacter($character);
    }

    /**
     * Broadcast the character's current Exploration output panel.
     *
     * @param Character $character The character to broadcast output for.
     * @return void This method does not return a value.
     */
    private function broadcastOutputForCharacter(Character $character): void
    {
        $output = $this->resolveOutputForCharacter($character);

        event(new ExplorationOutputUpdated($character->user, $output['type'], $output['output']));
    }

    /**
     * Dismiss the character's ended Exploration log panel.
     *
     * @param Character $character The character dismissing the ended log.
     * @return void This method does not return a value.
     */
    public function dismissEndedLog(Character $character): void
    {
        ExplorationLog::where('character_id', $character->id)
            ->whereNotNull('ended_at')
            ->whereNull('panel_dismissed_at')
            ->update(['panel_dismissed_at' => now()]);

        $this->broadcastOutputForCharacter($character);
    }

    /**
     * Resolve the character's current Exploration output panel: active log, warning, or ended log.
     *
     * @param Character $character The character to resolve output for.
     * @return array The resolved Exploration output panel.
     */
    private function resolveOutputForCharacter(Character $character): array
    {
        $activeLog = ExplorationLog::where('character_id', $character->id)
            ->whereNull('ended_at')
            ->latest()
            ->first();

        if (! is_null($activeLog)) {
            $automation = CharacterAutomation::where('id', $activeLog->character_automation_id)
                ->where('character_id', $character->id)
                ->first();

            if (is_null($automation)) {
                Log::error('Exploration log found active with no matching automation. Repairing.', [
                    'character_id' => $character->id,
                    'exploration_log_id' => $activeLog->id,
                    'character_automation_id' => $activeLog->character_automation_id,
                ]);

                $activeLog->update([
                    'ended_at' => now(),
                    'stopped_reason' => 'missing_automation',
                ]);

                try {
                    ExplorationWarning::create([
                        'character_id' => $character->id,
                        'user_id' => $character->user_id,
                        'exploration_log_id' => $activeLog->id,
                        'type' => 'missing_automation',
                        'message' => 'Exploration ended because the automation was missing. Please report this as a bug.',
                    ]);
                } catch (QueryException $exception) {
                    $exceptionCode = (int) $exception->getCode();
                    $previousCode = (int) ($exception->getPrevious()?->getCode() ?? 0);
                    $isRetryable = in_array($exceptionCode, [1205, 1213], true)
                        || in_array($previousCode, [1205, 1213], true)
                        || str_contains($exception->getMessage(), '1205')
                        || str_contains($exception->getMessage(), '1213')
                        || str_contains($exception->getMessage(), 'Lock wait timeout exceeded')
                        || str_contains($exception->getMessage(), 'Deadlock found');

                    if (! $isRetryable) {
                        throw $exception;
                    }

                    Log::warning('Exploration log repair skipped warning creation after database lock error.', [
                        'character_id' => $character->id,
                        'exploration_log_id' => $activeLog->id,
                        'exception_code' => $exceptionCode !== 0 ? $exceptionCode : $previousCode,
                    ]);
                }
            } else {
                return ['type' => 'active', 'output' => $this->formatLogOutput($activeLog)];
            }
        }

        $warning = ExplorationWarning::where('character_id', $character->id)
            ->whereNull('dismissed_at')
            ->latest()
            ->first();

        if (! is_null($warning)) {
            return ['type' => 'warning', 'output' => $this->formatWarningOutput($warning)];
        }

        $endedLog = ExplorationLog::where('character_id', $character->id)
            ->whereNotNull('ended_at')
            ->whereNull('panel_dismissed_at')
            ->latest('ended_at')
            ->first();

        if (! is_null($endedLog)) {
            return ['type' => 'ended', 'output' => $this->formatLogOutput($endedLog)];
        }

        return ['type' => null, 'output' => null];
    }

    /**
     * Format an Exploration warning into its output panel shape.
     *
     * @param ExplorationWarning $warning The warning to format.
     * @return array The formatted warning output panel.
     */
    private function formatWarningOutput(ExplorationWarning $warning): array
    {
        $logOutput = ! is_null($warning->explorationLog)
            ? $this->formatLogOutput($warning->explorationLog)
            : $this->emptyOutput();

        return [
            ...$logOutput,
            'id' => $warning->id,
            'character_id' => $warning->character_id,
            'user_id' => $warning->user_id,
            'exploration_log_id' => $warning->exploration_log_id,
            'type' => $warning->type,
            'reason' => $warning->type,
            'message' => $warning->message,
        ];
    }

    /**
     * Format an Exploration log into its output panel shape.
     *
     * @param ExplorationLog $log The log to format.
     * @return array The formatted log output panel.
     */
    private function formatLogOutput(ExplorationLog $log): array
    {
        $currencies = $log->currencies_gained ?? [];

        if (is_null($log->ended_at) && ! is_null($log->starting_level)) {
            $currencies['levels_gained'] = max(0, $log->character()->value('level') - $log->starting_level);
        }

        $monster = Monster::find($log->monster_id);
        $summary = $log->summary ?? [];
        $monsterSnapshot = is_array($summary['monster'] ?? null) ? $summary['monster'] : null;

        return [
            'id' => $log->id,
            'character_id' => $log->character_id,
            'user_id' => $log->user_id,
            'character_automation_id' => $log->character_automation_id,
            'monster_id' => $log->monster_id,
            'attack_type' => $log->attack_type,
            'started_at' => $log->started_at,
            'ended_at' => $log->ended_at,
            'stopped_reason' => $log->stopped_reason,
            'stopped_by_player' => $log->stopped_by_player,
            'fights' => $log->fights,
            'kills' => $log->kills,
            'weapon_damage' => $log->weapon_damage,
            'spell_damage' => $log->spell_damage,
            'xp_gained' => $log->xp_gained,
            'skill_xp_gained' => $log->skill_xp_gained,
            'faction_points_gained' => $log->faction_points_gained,
            'currencies_gained' => $currencies,
            'summary' => $log->summary,
            'current_round_creatures' => $summary['current_round_creatures'] ?? 0,
            'monster' => $this->formatMonster($monster, $log->monster_id, $monsterSnapshot),
            'totals' => [
                'fights' => $log->fights,
                'kills' => $log->kills,
                'xp' => $log->xp_gained,
                'skill_xp' => $log->skill_xp_gained,
                'faction_points' => $log->faction_points_gained,
            ],
            'currencies' => $currencies,
            'damage' => [
                'weapon' => $log->weapon_damage,
                'spell' => $log->spell_damage,
            ],
            'healing' => $currencies['healing_done'] ?? 0,
            'blocked' => $currencies['damage_blocked'] ?? 0,
            'duration' => $this->duration($log),
            'reason' => $log->stopped_reason,
            'message' => is_null($log->ended_at) ? 'Exploration is running.' : 'Exploration ended.',
        ];
    }

    /**
     * Return the empty default Exploration output panel shape.
     *
     * @return array The empty default output panel.
     */
    private function emptyOutput(): array
    {
        return [
            'monster' => null,
            'totals' => [
                'fights' => 0,
                'kills' => 0,
                'xp' => 0,
                'skill_xp' => 0,
                'faction_points' => 0,
            ],
            'currencies' => [],
            'damage' => [
                'weapon' => 0,
                'spell' => 0,
            ],
            'healing' => 0,
            'blocked' => 0,
            'duration' => 0,
            'current_round_creatures' => 0,
        ];
    }

    /**
     * Format the current monster's display stats, preferring the log snapshot over live model data.
     *
     * @param Monster|null $monster The base monster model, if it still exists.
     * @param int $monsterId The monster id recorded on the log.
     * @param array|null $snapshot The recorded monster snapshot, if any.
     * @return array The formatted monster display data.
     */
    private function formatMonster(?Monster $monster, int $monsterId, ?array $snapshot = null): array
    {
        $stats = [
            'str' => $snapshot['stats']['str'] ?? $monster?->str ?? 0,
            'dur' => $snapshot['stats']['dur'] ?? $monster?->dur ?? 0,
            'dex' => $snapshot['stats']['dex'] ?? $monster?->dex ?? 0,
            'chr' => $snapshot['stats']['chr'] ?? $monster?->chr ?? 0,
            'int' => $snapshot['stats']['int'] ?? $monster?->int ?? 0,
            'agi' => $snapshot['stats']['agi'] ?? $monster?->agi ?? 0,
            'focus' => $snapshot['stats']['focus'] ?? $monster?->focus ?? 0,
            'ac' => $snapshot['stats']['ac'] ?? $monster?->ac ?? 0,
            'health_range' => $this->formatMonsterStat($snapshot, $monster, 'health_range', 'health'),
            'attack_range' => $this->formatMonsterStat($snapshot, $monster, 'attack_range', 'attack_damage'),
            'max_spell_damage' => $this->formatMonsterStat($snapshot, $monster, 'max_spell_damage', 'spell_damage'),
            'healing_percentage' => $this->formatMonsterStat($snapshot, $monster, 'healing_percentage', 'healing'),
            'xp' => $snapshot['stats']['xp'] ?? $monster?->xp ?? 0,
            'gold' => $snapshot['stats']['gold'] ?? $monster?->gold ?? 0,
            'max_level' => $snapshot['stats']['max_level'] ?? $monster?->max_level ?? 0,
        ];

        if (isset($snapshot['stats']['health'])) {
            $stats['health'] = $snapshot['stats']['health'];
        }

        if (isset($snapshot['stats']['attack_damage'])) {
            $stats['attack_damage'] = $snapshot['stats']['attack_damage'];
        }

        return [
            'id' => $snapshot['id'] ?? $monsterId,
            'name' => $snapshot['name'] ?? $monster?->name,
            'link' => $snapshot['link'] ?? '/monsters/'.$monsterId,
            'stats' => $stats,
        ];
    }

    /**
     * Resolve a single monster stat, preferring the log snapshot over live model data.
     *
     * @param array|null $snapshot The recorded monster snapshot, if any.
     * @param Monster|null $monster The base monster model, if it still exists.
     * @param string $baseAttribute The monster model attribute name.
     * @param string $runtimeAttribute The snapshot's runtime attribute key.
     * @return mixed The resolved stat value.
     */
    private function formatMonsterStat(?array $snapshot, ?Monster $monster, string $baseAttribute, string $runtimeAttribute): mixed
    {
        return $snapshot['stats'][$runtimeAttribute]
            ?? $snapshot['stats'][$baseAttribute]
            ?? $monster?->getAttribute($runtimeAttribute)
            ?? $monster?->getAttribute($baseAttribute)
            ?? 0;
    }

    /**
     * Calculate the Exploration log's elapsed duration in seconds.
     *
     * @param ExplorationLog $log The log to measure.
     * @return int The elapsed duration in seconds.
     */
    private function duration(ExplorationLog $log): int
    {
        return (int) $log->started_at->diffInSeconds($log->ended_at ?? now());
    }
}
