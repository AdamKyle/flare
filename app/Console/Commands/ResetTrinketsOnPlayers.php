<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Game\CharacterInventory\Services\InventorySetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ResetTrinketsOnPlayers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:trinkets-on-players';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Puts all trinkets back into the characters inventory.';

    /**
     * Execute the console command.
     */
    public function handle(InventorySetService $inventorySetService) {

        ini_set('memory_limit', -1);

        Character::chunkById(100, function($characters) use ($inventorySetService) {
            foreach ($characters as $character) {

                if (is_null($character->inventory)) {
                    continue;
                }

                $slots = $character->inventory->slots()->where('equipped', true)->whereHas('item', function ($query) {
                    $query->where('type', 'trinket');
                });

                $slotsCount = $slots->count();

                if ($slotsCount === 0) {
                    $inventorySets = $character->inventorySets()
                        ->whereHas('slots', function ($query) {
                            $query->whereHas('item', function ($innerQuery) {
                                $innerQuery->where('type', 'trinket');
                            });
                        })
                        ->get();

                    if ($inventorySets->isNotEmpty()) {
                        foreach ($inventorySets as $inventorySet) {
                            $trinketItems = $inventorySet->slots->filter(function ($slot) {
                                return $slot->item->type === 'trinket';
                            })->pluck('item');

                            foreach ($trinketItems as $item) {
                                $inventorySetService->removeItemFromInventorySet($inventorySet, $item, true);
                            }
                        }
                    }
                }

                if ($slotsCount > 0) {
                    $slots->update(['equipped' => false]);
                }
            }
        });

        Artisan::call('create:character-attack-data');

    }
}
