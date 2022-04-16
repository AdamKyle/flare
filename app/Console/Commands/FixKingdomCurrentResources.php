<?php

namespace App\Console\Commands;

use App\Flare\Models\Kingdom;
use Illuminate\Console\Command;

class FixKingdomCurrentResources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:kingdom-resources';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix kingdom\'s resources';

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
        Kingdom::chunkById(100, function($kingdoms) {
            foreach ($kingdoms as $kingdom) {
                $kingdom->update([
                    'current_stone' => $kingdom->current_stone < 0 ? 0 : $kingdom->current_stone,
                    'current_wood'  => $kingdom->current_wood < 0 ? 0 : $kingdom->current_wood,
                    'current_clay'  => $kingdom->current_clay < 0 ? 0 : $kingdom->current_clay,
                    'current_iron'  => $kingdom->current_iron < 0 ? 0 : $kingdom->current_iron,
                ]);
            }
        });
    }
}
