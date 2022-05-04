<?php

namespace App\Console\Commands;

use App\Flare\Models\GameSkill;
use App\Flare\Models\HolyStack;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\Monster;
use App\Flare\Models\Quest;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Models\SetSlot;
use App\Flare\Models\Skill;
use Illuminate\Console\Command;

class DeleteAllArtifacts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:artifacts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all artifacts from the game and affixes.';

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
        Item::where('type', 'artifact')->chunkById(150, function($artifacts) {
           foreach ($artifacts as $artifact) {
               InventorySlot::where('item_id', $artifact->id)->delete();
               SetSlot::where('item_id', $artifact->id)->delete();
               MarketBoard::where('item_id', $artifact->id)->delete();
               MarketHistory::where('item_id', $artifact->id)->delete();
               HolyStack::where('item_id', $artifact->id)->delete();

               $artifact->delete();
           }
        });

        $questItems = Item::where('skill_name', 'Artifact Crafting')->get();

        foreach ($questItems as $questItem) {

            Monster::where('quest_item_id', $questItem->id)->update([
                'quest_item_id'          => null,
                'quest_item_drop_chance' => null
            ]);

            InventorySlot::where('item_id', $questItem->id)->delete();

            $quests = Quest::where('reward_item', $questItem->id)->orWhere('item_id', $questItem->id)->orWhere('secondary_required_item', $questItem->id)->get();

            foreach ($quests as $quest) {
                QuestsCompleted::where('quest_id', $quest->id)->delete();

                $quest->delete();
            }

            $questItem->delete();
        }

        $affixes = ItemAffix::where('skill_name', 'Artifact Crafting')->get();

        foreach ($affixes as $affix) {
            Item::where('item_suffix_id', $affix->id)->update([
                'item_suffix_id' => null,
            ]);

            Item::where('item_prefix_id', $affix->id)->update([
                'item_prefix_id' => null,
            ]);
        }

        $gameSkill = GameSkill::where('name', 'Artifact Crafting')->first();

        Skill::where('game_skill_id', $gameSkill->id)->delete();

        $gameSkill->delete();
    }
}
