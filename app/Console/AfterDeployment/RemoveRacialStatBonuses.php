<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Game\Character\CharacterCreation\Calculators\BaseStatCalculator;
use App\Game\Core\Values\CoreStatType;
use App\Game\Reincarnate\Values\MaxReincarnationStats;
use Illuminate\Console\Command;

class RemoveRacialStatBonuses extends Command
{
    /**
     * @var string
     */
    protected $signature = 'remove:racial-stat-bonuses';

    /**
     * @var string
     */
    protected $description = 'Recalculate Character base stats using the Class-only formula after Race stat modifiers were removed.';

    /**
     * @param BaseStatCalculator $baseStatCalculator Race-neutral, Class-only base stat calculator.
     */
    public function __construct(
        private readonly BaseStatCalculator $baseStatCalculator,
    ) {
        parent::__construct();
    }

    /**
     * Recalculate every non-exempt Character's base stats, preserving earned reincarnation history.
     *
     * @return int Command exit status.
     */
    public function handle(): int
    {
        $processed = 0;
        $changed = 0;
        $exempt = 0;

        Character::with('class')->chunkById(200, function ($characters) use (&$processed, &$changed, &$exempt): void {
            foreach ($characters as $character) {
                $processed++;

                if ($this->isExempt($character)) {
                    $exempt++;

                    continue;
                }

                if ($this->recalculate($character)) {
                    $changed++;
                }
            }
        });

        $this->info($processed.' Characters processed, '.$changed.' updated, '.$exempt.' exempt.');

        return self::SUCCESS;
    }

    /**
     * Determine whether the Character is exempt from recalculation: level 5,000 with every base
     * stat already at or above the reincarnation maximum.
     *
     * @param Character $character Character to evaluate.
     * @return bool Whether the Character is exempt.
     */
    private function isExempt(Character $character): bool
    {
        if ($character->level !== 5000) {
            return false;
        }

        foreach (CoreStatType::cases() as $stat) {
            if ($character->{$stat->value} < MaxReincarnationStats::MAX_STATS) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recalculate the Character's base stats using the Class-only formula, preserving earned
     * reincarnation history, and persist any changed values.
     *
     * @param Character $character Character to recalculate.
     * @return bool Whether any base stat changed.
     */
    private function recalculate(Character $character): bool
    {
        $baseStat = $this->baseStatCalculator->setClass($character->class);
        $levelUps = max($character->level - 1, 0);
        $updates = [];

        foreach (CoreStatType::cases() as $stat) {
            $levelUpBonus = $character->damage_stat === $stat->value ? $levelUps * 2 : $levelUps;
            $recalculated = min(
                $baseStat->{$stat->value}() + $character->reincarnated_stat_increase + $levelUpBonus,
                MaxReincarnationStats::MAX_STATS,
            );

            if ($character->{$stat->value} !== $recalculated) {
                $updates[$stat->value] = $recalculated;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $character->update($updates);

        return true;
    }
}
