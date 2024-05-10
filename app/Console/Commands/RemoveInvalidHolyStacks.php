<?php

namespace App\Console\Commands;

use App\Flare\Models\Item;
use Illuminate\Console\Command;

class RemoveInvalidHolyStacks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:invalid-holy-stacks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $itemsWithExcessStacks = Item::has('appliedHolyStacks')
            ->with('appliedHolyStacks')
            ->get();

        $itemsWithExcessStacks->each(function ($item) {
            $excessStacks = $item->appliedHolyStacks->count() - $item->holy_stacks;

            if ($excessStacks > 0) {
                $item->appliedHolyStacks->sortByDesc('created_at')->each(function ($stack) use (&$excessStacks) {
                    if ($excessStacks > 0) {
                        $stack->delete();
                        $excessStacks--;
                    }
                });
            }
        });
    }
}
