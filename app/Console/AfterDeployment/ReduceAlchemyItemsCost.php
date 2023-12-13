<?php

namespace App\Console\AfterDeployment;

use App\Flare\AlchemyItemGenerator\Values\AlchemyItemType;
use App\Flare\ExponentialCurve\Curve\ExponentialAttributeCurve;
use App\Flare\Models\Item;
use Illuminate\Console\Command;

class ReduceAlchemyItemsCost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reduce:alchemy-items-cost';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'reduces the alchemy item cost';

    /**
     * Execute the console command.
     */
    public function handle(ExponentialAttributeCurve $exponentialAttributeCurve) {

        $types = AlchemyItemType::$list;

        foreach ($types as $type) {
            $items = Item::where('type', 'alchemy')->where('alchemy_type', $type)->orderBy('gold_dust_cost')->get();

            $cost = $this->generateIntegers($exponentialAttributeCurve, $items->count(), 10, 50000, 25, 100);

            $items->each(function($item, $index) use($cost) {
               $item->update([
                   'gold_dust_cost' => $cost[$index],
                   'shards_cost' => $cost[$index]
               ]);
            });
        }
    }

    protected function generateIntegers(ExponentialAttributeCurve $exponentialAttributeCurve, int $size, int $min, int $max, int $increase, int $range): array {
        $curve = $exponentialAttributeCurve->setMin($min)
            ->setMax($max)
            ->setIncrease($increase)
            ->setRange($range);

        return $curve->generateValues($size, true);
    }
}
