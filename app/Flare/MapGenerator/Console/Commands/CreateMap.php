<?php

namespace App\Flare\MapGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ChristianEssl\LandmapGeneration\Struct\Color;
use App\Flare\MapGenerator\Builders\MapBuilder;

class CreateMap extends Command {
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
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {

        // Surface:
        // $land  = new Color(23, 132, 72);
        // $land = new Color(97, 83, 61);

        // Labyrinth:
        //$land = new Color(99, 70, 8);

        // Dungeon:
        // $land = new Color(94, 74, 73);

        // Shadow Plane:
        // $land = new Color(128, 127, 126);

        // Hell:
        // $land = new Color(59, 46, 23);

        // Purgatory:
        // $land = new Color(0,0,0);

        // Ice Plane
        // $land = new Color(39, 84, 166);

        // Twisted Memories Plane
        // $land = new Color(91, 110, 96);

        // Delusional Memories
        $land = new Color(138, 79, 12);

        // Surface & Labyrinth:
        // $water = new Color(44, 86, 100);
        $water = new Color(66, 129, 178);

        // Dungeon Water:
        // $water = new Color(162, 219, 118);

        // Shadow Plane Water:
        // $water = new Color(100, 227, 250);

        // Hell Magma:
        // $water = new Color(97, 0, 16);

        // Purgatory Water:
        // $water = new Color(255, 255, 255);

        // Ice Planes:
        // $water = new Color(195, 225, 250);

        // Twisted memories:
        // $water = new Color(18, 57, 87);

        // Regular Water Level
        // $waterLevel = 45;

        // Hell Water Level
        // $waterLevel = 55;

        // Purgatory Water Level
        // $waterLevel = 85;

        // Ice Plane Water Level
        // $waterLevel = 25;

        // Twisted Memories Water Level
        // $waterLevel = 75;

        // Delusional Memories Water Level
        $waterLevel = 40;

        ini_set('memory_limit', '3G');

        resolve(MapBuilder::class)->setLandColor($land)
            ->setWaterColor($water)
            ->setMapHeight($this->argument('height'))
            ->setMapWidth($this->argument('width'))
            ->setMapSeed($this->argument('randomness'))
            ->buildMap($this->argument('name'), $waterLevel);
    }
}
