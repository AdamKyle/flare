<?php

namespace App\Console\Commands;

use App\Flare\Models\Adventure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * @codeCoverageIgnore
 */
class ReShuffleAdventureLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:adventure-locations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes Adventure Locations';

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
        $adventureLocations = DB::Table('adventure_location')->select()->get();

        foreach ($adventureLocations as $adventureLocation) {
            Adventure::find($adventureLocation->adventure_id)->update([
                'location_id' => $adventureLocation->location_id
            ]);
        }
    }
}
