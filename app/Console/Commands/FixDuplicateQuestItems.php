<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use Illuminate\Console\Command;

class FixDuplicateQuestItems extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:duplicate-quest-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes Duplicate Quest Items';

    /**
     * Execute the console command.
     */
    public function handle() {
        Character::chunkById(50, function ($characters) {
            foreach ($characters as $character) {

                if (is_null($character->inventory)) {
                    continue;
                }

                $questItems = $character->inventory->slots
                    ->where('item.type', 'quest')
                    ->pluck('item.name')
                    ->toArray();

                $duplicateItems = array_unique(array_diff_assoc($questItems, array_unique($questItems)));

                if (!empty($duplicateItems)) {
                    $itemIds = Item::whereIn('name', $duplicateItems)->pluck('id')->toArray();

                    $character->inventory->slots()->whereIn('item_id', $itemIds)->delete();

                    $this->line('Deleted Duplicate Quest Items for: ' . $character->name);
                }
            }
        });
    }
}
