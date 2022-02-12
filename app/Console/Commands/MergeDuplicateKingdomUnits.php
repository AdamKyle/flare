<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Flare\Models\Kingdom;
use App\Flare\Jobs\MergeDuplicateKingdomUnits as MergeDuplicateKingdomUnitsJob;

/**
 * @codeCoverageIgnore
 */
class MergeDuplicateKingdomUnits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'merge:duplicate-kingdom-units';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
                MergeDuplicateKingdomUnitsJob::dispatch($kingdom)->onConnection('kingdom_jobs');
            }
        });
    }
}
