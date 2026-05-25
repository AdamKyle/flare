<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Values\BaseStatValue;
use App\Game\Core\Services\CharacterStatRepairService;
use App\Game\Reincarnate\Values\MaxReincarnationStats;
use Exception;
use Illuminate\Console\Command;
use Throwable;

class RepairCharacterStats extends Command
{
    protected $signature = 'characters:repair-stats {--apply}';

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
        $largestCorrection = 0;
        $largestCorrectionCharacter = null;
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
            &$largestCorrection,
            &$largestCorrectionCharacter,
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

                if (empty($repairPlan)) {
                    continue;
                }

                $charactersAffected++;
                $characterCorrection = array_sum($repairPlan);

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

        $this->info('Characters scanned: ' . $charactersScanned);
        $this->info('Characters affected: ' . $charactersAffected);
        $this->info('Characters changed: ' . $charactersChanged);
        $this->info('Characters skipped: ' . $charactersSkipped);
        $this->info('Total stat points to add: ' . $totalStatPoints);
        $this->info('Per-stat totals:');

        foreach ($statTotals as $stat => $total) {
            $this->info($stat . ': ' . $total);
        }

        if (! is_null($largestCorrectionCharacter)) {
            $this->info('Largest correction: ' . $largestCorrection . ' for character ' . $largestCorrectionCharacter->id . ' (' . $largestCorrectionCharacter->name . ')');
        }

        foreach ($skippedCharacters as $skippedCharacter) {
            $this->error('Skipped character ' . $skippedCharacter['id'] . ' (' . $skippedCharacter['name'] . '): ' . $skippedCharacter['error']);
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
}
