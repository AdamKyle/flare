<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\BaseStatValue;
use App\Game\Core\Services\CharacterStatRepairService;
use App\Game\Reincarnate\Values\MaxReincarnationStats;
use Exception;
use Illuminate\Console\Command;
use Throwable;

class RepairCharacterStats extends Command
{
    protected $signature = 'characters:repair-stats {--apply} {--repair-reincarnation-bonus}';

    protected $description = 'Repairs existing character raw stats to their deterministic current-state floors.';

    private array $baseStats = ['str', 'dur', 'dex', 'chr', 'int', 'agi', 'focus'];

    public function __construct(
        private readonly CharacterStatRepairService $characterStatRepairService,
        private readonly BaseStatValue $baseStatValue,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $charactersScanned = 0;
        $charactersAffected = 0;
        $charactersChanged = 0;
        $charactersSkipped = 0;
        $totalStatPoints = 0;
        $totalReincarnationBonusGap = 0;
        $largestCorrection = 0;
        $largestCorrectionCharacter = null;
        $maxLevelConfiguration = MaxLevelConfiguration::query()->first();
        $repairReincarnationBonus = (bool) $this->option('repair-reincarnation-bonus');
        $affectedCharacters = [];
        $skippedCharacters = [];
        $statTotals = [
            'str' => 0,
            'dur' => 0,
            'dex' => 0,
            'chr' => 0,
            'int' => 0,
            'agi' => 0,
            'focus' => 0,
        ];

        Character::query()->with(['race', 'class'])->chunk(100, function ($characters) use (
            $apply,
            &$charactersScanned,
            &$charactersAffected,
            &$charactersChanged,
            &$charactersSkipped,
            &$totalStatPoints,
            &$totalReincarnationBonusGap,
            &$largestCorrection,
            &$largestCorrectionCharacter,
            $maxLevelConfiguration,
            $repairReincarnationBonus,
            &$affectedCharacters,
            &$skippedCharacters,
            &$statTotals
        ) {
            foreach ($characters as $character) {
                $charactersScanned++;

                try {
                    $repairPlan = $this->repairPlan($character);
                } catch (Throwable $throwable) {
                    $charactersSkipped++;
                    $skippedCharacters[] = [
                        'id' => $character->id,
                        'name' => $character->name,
                        'error' => $throwable->getMessage(),
                    ];

                    continue;
                }

                $reincarnationBonusGap = 0;
                $expectedReincarnatedStatIncrease = $character->reincarnated_stat_increase;
                $currentReincarnatedStatIncrease = $character->reincarnated_stat_increase;

                if ($repairReincarnationBonus && ! is_null($maxLevelConfiguration)) {
                    $minimumBonus = $this->characterStatRepairService->getMinimumReincarnationBonus($character, $maxLevelConfiguration->max_level);
                    $reincarnationBonusGap = max($minimumBonus - $character->reincarnated_stat_increase, 0);
                    $expectedReincarnatedStatIncrease = max($minimumBonus, $character->reincarnated_stat_increase);
                    $totalReincarnationBonusGap += $reincarnationBonusGap;

                    if ($apply && $reincarnationBonusGap > 0) {
                        $this->characterStatRepairService->repairReincarnationBonus($character, $maxLevelConfiguration->max_level);
                        $character = $character->refresh();
                    } elseif ($reincarnationBonusGap > 0) {
                        $character->setAttribute('reincarnated_stat_increase', $expectedReincarnatedStatIncrease);
                    }
                }

                if ($repairReincarnationBonus && $reincarnationBonusGap > 0) {
                    $repairPlan = $this->repairPlan($character);
                }

                if (empty($repairPlan)) {
                    if ($reincarnationBonusGap === 0) {
                        continue;
                    }
                }

                $charactersAffected++;
                $characterCorrection = array_sum($repairPlan);
                $statsToRepair = $this->formatStatsToRepair($repairPlan);
                $affectedCharacters[] = [
                    'character_id' => $character->id,
                    'character_name' => $character->name,
                    'level' => $character->level,
                    'times_reincarnated' => $character->times_reincarnated,
                    'current_reincarnated_stat_increase' => $currentReincarnatedStatIncrease,
                    'expected_reincarnated_stat_increase' => $expectedReincarnatedStatIncrease,
                    'reincarnation_bonus_missing' => $reincarnationBonusGap,
                    'raw_stats_missing_total' => $characterCorrection,
                    'stats_to_repair' => $statsToRepair,
                    'change' => $this->formatAffectedCharacterChange($apply, $currentReincarnatedStatIncrease, $expectedReincarnatedStatIncrease, $statsToRepair),
                ];

                if ($characterCorrection > $largestCorrection) {
                    $largestCorrection = $characterCorrection;
                    $largestCorrectionCharacter = $character;
                }

                foreach ($repairPlan as $stat => $pointsToAdd) {
                    $statTotals[$stat] += $pointsToAdd;
                    $totalStatPoints += $pointsToAdd;
                }

                if ($apply) {
                    try {
                        $this->characterStatRepairService->repair($character);
                        $charactersChanged++;
                    } catch (Throwable $throwable) {
                        $charactersSkipped++;
                        $skippedCharacters[] = [
                            'id' => $character->id,
                            'name' => $character->name,
                            'error' => $throwable->getMessage(),
                        ];
                    }
                }
            }
        });

        $this->info('Characters scanned: '.$charactersScanned);
        $this->info('Characters affected: '.$charactersAffected);
        $this->info('Characters changed: '.$charactersChanged);
        $this->info('Characters skipped: '.$charactersSkipped);
        $this->info('Total stat points to add: '.$totalStatPoints);
        $this->info('Total reincarnation bonus gap: '.$totalReincarnationBonusGap);
        $this->info('Per-stat totals:');

        foreach ($statTotals as $stat => $total) {
            $this->info($stat.': '.$total);
        }

        if (empty($affectedCharacters)) {
            $this->info('No affected characters found.');
        } else {
            $this->table([
                'character_id',
                'character_name',
                'level',
                'times_reincarnated',
                'current_reincarnated_stat_increase',
                'expected_reincarnated_stat_increase',
                'reincarnation_bonus_missing',
                'raw_stats_missing_total',
                'stats_to_repair',
                'change',
            ], $affectedCharacters);
        }

        if (! is_null($largestCorrectionCharacter)) {
            $this->info('Largest correction: '.$largestCorrection.' for character '.$largestCorrectionCharacter->id.' ('.$largestCorrectionCharacter->name.')');
        }

        foreach ($skippedCharacters as $skippedCharacter) {
            $this->error('Skipped character '.$skippedCharacter['id'].' ('.$skippedCharacter['name'].'): '.$skippedCharacter['error']);
        }

        return self::SUCCESS;
    }

