<?php

namespace App\Game\Maps\Console\Commands;

use Illuminate\Console\Command;
use App\Flare\Models\GameMap;
use App\Game\Maps\Events\UpdateGlobalCharacterCountBroadcast;

class UpdateMapCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:map-count';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates map count';

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
     * @return mixed
     */
    public function handle()
    {
        foreach (GameMap::all() as $map) {
            broadcast(new UpdateGlobalCharacterCountBroadcast($map));
        }
    }
}
