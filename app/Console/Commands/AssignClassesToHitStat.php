<?php

namespace App\Console\Commands;

use App\Flare\Models\GameClass;
use App\Flare\Models\Item;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\Notification;
use App\Flare\Values\CharacterClassValue;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AssignClassesToHitStat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'class:assign-to-hit-stat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns to hit stat.';

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
                $class->update(['to_hit_stat' => 'agi']);
            }

            if ($classType->isFighter()) {
                $class->update(['to_hit_stat' => 'dex']);
            }

            if ($classType->isVampire()) {
                $class->update(['to_hit_stat' => 'dur']);
            }

            if ($classType->isHeretic() || $classType->isProphet()) {
                $class->update(['to_hit_stat' => 'focus']);
            }
        });
    }
}
