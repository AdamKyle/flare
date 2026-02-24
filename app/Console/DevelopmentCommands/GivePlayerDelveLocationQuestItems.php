<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Quest;
use App\Flare\Values\LocationType;
use Illuminate\Console\Command;

class GivePlayerDelveLocationQuestItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'give-player:delve-location-quest-items {characterName}';

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
        $characterName = $this->argument('characterName');

        $character = Character::where('name', $characterName)->first();

        if (is_null($character)) {
            $this->error('No character found for name: ' . $characterName);

            return;
        }

        $locationIds = Location::where('type', LocationType::CAVE_OF_MEMORIES)->pluck('id')->toArray();

        $questItems = Item::whereIn('drop_location_id', $locationIds)->where('type', 'quest')->get();

        $character->loadMissing('inventory.slots');

        $ownedItemIds = $character->inventory->slots->pluck('item_id')->all();

        $completedQuestIds = $character->questsCompleted()
            ->whereNotNull('quest_id')
            ->pluck('quest_id')
            ->all();

        $blockedItemIds = [];

        if (! empty($completedQuestIds)) {
            $blockedItemIds = Quest::query()
                ->whereIn('id', $completedQuestIds)
                ->get(['item_id', 'secondary_required_item'])
                ->flatMap(function (Quest $quest): array {
                    return [
                        $quest->item_id,
                        $quest->secondary_required_item,
                    ];
                })
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $eligibleItems = $questItems->filter(function (Item $item) use ($ownedItemIds, $blockedItemIds): bool {
            return ! in_array($item->id, $ownedItemIds, true)
                && ! in_array($item->id, $blockedItemIds, true);
        });

        if ($eligibleItems->isEmpty()) {

            $this->info('Nothing to give the player ...');

            return;
        }

        foreach ($eligibleItems as $eligibleItem) {
            $this->info('Gave: ' . $eligibleItem->name . ' to: ' . $characterName);

            $character->inventory->slots()->create([
                'item_id' => $eligibleItem->id,
            ]);
        }
    }
}
