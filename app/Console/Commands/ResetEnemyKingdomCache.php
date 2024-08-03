<?php

namespace App\Console\Commands;

use App\Flare\Values\MapNameValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ResetEnemyKingdomCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:enemy-kingdom-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach (MapNameValue::$values as $name) {
            Cache::delete('enemy-kingdoms-'.$name);
        }
    }
}
