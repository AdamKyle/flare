<?php

namespace App\Console\Commands;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Flare\Values\RandomAffixDetails;
use Illuminate\Console\Command;

class FixInValidMythics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:in-valid-mythics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix invalid mythics';

    /**
     * Execute the console command.
     */
    public function handle() {
        InventorySlot::whereHas('item', function($query) {
            $query->where('is_mythic', true);
        })->chunkById(250, function($inventorySlots) {
            foreach ($inventorySlots as $slot) {

                if ($this->isValidMythic($slot->item)) {
                    continue;
                }

                $slot->item()->update([
                    'is_mythic' => false,
                    'is_cosmic' => false,
                ]);
            }
        });

        SetSlot::whereHas('item', function($query) {
            $query->where('is_mythic', true);
        })->chunkById(250, function($inventorySlots) {
            foreach ($inventorySlots as $slot) {

                if ($this->isValidMythic($slot->item)) {
                    continue;
                }

                $slot->item()->update([
                    'is_mythic' => false,
                    'is_cosmic' => false,
                ]);
            }
        });
    }

    private function isValidMythic(Item $item): bool {

        if (!is_null($item->item_prefix_id)) {
            if ($item->itemPrefix->cost !== RandomAffixDetails::MYTHIC) {
                return false;
            }
        }

        if (!is_null($item->item_suffix_id)) {
            if ($item->itemSuffix->cost !== RandomAffixDetails::MYTHIC) {
                return false;
            }
        }

        return true;
    }
}
