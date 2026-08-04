<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CleanDuplicateQuestInventorySlots extends Command
{
    protected $signature = 'cleanup:duplicate-quest-inventory-slots {--apply : Delete duplicate quest-item inventory slots instead of dry-running}';

    protected $description = 'Finds and removes duplicate quest-item inventory slots, keeping the lowest slot ID for each inventory/item pair.';

    public function handle(): void
    {
        $apply = (bool) $this->option('apply');

        $duplicateGroups = DB::table('inventory_slots')
            ->join('items', 'items.id', '=', 'inventory_slots.item_id')
            ->where('items.type', 'quest')
            ->select('inventory_slots.inventory_id', 'inventory_slots.item_id', DB::raw('COUNT(*) as slot_count'))
            ->groupBy('inventory_slots.inventory_id', 'inventory_slots.item_id')
            ->having('slot_count', '>=', 2)
            ->get();

        if (! $apply) {
            $this->runDryRun($duplicateGroups);

            return;
        }

        $this->runApply($duplicateGroups);
    }

    /**
     * Report what would be deleted without mutating any data.
     */
    private function runDryRun(Collection $duplicateGroups): void
    {
        $extraRowCount = 0;

        foreach ($duplicateGroups as $group) {
            $extraRowCount += $group->slot_count - 1;
        }

        $this->info('[DRY RUN] Pass --apply to delete duplicate quest-item inventory slots.');
        $this->line('Duplicate quest-item groups found: '.$duplicateGroups->count());
        $this->line('Duplicate rows that would be deleted: '.$extraRowCount);
        $this->line('The lowest slot ID in each group would be retained.');
    }

    /**
     * Delete duplicate quest-item inventory slots, keeping the lowest slot ID
     * per inventory/item pair.
     */
    private function runApply(Collection $duplicateGroups): void
    {
        $totalGroupsCleaned = 0;
        $totalRowsDeleted = 0;

        foreach ($duplicateGroups as $group) {
            $inventoryId = $group->inventory_id;
            $itemId = $group->item_id;

            $inventory = Inventory::where('id', $inventoryId)->first();

            if (is_null($inventory)) {
                continue;
            }

            $slots = InventorySlot::where('inventory_id', $inventoryId)
                ->where('item_id', $itemId)
                ->whereHas('item', function ($query): void {
                    $query->where('type', 'quest');
                })
                ->orderBy('id')
                ->get();

            if ($slots->count() < 2) {
                continue;
            }

            $keeperId = $slots->first()->id;
            $duplicateSlots = $slots->slice(1);
            $deletedIds = [];

            foreach ($duplicateSlots as $duplicateSlot) {
                $deletedIds[] = $duplicateSlot->id;
                $duplicateSlot->delete();
            }

            $remainingSlots = InventorySlot::where('inventory_id', $inventoryId)
                ->where('item_id', $itemId)
                ->whereHas('item', function ($query): void {
                    $query->where('type', 'quest');
                })
                ->orderBy('id')
                ->get();

            if ($remainingSlots->count() !== 1 || $remainingSlots->first()->id !== $keeperId) {
                throw new RuntimeException(sprintf(
                    'Duplicate quest-item cleanup verification failed for inventory %d, item %d.',
                    $inventoryId,
                    $itemId
                ));
            }

            $totalGroupsCleaned++;
            $totalRowsDeleted += count($deletedIds);

            $this->line(sprintf(
                'Inventory: %d, Item: %d, Retained slot: %d, Deleted slots: %s',
                $inventoryId,
                $itemId,
                $keeperId,
                implode(', ', $deletedIds)
            ));
        }

        $this->info('Duplicate quest-item groups cleaned: '.$totalGroupsCleaned);
        $this->info('Duplicate quest-item slots deleted: '.$totalRowsDeleted);
    }
}
