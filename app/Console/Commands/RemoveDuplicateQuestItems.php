<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Game\Core\Services\CharacterService;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Illuminate\Console\Command;

class RemoveDuplicateQuestItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:duplicate-quest-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes Duplicate Quest items';

    private $toRemove = [
        [
            'to_delete' => 'Weapon Smiths Book',
            'no_duplicates'  => 'Weapon Smiths Master Book',
            'has_both'  => ['Weapon Smiths Book', 'Weapon Smiths Master Book']
        ],
        [
            'to_delete' => 'Spell Weaving Book',
            'no_duplicates'  => 'Mages Tome',
            'has_both'  => ['Spell Weaving Book', 'Mages Tome']
        ],
        [
            'to_delete' => 'Artifact Crafting Book',
            'no_duplicates'  => 'Artifact Crafting Masters Book',
            'has_both'  => ['Artifact Crafting Book', 'Artifact Crafting Masters Book']
        ],
        [
            'to_delete' => 'Blacksmiths Book',
            'no_duplicates'  => 'Black Smiths Master Recipes',
            'has_both'  => ['Blacksmiths Book', 'Black Smiths Master Recipes']
        ],
        [
            'to_delete' => 'Ring Crafters Book',
            'no_duplicates'  => 'Ring Crafters Master Book',
            'has_both'  => ['Ring Crafters Book', 'Ring Crafters Master Book']
        ],
        [
            'to_delete' => 'Enchanters Book',
            'no_duplicates'  => 'Enchantresses Diary',
            'has_both'  => ['Enchanters Book', 'Enchantresses Diary']
        ],
    ];

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
        $this->line('processing ...');

        Character::chunkById(100, function($characters) {
            foreach ($characters as $character) {
                foreach ($this->toRemove as $remove) {
                    $foundItems = $character->inventory->slots()->join('items', function ($join) use ($remove) {
                        $join->on('items.id', 'inventory_slots.item_id')
                            ->whereIn('items.name', $remove['has_both']);
                    })->select('items.*')->get();

                    $itemNames = $foundItems->pluck('name')->toArray();

                    if ($foundItems->isNotEmpty()) {
                        if (count(array_diff($itemNames, $remove['has_both'])) === 0) {
                            $character->inventory->slots()->join('items', function ($join) use ($remove) {
                                $join->on('items.id', 'inventory_slots.item_id')
                                    ->where('items.name', $remove['to_delete']);
                            })->select('inventory_slots.*')->delete();
                        }
                    }

                    $count = $character->inventory->slots()->join('items', function ($join) use ($remove) {
                        $join->on('items.id', 'inventory_slots.item_id')
                            ->where('items.name', $remove['no_duplicates']);
                    })->select('items.*')->count();

                    if ($count > 1) {
                        $character->inventory->slots()->join('items', function ($join) use ($remove) {
                            $join->on('items.id', 'inventory_slots.item_id')
                                ->where('items.name', $remove['no_duplicates']);
                        })->select('inventory_slots.*')->get()->each(function($slot, $index) {
                            if ($index !== 0) {
                                $slot->delete();
                            }
                        });
                    }
                }
            }
        });

        $this->line('done!');
    }
}
