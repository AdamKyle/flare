<?php

namespace App\Console\Commands;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\SetSlot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class RemoveDuplicateItems extends Command
{

    private $skillLevelRequiredForCraftableItems = [
        1,
        5,
        7,
        10,
        12,
        15,
        16,
        18,
        20,
        23,
        25,
        27,
        29,
        31,
        33,
        35,
        37,
        40,
        41,
        44,
        46,
        48,
        50,
        52,
        56,
        63,
        69,
        76,
        82,
        88,
        96,
        104,
        110,
        116,
        123,
        132,
        141,
        149,
        157,
        165,
        173,
        181,
        189,
        195,
        201,
        209,
        222,
        236,
        250,
        265,
        278,
        288,
        305,
        317,
        330,
        344,
        360,
        375,
        389,
    ];


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:duplicate-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes duplicate items and cleans up related data.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {

        $this->cleanUpDuplicates();

        $this->cleanUpCraftableItems();

        Artisan::call('cleanup:unused-items');

        $this->info('All done.');
    }

    private function cleanUpCraftableItems(): void {
        foreach ($this->skillLevelRequiredForCraftableItems as $skillLevel) {
            Artisan::call('clean:duplicate-craftable-items '. $skillLevel);
        }
    }

    /**
     * Clean up duplicate items.
     *
     * @return void
     */
    private function cleanUpDuplicates(): void {
        $idsToRemove = [];
        $idsToCleanUp = [];

        $duplicateItems = $this->findDuplicateItems();

        foreach ($duplicateItems as $duplicate) {
            $ids = $this->extractItemIds($duplicate->ids);
            $mainItemId = min($ids);
            $ids = array_diff($ids, [$mainItemId]);

            $mainItem = Item::find($mainItemId);

            if (!$mainItem) {
                $this->warn("Skipping. Main item ID {$mainItemId} does not exist.");
                continue;
            }

            $this->info("[Duplicate items] Item Name: {$duplicate->name} has " . count($ids) . " duplicates => IDs: [" . implode(', ', $ids) . "]");

            $this->updateRecordsForItemIds($ids, $mainItemId);

            $idsToRemove = array_merge($idsToRemove, $ids);
            $idsToCleanUp[] = $mainItemId;
        }

        $this->cleanUpDuplicateItemsWithoutRelations($idsToRemove, $idsToCleanUp);
        $this->removeItems($idsToRemove);
        $this->cleanUpItems($idsToCleanUp);
    }

    /**
     * Finds items that have duplicates based on name and type.
     *
     * @return \Illuminate\Support\Collection
     */
    private function findDuplicateItems(): \Illuminate\Support\Collection
    {
        return Item::whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->whereNotIn('type', ['artifact'])
            ->select('name', 'type', DB::raw('COUNT(*) as count'), DB::raw('GROUP_CONCAT(id) as ids'))
            ->groupBy('name', 'type')
            ->having('count', '>', 1)
            ->get();
    }

    /**
     * Extracts item IDs from a comma-separated string.
     *
     * @param string $ids
     * @return array
     */
    private function extractItemIds(string $ids): array
    {
        return array_map('intval', explode(',', $ids));
    }

    /**
     * Updates various records where the item IDs are duplicates, setting them to the main item ID.
     *
     * @param array $ids
     * @param int $mainItemId
     * @return void
     */
    private function updateRecordsForItemIds(array $ids, int $mainItemId): void
    {
        $this->updateRecord(InventorySlot::class, $ids, $mainItemId);
        $this->updateRecord(SetSlot::class, $ids, $mainItemId);
        $this->updateRecord(MarketBoard::class, $ids, $mainItemId);
        $this->updateRecord(MarketHistory::class, $ids, $mainItemId);
    }

    /**
     * Updates records of a given model where item IDs are found in the provided array.
     *
     * @param string $modelClass
     * @param array $ids
     * @param int $mainItemId
     * @return void
     */
    private function updateRecord(string $modelClass, array $ids, int $mainItemId): void
    {
        if (!Item::find($mainItemId)) {
            $this->warn("Cannot update $modelClass: target item ID $mainItemId doesn't exist.");

            return;
        }

        $modelClass::whereIn('item_id', $ids)->update(['item_id' => $mainItemId]);
    }

    /**
     * Cleans up duplicate items that do not have relations.
     *
     * @param array $idsToRemove
     * @param array $idsToCleanUp
     * @return void
     */
    private function cleanUpDuplicateItemsWithoutRelations(array &$idsToRemove, array &$idsToCleanUp): void
    {
        $duplicateItems = Item::whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->whereNotIn('type', ['artifact'])
            ->whereDoesntHave('inventorySlots')
            ->whereDoesntHave('inventorySetSlots')
            ->whereDoesntHave('marketListings')
            ->whereDoesntHave('marketHistory')
            ->select('name', 'type', DB::raw('COUNT(*) as count'), DB::raw('GROUP_CONCAT(id) as ids'))
            ->groupBy('name', 'type')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicateItems as $duplicate) {
            $ids = $this->extractItemIds($duplicate->ids);
            $mainItemId = min($ids);
            $ids = array_diff($ids, [$mainItemId]);

            $this->info("Item Name: {$duplicate->name} has " . count($ids) . " duplicates => IDs: [" . implode(', ', $ids) . "]");

            $idsToRemove = array_merge($idsToRemove, $ids);
            $idsToCleanUp[] = $mainItemId;
        }
    }

    /**
     * Deletes items and their related data.
     *
     * @param array $idsToRemove
     * @return void
     */
    private function removeItems(array $idsToRemove): void
    {
        $itemsToRemove = Item::whereIn('id', $idsToRemove)->get();

        foreach ($itemsToRemove as $itemToRemove) {
            if ($itemToRemove->appliedHolyStacks->isNotEmpty()) {
                $itemToRemove->appliedHolyStacks()->delete();
            }

            if ($itemToRemove->sockets->isNotEmpty()) {
                $itemToRemove->sockets()->delete();
            }

            $this->info('Deleted: ' . $itemToRemove->affix_name);
            $itemToRemove->delete();
        }
    }

    /**
     * Cleans up items by deleting related data such as holy stacks and sockets.
     *
     * @param array $idsToCleanUp
     * @return void
     */
    private function cleanUpItems(array $idsToCleanUp): void
    {
        $itemsToCleanUp = Item::whereIn('id', $idsToCleanUp)->get();

        foreach ($itemsToCleanUp as $itemToCleanup) {
            if ($itemToCleanup->appliedHolyStacks->isNotEmpty()) {
                $itemToCleanup->appliedHolyStacks()->delete();
            }

            if ($itemToCleanup->sockets->isNotEmpty()) {
                $itemToCleanup->sockets()->delete();
            }

            $this->info('Cleaned up: ' . $itemToCleanup->affix_name);
        }
    }
}
