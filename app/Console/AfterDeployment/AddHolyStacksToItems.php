<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Item;
use App\Flare\Values\ItemSpecialtyType;
use Exception;
use Illuminate\Console\Command;

class AddHolyStacksToItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:holy-stacks-to-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds Holy Stack amounts to Items';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void {
        Item::whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->chunkById(250, function($items) {
                foreach ($items as $item) {

                    $maxStacks = 0;

                    if (!is_null($item->specialty_type)) {
                        $type = new ItemSpecialtyType($item->specialty_type);

                        if ($type->isHellForged() || $type->isPurgatoryChains() || $type->isPirateLordLeather() || $type->isCorruptedIce() || $type->isDelusionalSilver() || $type->isTwistedEarth()) {
                            $maxStacks = 20;
                        }
                    } else {
                        $maxLevel = $item->skill_level_trivial;

                        $maxStacks = ($maxLevel / 10) / 2;

                        if ($maxStacks < 1) {
                            $maxStacks = 1;
                        }

                        if ($maxStacks > 20) {
                            $maxStacks = 20;
                        }
                    }

                    $item->update([
                        'holy_stacks' => $maxStacks
                    ]);
                }
            });
    }
}
