<?php

namespace App\Console\Commands;

use App\Flare\Models\Item;
use Illuminate\Console\Command;

/**
 * @codeCoverageIgnore
 */
class AssignParentChildRelationsToEnchantedItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:parent-child-to-enchanted-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will assign parent ids to all enchanted items, If one does not have a parent it will be deleted.';

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
        Item::whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->whereNotIn('type', ['quest', 'alchemy'])
            ->chunk(100, function($items) {
               foreach ($items as $item) {
                   $this->assignRelation($item);

                   $this->line('Updated: ' . $item->name . ' to have children...');
               }
            });

        $hangingChildren = Item::whereNotNull('item_suffix_id')
            ->whereNotNull('item_prefix_id')
            ->whereNull('parent_id')
            ->get();

        if ($hangingChildren->isNotEmpty()) {
            $this->line('You have children with no parents:');
            $this->line('==================================');
            foreach ($hangingChildren as $child) {
                $this->line($child->name);
            }
        }
    }

    public function assignRelation(Item $item) {
        Item::whereNotNull('item_prefix_id')->where('name', $item->name)->update([
            'parent_id' => $item->id
        ]);

        Item::whereNotNull('item_suffix_id')->where('name', $item->name)->update([
            'parent_id' => $item->id
        ]);
    }
}
