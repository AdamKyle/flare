<?php

namespace App\Console\Commands;

use App\Flare\Models\Item;
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
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Item::whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereNotIn('type', ['quest', 'alchemy'])
            ->chunkById(250, function($items) {
                foreach ($items as $item) {
                    $maxLevel = $item->skill_level_trivial;

                    $maxStacks = ($maxLevel / 10) / 2;

                    if ($maxLevel <= 1) {
                        $maxStacks = 1;
                    }

                    $item->update([
                        'holy_stacks' => $maxStacks
                    ]);
                }
            });
    }
}
