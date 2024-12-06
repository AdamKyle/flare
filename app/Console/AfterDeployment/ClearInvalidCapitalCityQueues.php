<?php

namespace App\Console\AfterDeployment;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityResourceRequest;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use DB;

class ClearInvalidCapitalCityQueues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:invalid-capital-city-queues';

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
        CapitalCityBuildingQueue::where('started_at', '<', Carbon::today())->delete();
        CapitalCityUnitQueue::where('started_at', '<', Carbon::today())->delete();
        CapitalCityResourceRequest::where('started_at', '<', Carbon::today())->delete();

        UnitMovementQueue::where('started_at', '=', DB::raw('completed_at'))
            ->where('resources_requested', true)
            ->delete();


        $this->updateKingdomsWithInvalidCurrentResources();
    }

    private function updateKingdomsWithInvalidCurrentResources(): void
    {
        Kingdom::where(function ($query) {
            $query->where('current_stone', '<', 0)
                ->orWhere('current_wood', '<', 0)
                ->orWhere('current_clay', '<', 0)
                ->orWhere('current_iron', '<', 0)
                ->orWhere('current_steel', '<', 0);
        })->chunkById(100, function ($kingdoms) {
            foreach ($kingdoms as $kingdom) {
                $updates = [];

                if ($kingdom->current_stone < 0) {
                    $updates['current_stone'] = $kingdom->max_stone;
                }
                if ($kingdom->current_wood < 0) {
                    $updates['current_wood'] = $kingdom->max_wood;
                }
                if ($kingdom->current_clay < 0) {
                    $updates['current_clay'] = $kingdom->max_clay;
                }
                if ($kingdom->current_iron < 0) {
                    $updates['current_iron'] = $kingdom->max_iron;
                }
                if ($kingdom->current_steel < 0) {
                    $updates['current_steel'] = $kingdom->max_steel;
                }

                if (!empty($updates)) {
                    $kingdom->update($updates);
                }
            }
        });
    }
}
