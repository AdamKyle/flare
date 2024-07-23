<?php

namespace App\Game\Kingdoms\Console\Commands;

use App\Flare\Jobs\UpdateKingdomJob;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Game\Kingdoms\Jobs\CapitalCityUpdateAutoWalkedKingdoms;
use Illuminate\Console\Command;
use App\Flare\Models\Kingdom;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class ResetCapitalCityWalkingStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:capital-city-walking-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates all the kingdoms to reset their capital city walking status';

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
    public function handle(KingdomTransformer $kingdomTransformer, Manager $manager)
    {
        Kingdom::where('npc_owned', false)->update(['auto_walked' => false]);

        Kingdom::where('npc_owned', false)->whereNotNull('character_id')->chunkById(100, function ($kingdoms) use($kingdomTransformer, $manager) {
            foreach ($kingdoms as $kingdom) {
                CapitalCityUpdateAutoWalkedKingdoms::dispatch($kingdom);
            }
        });
    }
}
