<?php

namespace App\Game\Kingdoms\Console\Commands;

use App\Flare\Jobs\UpdateKingdomJob;
use Illuminate\Console\Command;
use App\Flare\Models\Kingdom;

class UpdateKingdoms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:kingdoms';

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
    public function handle()
    {
        Kingdom::chunkById(250, function($kingdoms) {
            foreach ($kingdoms as $kingdom) {
                UpdateKingdomJob::dispatch($kingdom)->onConnection('long_running');
            }
        });
    }
}
