<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityResourceRequest;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\CharacterAutomation;
use Illuminate\Console\Command;

class ClearPlayersKingdomJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:players-kingdom-jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear player kingdom jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        CapitalCityUnitQueue::truncate();
        CapitalCityBuildingQueue::truncate();
        CapitalCityResourceRequest::truncate();
    }
}
