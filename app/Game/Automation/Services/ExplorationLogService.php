<?php

namespace App\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Flare\Models\Monster;
use App\Game\Automation\Events\ExplorationOutputUpdated;
use App\Game\Automation\Events\ExplorationWarningState;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class ExplorationLogService
{
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
    }

    public function latestForCharacter(Character $character): ?ExplorationLog
    {
        return ExplorationLog::where('character_id', $character->id)
            ->latest()
            ->first();
    }

    public function activeForCharacter(Character $character): ?ExplorationLog
    {
        return ExplorationLog::where('character_id', $character->id)
            ->whereNull('ended_at')
            ->latest()
            ->first();
    }

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
        } catch (\Throwable $throwable) {
            Log::warning('ExplorationLogService::applyRewardContext failed to broadcast exploration output.', [
                'character_id' => $character->id,
                'exception_class' => $throwable::class,
                'exception_message' => $throwable->getMessage(),
            ]);
        }
    }

    private static function addCurrencyDelta(array $currenciesGained, string $currency, int $currentValue, int $previousValue): array
    {
        $delta = max(0, $currentValue - $previousValue);

        if ($delta <= 0) {
            return $currenciesGained;
        }

        $currenciesGained[$currency] = ($currenciesGained[$currency] ?? 0) + $delta;

        return $currenciesGained;
    }

    public function outputForCharacter(Character $character): array
    {
        $output = $this->resolveOutputForCharacter($character);

        event(new ExplorationOutputUpdated($character->user, $output['type'], $output['output']));

        return $output;
    }

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

    private function broadcastOutputForCharacter(Character $character): void
    {
        $output = $this->resolveOutputForCharacter($character);

        event(new ExplorationOutputUpdated($character->user, $output['type'], $output['output']));
    }

    public function dismissEndedLog(Character $character): void
    {
        ExplorationLog::where('character_id', $character->id)
            ->whereNotNull('ended_at')
            ->whereNull('panel_dismissed_at')
            ->update(['panel_dismissed_at' => now()]);

        $this->broadcastOutputForCharacter($character);
    }

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

    private function formatMonsterStat(?array $snapshot, ?Monster $monster, string $baseAttribute, string $runtimeAttribute): mixed
    {
        return $snapshot['stats'][$runtimeAttribute]
            ?? $snapshot['stats'][$baseAttribute]
            ?? $monster?->getAttribute($runtimeAttribute)
            ?? $monster?->getAttribute($baseAttribute)
            ?? 0;
    }

    private function duration(ExplorationLog $log): int
    {
        if (is_null($log->started_at)) {
            return 0;
        }

        return (int) $log->started_at->diffInSeconds($log->ended_at ?? now());
    }
}
