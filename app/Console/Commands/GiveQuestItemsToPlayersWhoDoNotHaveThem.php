<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Quest;
use Illuminate\Console\Command;

class GiveQuestItemsToPlayersWhoDoNotHaveThem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'give:quest-items-to-players-who-do-not-have-them';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Character::chunkById(150, function($characters) {
            foreach ($characters as $character) {

                if (is_null($character->inventory)) {
                    continue;
                }

                $this->handOutQuestItems($character);
            }
        });
    }

    private function handOutQuestItems(Character $character): void {

        $questRewardItems = $character->questsCompleted->filter(function($completedQuest) {
            if (!is_null($completedQuest->quest)) {

                $rewardItemId = $completedQuest->quest->rewardItem;

                $foundQuestNeeding = Quest::where('item_id', $rewardItemId)->orWhere('secondary_required_item', $rewardItemId)->first();

                if (!is_null($foundQuestNeeding)) {
                    return $foundQuestNeeding;
                }
            }

            return null;
        })->pluck('quest.reward_item')->toArray();

        $itemsToGive = Item::whereIn('id', $questRewardItems)->pluck('name')->toArray();

        if (!empty($itemsToGive)) {
            dump($character->name, $itemsToGive);
        }
    }
}
