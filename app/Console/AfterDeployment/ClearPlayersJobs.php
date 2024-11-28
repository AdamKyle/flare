<?php

namespace App\Console\Commands;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityResourceRequest;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\CharacterAutomation;
use Illuminate\Console\Command;

class ClearPlayersJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:players-jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command descriptionClear specific player jobs when the queue is acting up';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        CharacterAutomation::chunkById(100, function ($characterAutomations) {
            foreach ($characterAutomations as $characterAutomation) {
                $characterAutomation->delete();
            }
        });

        CapitalCityUnitQueue::truncate();
        CapitalCityBuildingQueue::truncate();
        CapitalCityResourceRequest::truncate();
    }
}
