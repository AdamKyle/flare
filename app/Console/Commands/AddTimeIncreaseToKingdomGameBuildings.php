<?php

namespace App\Console\Commands;

use App\Flare\Models\GameBuilding;
use Illuminate\Console\Command;

class AddTimeIncreaseToKingdomGameBuildings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:game-building-time-increases';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds the time increase to each building';

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
        GameBuilding::where('name', 'Keep')->update([
            'time_increase_amount' => 0.20
        ]);

        GameBuilding::where('name', 'Farm')->update([
            'time_increase_amount' => 0.05
        ]);

        GameBuilding::where('name', 'Lumber Mill')->update([
            'time_increase_amount' => 0.08
        ]);

        GameBuilding::where('name', 'Clay Pit')->update([
            'time_increase_amount' => 0.05
        ]);

        GameBuilding::where('name', 'Stone Quarry')->update([
            'time_increase_amount' => 0.10
        ]);

        GameBuilding::where('name', 'Iron Mine')->update([
            'time_increase_amount' => 0.12
        ]);

        GameBuilding::where('name', 'Walls')->update([
            'time_increase_amount' => 0.15
        ]);

        GameBuilding::where('name', 'Barracks')->update([
            'time_increase_amount' => 0.13
        ]);

        GameBuilding::where('name', 'Church')->update([
            'time_increase_amount' => 0.16
        ]);

        GameBuilding::where('name', 'Settlers Hall')->update([
            'time_increase_amount' => 0.25
        ]);
    }
}
