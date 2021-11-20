<?php

namespace App\Flare\MapGenerator\Console\Commands;

use Illuminate\Console\Command;
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
    protected $signature = 'create:map {name} {width} {height} {randomness}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a game map';

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

        // Surface:
        // $land  = new Color(23, 132, 72);

        // Labyrinth:
        // $land = new Color(99, 70, 8);

        // Dungeon:
        // $land = new Color(94, 74, 73);

        // Shadow Plane:
        // $land = new Color(128, 127, 126);

        // Hell:
        $land = new Color(130, 118, 104);

        // Surface & Labyrinth:
        // $water = new Color(66, 129, 178);

        // Dungeon Water:
        //$water = new Color(162, 219, 118);

        // Shadow Plane Water:
        // $water = new Color(100, 227, 250);

        // Hell Magma:
        $water = new Color(110, 71, 84);

        ini_set('memory_limit','3G');

        resolve(MapBuilder::class)->setLandColor($land)
                                  ->setWaterColor($water)
                                  ->setMapHeight($this->argument('height'))
                                  ->setMapWidth($this->argument('width'))
                                  ->setMapSeed(Str::random($this->argument('randomness')))
                                  ->buildMap($this->argument('name'));
    }
}
