<?php

namespace App\Console\Commands;

use App\Flare\Models\GameClass;
use App\Flare\Models\GameRace;
use App\Flare\Models\Item;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\Notification;
use App\Flare\Values\CharacterClassValue;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateRacesAndClasses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-races-classes:assign-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns the stat mods to classes and races.';

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
        GameClass::all()->each(function($class) {
            $classType = new CharacterClassValue($class->name);

            if ($classType->isRanger() || $classType->isThief()) {
                $class->update(['agi_mod' => 5]);
            }

            if ($classType->isHeretic() || $classType->isProphet()) {
                $class->update(['focus_mod' => 5]);
            }
        });

        GameRace::all()->each(function($race) {
            if ($race->name === 'Centaur') {
                $race->update(['agi_mod' => 3]);
            }

            if ($race->name === 'Dryad') {
                $race->update(['agi_mod' => 2, 'focus_mod' => 3]);
            }

            if ($race->name === 'High Elf') {
                $race->update(['agi_mod' => 3, 'focus_mod' => 2]);
            }

            if ($race->name === 'Dark Dwarf') {
                $race->update(['focus_mod' => 3]);
            }

            if ($race->name === 'Orc') {
                $race->update(['focus_mod' => 2, 'agi_mod' => 1]);
            }

            if ($race->name === 'Human') {
                $race->update(['focus_mod' => 1, 'agi_mod' => 1]);
            }
        });
    }
}
