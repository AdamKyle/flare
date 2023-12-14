<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Quest;
use App\Flare\Values\MapNameValue;
use Illuminate\Console\Command;

class RebalanceQuestCurrencyCostsAndRewards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebalance:quest-currency-costs-and-rewards';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebalance Quest Currency Costs and Rewards';

    /**
     * Execute the console command.
     */
    public function handle() {
        $this->handleSurfaceQuests();
        $this->handleLabyrinthQuests();
        $this->handleShadowPlanesQuests();
        $this->handleHellPlanesQuests();

        $this->handleIcePlaneQuests();
    }

    public function handleSurfaceQuests() {
        $quests = Quest::whereHas('npc.gameMap', function($query) {
            $query->where('name', MapNameValue::SURFACE);
        })->get();

        $updates = [];

        foreach ($quests as $quest) {

            if (!is_null($quest->gold_cost)) {
                $updates['gold_cost'] = 1000;
            }

            if (!is_null($quest->gold_dust_cost)) {
                $updates['gold_dust_cost'] = 100;
            }

            if (!is_null($quest->shards_cost)) {
                $updates['shards_cost'] = 10;
            }

            if (!is_null($quest->copper_coins_cost)) {
                $updates['copper_coins_cost'] = 100;
            }

            if (!is_null($quest->reward_gold)) {
                $updates['reward_gold'] = 150;
            }

            if (!is_null($quest->reward_gold_dust)) {
                $updates['reward_gold_dust'] = 25;
            }

            if (!is_null($quest->reward_shards)) {
                $updates['reward_shards'] = 2;
            }

            if (!is_null($quest->reward_copper_coins)) {
                $updates['reward_copper_coins'] = 10;
            }

            $quest->update($updates);

            $updates = [];
        }
    }

    public function handleLabyrinthQuests() {
        $quests = Quest::whereHas('npc.gameMap', function($query) {
            $query->where('name', MapNameValue::LABYRINTH);
        })->where('name', '!=', 'Reach for the stars')->get();

        $updates = [];

        foreach ($quests as $quest) {

            if (!is_null($quest->gold_cost)) {
                $updates['gold_cost'] = 2500;
            }

            if (!is_null($quest->gold_dust_cost)) {
                $updates['gold_dust_cost'] = 500;
            }

            if (!is_null($quest->shards_cost)) {
                $updates['shards_cost'] = 10;
            }

            if (!is_null($quest->copper_coins_cost)) {
                $updates['copper_coins_cost'] = 500;
            }

            if (!is_null($quest->reward_gold)) {
                $updates['reward_gold'] = 250;
            }

            if (!is_null($quest->reward_gold_dust)) {
                $updates['reward_gold_dust'] = 150;
            }

            if (!is_null($quest->reward_shards)) {
                $updates['reward_shards'] = 2;
            }

            if (!is_null($quest->reward_copper_coins)) {
                $updates['reward_copper_coins'] = 75;
            }

            $quest->update($updates);

            $updates = [];
        }
    }

    public function handleDungeonQuests() {
        $quests = Quest::whereHas('npc.gameMap', function($query) {
            $query->where('name', MapNameValue::DUNGEONS);
        })->get();

        $updates = [];

        foreach ($quests as $quest) {

            if (!is_null($quest->gold_cost)) {
                $updates['gold_cost'] = 10000;
            }

            if (!is_null($quest->gold_dust_cost)) {
                $updates['gold_dust_cost'] = 1000;
            }

            if (!is_null($quest->shards_cost)) {
                $updates['shards_cost'] = 50;
            }

            if (!is_null($quest->copper_coins_cost)) {
                $updates['copper_coins_cost'] = 2000;
            }

            if (!is_null($quest->reward_gold)) {
                $updates['reward_gold'] = 3500;
            }

            if (!is_null($quest->reward_gold_dust)) {
                $updates['reward_gold_dust'] = 250;
            }

            if (!is_null($quest->reward_shards)) {
                $updates['reward_shards'] = 25;
            }

            if (!is_null($quest->reward_copper_coins)) {
                $updates['reward_copper_coins'] = 125;
            }

            $quest->update($updates);

            $updates = [];
        }
    }

    public function handleShadowPlanesQuests() {
        $quests = Quest::whereHas('npc.gameMap', function($query) {
            $query->where('name', MapNameValue::SHADOW_PLANE);
        })->get();

        $updates = [];

        foreach ($quests as $quest) {

            if (!is_null($quest->gold_cost)) {
                $updates['gold_cost'] = 50000;
            }

            if (!is_null($quest->gold_dust_cost)) {
                $updates['gold_dust_cost'] = 5000;
            }

            if (!is_null($quest->shards_cost)) {
                $updates['shards_cost'] = 100;
            }

            if (!is_null($quest->copper_coins_cost)) {
                $updates['copper_coins_cost'] = 5000;
            }

            if (!is_null($quest->reward_gold)) {
                $updates['reward_gold'] = 5000;
            }

            if (!is_null($quest->reward_gold_dust)) {
                $updates['reward_gold_dust'] = 500;
            }

            if (!is_null($quest->reward_shards)) {
                $updates['reward_shards'] = 50;
            }

            if (!is_null($quest->reward_copper_coins)) {
                $updates['reward_copper_coins'] = 250;
            }

            $quest->update($updates);

            $updates = [];
        }
    }

    public function handleHellPlanesQuests() {
        $quests = Quest::whereHas('npc.gameMap', function($query) {
            $query->where('name', MapNameValue::HELL);
        })->get();

        $updates = [];

        foreach ($quests as $quest) {

            if (!is_null($quest->gold_cost)) {
                $updates['gold_cost'] = 100000;
            }

            if (!is_null($quest->gold_dust_cost)) {
                $updates['gold_dust_cost'] = 25000;
            }

            if (!is_null($quest->shards_cost)) {
                $updates['shards_cost'] = 1000;
            }

            if (!is_null($quest->copper_coins_cost)) {
                $updates['copper_coins_cost'] = 10000;
            }

            if (!is_null($quest->reward_gold)) {
                $updates['reward_gold'] = 10000;
            }

            if (!is_null($quest->reward_gold_dust)) {
                $updates['reward_gold_dust'] = 2500;
            }

            if (!is_null($quest->reward_shards)) {
                $updates['reward_shards'] = 125;
            }

            if (!is_null($quest->reward_copper_coins)) {
                $updates['reward_copper_coins'] = 500;
            }

            $quest->update($updates);

            $updates = [];
        }
    }

    public function handleIcePlaneQuests() {
        $quests = Quest::whereHas('npc.gameMap', function($query) {
            $query->where('name', MapNameValue::ICE_PLANE);
        })->get();

        $updates = [];

        foreach ($quests as $quest) {

            if (!is_null($quest->gold_cost)) {
                $updates['gold_cost'] = 1500;
            }

            if (!is_null($quest->gold_dust_cost)) {
                $updates['gold_dust_cost'] = 125;
            }

            if (!is_null($quest->shards_cost)) {
                $updates['shards_cost'] = 20;
            }

            if (!is_null($quest->copper_coins_cost)) {
                $updates['copper_coins_cost'] = 150;
            }

            if (!is_null($quest->reward_gold)) {
                $updates['reward_gold'] = 200;
            }

            if (!is_null($quest->reward_gold_dust)) {
                $updates['reward_gold_dust'] = 75;
            }

            if (!is_null($quest->reward_shards)) {
                $updates['reward_shards'] = 10;
            }

            if (!is_null($quest->reward_copper_coins)) {
                $updates['reward_copper_coins'] = 25;
            }

            $quest->update($updates);

            $updates = [];
        }
    }
}
