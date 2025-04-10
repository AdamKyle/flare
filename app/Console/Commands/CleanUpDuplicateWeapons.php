<?php

namespace App\Console\Commands;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Game\Character\CharacterInventory\Values\ItemType;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CleanUpDuplicateWeapons extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:duplicate-craftable-items {minCraftingLevel=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans up all the duplicate weapons we have hanging around.';

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
     */
    public function handle(): void
    {
        $this->cleanWeapons();

        Cache::delete('crafting-table-data');

        $this->rebuildCraftingTableCache();
    }

    private function rebuildCraftingTableCache() {
        $items = Item::whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->whereNull('specialty_type')
            ->doesntHave('appliedHolyStacks')
            ->doesntHave('sockets')
            ->where('can_craft', true)
            ->get();

        Cache::put('crafting-table-data', $items);
    }

    private function cleanWeapons(): void {
        $weaponTypes = ItemType::allTypes();

        foreach ($weaponTypes as $weaponType) {
            $items = Item::whereNull('item_prefix_id')
                ->whereNull('item_suffix_id')
                ->doesntHave('appliedHolyStacks')
                ->doesnthave('sockets')
                ->where('type', $weaponType)
                ->where('skill_level_required', (int) $this->argument('minCraftingLevel'))
                ->get();

            $this->cleanUpItems($items, $weaponType);
        }
    }

    private function cleanUpItems(Collection $items, string $weaponType): void {

        if ($items->count() <= 1) {

            $this->info('Nothing to do, we only have one 1 item for item type: ' . $weaponType);

            return;
        }

        $firstItemToKeep = null;
        $originalCount = $items->count();

        foreach ($items as $item) {
            if (is_null($firstItemToKeep)) {
                $firstItemToKeep = $item;

                continue;
            }

            InventorySlot::where('item_id', $item->id)->update(['item_id' => $firstItemToKeep->id]);
            SetSlot::where('item_id', $item->id)->update(['item_id' => $firstItemToKeep->id]);

            $item->delete();
        }

        $this->info('Cleaned: ' . $originalCount . ' of type: ' . $weaponType);
    }
}
