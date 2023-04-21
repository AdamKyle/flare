<?php

namespace App\Console\Commands;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Flare\Values\ItemSpecialtyType;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class CleanUpSpecialtyShops extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean-up:specialty-shops';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up specialty shops.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $hellForgedItems = Item::where('specialty_type', ItemSpecialtyType::HELL_FORGED)
                                ->whereNull('item_suffix_id')
                                ->whereNull('item_prefix_id')
                                ->doesntHave('appliedHolyStacks')
                                ->get();

        $purgatoryItems = Item::where('specialty_type', ItemSpecialtyType::PURGATORY_CHAINS)
                              ->whereNull('item_suffix_id')
                              ->whereNull('item_prefix_id')
                              ->doesntHave('appliedHolyStacks')
                              ->get();

        $this->handleSpecialtyItems($hellForgedItems);
        $this->handleSpecialtyItems($purgatoryItems);
    }

    /**
     * Handle the specialty type items.
     *
     * @param Collection $specialtyItems
     * @return void
     */
    protected function handleSpecialtyItems(Collection $specialtyItems): void {
        foreach ($specialtyItems as $specialtyItem) {
            $duplicates = $this->findDuplicates($specialtyItem);

            if (!is_null($duplicates)) {
                $this->handleDuplicates($duplicates);
            }
        }
    }

    /**
     * Find duplicates.
     *
     * @param Item $specialtyItem
     * @return Collection|null
     */
    protected function findDuplicates(Item $specialtyItem): ?Collection {
        $duplicates = Item::where('name', $specialtyItem->name)
                          ->where('specialty_type', $specialtyItem->specialty_type)
                          ->whereNull('item_suffix_id')
                          ->whereNull('item_prefix_id')
                          ->doesntHave('appliedHolyStacks')
                          ->orderBy('id', 'asc')
                          ->get();

        if ($duplicates->count() > 1) {
            return $duplicates;
        }

        return null;
    }

    /**
     * Handle removing duplicates and replacing with one item.
     *
     * @param Collection $duplicates
     * @return void
     */
    protected function handleDuplicates(Collection $duplicates) {
        // Keep one of the items.
        $specialtyItemToKeep = $duplicates->shift();

        foreach ($duplicates as $duplicate) {
            $inventorySlots = InventorySlot::where('item_id', $duplicate->id)->get();

            if ($inventorySlots->isNotEmpty()) {
                $this->replaceItem($specialtyItemToKeep, $inventorySlots);
            }

            $inventorySetSlots = SetSlot::where('item_id', $duplicate->id)->get();

            if ($inventorySetSlots->isNotEmpty()) {
                $this->replaceItem($specialtyItemToKeep, $inventorySetSlots);
            }

            $this->line('Possibly Replaced: ' . $duplicate->name . ' with original in either inventory or inventory slot.');
            $this->line('Deleted: ' . $duplicate->name);

            $duplicate->delete();
        }
    }

    /**
     * Replace the specialty item.
     *
     * @param Item $specialtyItem
     * @param Collection $slots
     * @return void
     */
    protected function replaceItem(Item $specialtyItem, Collection $slots): void {
        foreach ($slots as $slot) {
            $slot->update([
                'item_id' => $specialtyItem->id,
            ]);
        }
    }
}
