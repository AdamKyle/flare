<?php

namespace App\Console\Commands;

use App\Flare\Models\Item;
use Illuminate\Console\Command;

class ReBalanaceArmourClass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebalance:armour-class {type} {start} {end} {growth}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebalance Armour Class';

    /**
     * Execute the console command.
     */
    public function handle() {
        $type  = $this->argument('type');

        $validTypes = [
            'sleeves',
            'shield',
            'leggings',
            'helmet',
            'gloves',
            'feet',
            'body',
        ];

        if (!in_array($type, $validTypes)) {
            return $this->error('invalid type for armour. Accepted types are: ' . implode(', ', $validTypes));
        }

        $this->line('Updating for type: ' . $type . ' ...');

        $items = Item::where('type', $type)
                     ->whereNull('item_prefix_id')
                     ->whereNull('item_suffix_id')
                     ->where('is_mythic', false)
                     ->where('is_cosmic', false)
                     ->get();

        $curve                    = $this->generateArmourClassCurve(intVal($this->argument('start')), intVal($this->argument('end')), floatVal($this->argument('growth')), $items->count());
        $curve[0]                 = intVal($this->argument('start'));
        $curve[count($curve) - 1] = intVal($this->argument('end'));

        $tableHeaders = ['Item Name', 'New AC'];
        $tableData    = [];

        foreach ($items as $index => $item) {
            $item->update(['base_ac' => $curve[$index]]);

            $item->children()->update(['base_ac' => $curve[$index]]);

            $tableData[] = [$item->name, $item->base_ac];
        }

        $this->table($tableHeaders, $tableData);
    }

    protected function generateArmourClassCurve(int $start, int $end, float $curveStrength, int $numberOfItems): array {
        $curve       = $this->buildGeneralCurve($numberOfItems, $curveStrength, $end);
        $scaledCurve = $this->scaleTheCurve($curve, $end, $start);

        return $this->resolveRepeatingNumbers($scaledCurve);
    }

    protected function buildGeneralCurve(int $numberOfItems, float $curveStrength, int $maxValue): array {
        $curve = [];

        for ($i = 0; $i < $numberOfItems; $i++) {
            $x = $i / ($numberOfItems - 1); // Scale x from 0 to 1

            $value = $x * $maxValue * $curveStrength;

            $adjustedValue = $value * $value * $value;

            $curve[] = intVal($adjustedValue);
        }

        return $curve;
    }

    protected function scaleTheCurve(array $curve, int $end, int $start): array {
        $minValue = min($curve);
        $maxValue = max($curve);

        $scaledCurve = [];

        foreach ($curve as $value) {
            $normalizedValue = ($value - $minValue) / ($maxValue - $minValue);
            $scaledValue     = $normalizedValue * ($end - $start) + $start;
            $scaledCurve[]   = intval($scaledValue);
        }

        return $scaledCurve;
    }

    protected function resolveRepeatingNumbers(array $curve): array {
        $resolvedCurve = [];

        $previousValue = null;
        foreach ($curve as $value) {
            if ($value === $previousValue) {
                $value += 1;
            }

            $resolvedCurve[] = $value;
            $previousValue = $value;
        }

        return $resolvedCurve;
    }
}
