<?php

namespace App\Console\Commands;

use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use Illuminate\Console\Command;

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
        Kingdom::chunkById(100, function($kingdoms) use ($service) {
            foreach ($kingdoms as $kingdom) {
                $service->setKingdom($kingdom)->updateKingdom();
            }
        });

        dd($service->getKingdomsUpdated());
    }
}
