<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
use Illuminate\Console\Command;

class RewardPlayer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reward:player {characterId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Used for testing purposes to test aspects of the game connected together.';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $characterId = $this->argument('characterId');

        if (is_null($characterId)) {
            $this->error('Missing character id.');

            return;
        }

        $character = Character::find($characterId);

        if (is_null($character)) {
            $this->error('Character not found.');

            return;
        }

        // Give the max gold bars to all kingdoms
        $character->kingdoms()->update(['gold_bars' => 1000]);

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $character = $character->refresh();

        $factionLoyalties = $character->factionLoyalties;

        foreach ($factionLoyalties as $factionLoyalty) {

            $factionLoyaltyNpcs = $factionLoyalty->factionLoyaltyNpcs;

            foreach ($factionLoyaltyNpcs as $factionLoyaltyNpc) {

                $fameTasks = $factionLoyaltyNpc->factionLoyaltyNpcTasks->fame_tasks;

                if (empty($fameTasks)) {

                    if ($factionLoyaltyNpc->current_level < $factionLoyaltyNpc->max_level) {
                        $factionLoyaltyNpc->update([
                            'current_level' => $factionLoyaltyNpc->max_level,
                        ]);
                    }

                    continue;
                }

                foreach ($fameTasks as $index => $fameTask) {
                    $fameTasks[$index]['current_amount'] = $fameTask['required_amount'] - 1;
                }

                $factionLoyaltyNpc->factionLoyaltyNpcTasks()->update(['fame_tasks' => $fameTasks]);
                $factionLoyaltyNpc->update([
                    'current_level' => $factionLoyaltyNpc->max_level - 1,
                ]);
            }

        }
    }
}
