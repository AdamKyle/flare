<?php

namespace App\Console\Commands;

use App\Flare\Models\Item;
use Illuminate\Console\Command;

class AllowAlchemyItemsToStack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'allow:alchemy-items-to-stack';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allows alchemy items that can be used on self to stack';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        Item::where('usable', true)->where('damages_kingdoms', false)->where('can_use_on_other_items', false)->update([
            'can_stack' => true,
        ]);
    }
}
