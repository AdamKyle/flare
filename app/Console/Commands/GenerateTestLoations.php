<?php

namespace App\Console\Commands;

use App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use Illuminate\Console\Command;
use Str;

class GenerateTestLoations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:locations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates all locations. Consider this a stress test.';

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
        $coordinatesCache = new CoordinatesCache;

        $coordinatesCache = $coordinatesCache->getFromCache();

        $xCoordinates = $coordinatesCache['x'];
        $yCoordinates = $coordinatesCache['y'];

        $total = (count($xCoordinates) + count($yCoordinates));

        $this->info('Creating ' . $total . ' locations.');

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($xCoordinates as $xCoordinate) {
            foreach($yCoordinates as $yCoordinate) {
                $location = Location::where('x', $xCoordinate)->where('y', $yCoordinate)->first();

                if (is_null($location)) {
                    Location::create([
                        'name' => Str::random(5),
                        'game_map_id' => GameMap::first()->id,
                        'quest_reward_item_id' => null,
                        'description' => Str::random(10),
                        'is_port' => false,
                        'x' => $xCoordinate,
                        'y' => $yCoordinate,
                    ]);
                }
            }

            $bar->advance();
        }

        $bar->finish();

        $this->info(' All Done :D');
    }
}
