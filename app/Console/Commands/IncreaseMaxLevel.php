<?php

namespace App\Console\Commands;

use App\Flare\Models\MaxLevelConfiguration;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Console\Command;

class IncreaseMaxLevel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'increase:max_level';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Increase the max level or creates the new max level.';

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
       $config = MaxLevelConfiguration::first();

       if (is_null($config)) {
           MaxLevelConfiguration::create([
               'max_level'      => 3500,
               'half_way'       => ceil(3500 / 2),
               'three_quarters' => ceil(3500 * .75),
               'last_leg'       => 3400
           ]);
       } else {

           if ($config->max_level >= 9999) {
               return;
           }

           $maxLevel = $config->max_level + 100;

           if ($maxLevel > 9999) {
               $maxLevel = 9999;
           }

           $config->update([
               'max_level' => $maxLevel,
               'half_way' => ceil($maxLevel / 2),
               'three_quarters' => ceil($maxLevel * 0.75),
               'last_leg' => $maxLevel - 100,
           ]);

           event(new GlobalMessageEvent('Max level has been increased by 100 levels! refresh to see the increase.'));

           if ($maxLevel === 9999) {
               event(new GlobalMessageEvent('Max level is now 9999. Max level will no longer increase past today.'));
           }
       }
    }
}
