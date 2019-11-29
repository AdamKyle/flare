<?php

namespace App\Flare\MapGenerator\Console\Commands;

use Hash;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Str;
use ChristianEssl\LandmapGeneration\Struct\Color;
use App\Flare\MapGenerator\Builders\MapBuilder;


class CreateMap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:map';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the game map';

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
        $water = new Color(66, 129, 178);
        $land  = new Color(23, 132, 72);

         ini_set('memory_limit','1G');

        resolve(MapBuilder::class)->setLandColor($land)
                                  ->setWaterColor($water)
                                  ->setMapHeight(1250)
                                  ->setMapWidth(1250)
                                  ->setMapSeed(Str::random(80))
                                  ->buildMap('surface');
    }
}
