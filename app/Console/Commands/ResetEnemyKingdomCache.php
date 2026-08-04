<?php

namespace App\Console\Commands;

use App\Game\Maps\Values\MapName;
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
        foreach (MapName::values() as $name) {
            Cache::delete('enemy-kingdoms-'.$name);
        }
    }
}
