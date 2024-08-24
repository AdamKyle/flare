<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\GameMap;
use App\Flare\Values\MapNameValue;
use Illuminate\Console\Command;

class AllowTraverseForMaps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'allow:traverse-for-maps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update maps to allow for traversal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        GameMap::where('name', '!=', MapNameValue::TWISTED_MEMORIES)->update([
            'can_traverse' => true,
        ]);
    }
}