    private function repairPlan(Character $character): array
    {
        if (is_null($character->race)) {
            throw new Exception('Missing race relation.');
        }

        if (is_null($character->class)) {
            throw new Exception('Missing class relation.');
        }

        $levelUps = max($character->level - 1, 0);
        $updates = [];
        $baseStatValue = $this->baseStatValue->setRace($character->race)->setClass($character->class);

        foreach ($this->baseStats as $stat) {
            $levelUpFloor = $character->damage_stat === $stat ? $levelUps * 2 : $levelUps;
            $floor = min($baseStatValue->{$stat}() + $character->reincarnated_stat_increase + $levelUpFloor, MaxReincarnationStats::MAX_STATS);

            if ($character->{$stat} < $floor) {
                $updates[$stat] = $floor - $character->{$stat};
            }
        }

        return $updates;
    }

    private function formatStatsToRepair(array $repairPlan): string
    {
        if (empty($repairPlan)) {
            return 'none';
        }

        $statsToRepair = [];

        foreach ($repairPlan as $stat => $pointsToAdd) {
            $statsToRepair[] = $stat.' +'.$pointsToAdd;
        }

        return implode(', ', $statsToRepair);
    }

    private function formatAffectedCharacterChange(
        bool $apply,
        int $currentReincarnatedStatIncrease,
        int $expectedReincarnatedStatIncrease,
        string $statsToRepair
    ): string {
        $changes = [];
        $verb = $apply ? 'fixed' : 'will change';

        if ($expectedReincarnatedStatIncrease > $currentReincarnatedStatIncrease) {
            $changes[] = 'reincarnated_stat_increase '.$currentReincarnatedStatIncrease.' -> '.$expectedReincarnatedStatIncrease;
        }

        if ($statsToRepair !== 'none') {
            $changes[] = 'raw stats: '.$statsToRepair;
        }

        if (empty($changes)) {
            return 'none';
        }

        return $verb.' '.implode('; ', $changes);
    }
}
