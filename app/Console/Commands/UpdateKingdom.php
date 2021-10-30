<?php

namespace App\Console\Commands;

use App\Flare\Jobs\UpdateKingdomJob;
use Illuminate\Console\Command;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use Facades\App\Flare\Values\UserOnlineValue;

class UpdateKingdom extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:kingdom';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the kingdom\'s per hour resources.';

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
    public function handle(KingdomResourcesService $service)
    {
        Kingdom::chunkById(250, function($kingdoms) use ($service) {
            foreach ($kingdoms as $kingdom) {
                UpdateKingdomJob::dispatch($kingdom)->onConnection('kingdom_jobs');
            }
        });
    }
}
