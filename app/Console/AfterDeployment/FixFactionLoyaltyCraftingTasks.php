<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\FactionLoyaltyNpcTask;
use App\Flare\Models\Item;
use Illuminate\Console\Command;

class FixFactionLoyaltyCraftingTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:faction-loyalty-crafting-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes invalid items for faction loyalty crafting tasks.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        FactionLoyaltyNpcTask::whereJsonLength('fame_tasks', '>', 0)->chunkById(100, function($factionLoyaltyTasks) {
            foreach ($factionLoyaltyTasks as $factionLoyaltyTask) {
                $this->fixInvalidItemsOnFactionLoyaltyTask($factionLoyaltyTask);
            }
        });
    }

    private function fixInvalidItemsOnFactionLoyaltyTask(FactionLoyaltyNpcTask $factionLoyaltyNpcTask): void {
        $tasks = $factionLoyaltyNpcTask->fame_tasks;


        foreach ($tasks as $index => $task) {
            if ($task['type'] === 'bounty') {
                continue;
            }

            $doesItemExist = Item::where('id', $task['item_id'])->exists();

            if (!$doesItemExist) {
                $this->info('Item: ' . $task['item_name'] . ' does not exist for id: ' . $task['item_id'] . '. Starting replacement');

                $foundItemByName = Item::where('name', $task['item_name'])->first();

                $task['item_id'] = $foundItemByName->id;

                $tasks[$index] = $task;

                $this->info('Replaced item.');
            }
        }

        $factionLoyaltyNpcTask->update([
            'fame_tasks' => $tasks,
        ]);
    }
}
