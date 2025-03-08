<?php

namespace App\Console\AfterDeployment;


use App\Flare\Models\Item;
use Illuminate\Console\Command;

class UpdateWeaponsAndArmourWithNewStats extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:weapons-and-armour-with-new-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates weapons and armour with new stats';

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
        Item::whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->chunkById(100, function ($items): void {
                foreach ($items as $item) {
                    $this->updateItemsOfTheSameType($item);
                }
            });
    }

    private function updateItemsOfTheSameType(Item $item): void {

        Item::where('name', $item->name)
            ->where(function ($query) {
                $query->whereNotNull('item_suffix_id')
                    ->orWhereNotNull('item_prefix_id');
            })
            ->update([
                'base_damage' => $item->base_damage,
                'base_ac' => $item->base_ac,
                'base_healing' => $item->base_healing,
                'cost' => $item->cost,
                'gold_dust_cost' => $item->gold_dust_cost,
                'shards_cost' => $item->shards_cost,
                'copper_coin_cost' => $item->copper_coin_cost,
                'base_damage_mod' => $item->base_damage_mod,
                'base_healing_mod' => $item->base_healing_mod,
                'base_ac_mod' => $item->base_ac_mod,
                'str_mod' => $item->str_mod,
                'dur_mod' => $item->dur_mod,
                'dex_mod' => $item->dex_mod,
                'chr_mod' => $item->chr_mod,
                'int_mod' => $item->int_mod,
                'agi_mod' => $item->agi_mod,
                'focus_mod' => $item->focus_mod,
                'skill_level_required' => $item->skill_level_required,
                'skill_level_trivial' => $item->skill_level_trivial,
            ]);
    }
}
