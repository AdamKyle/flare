<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\BuildingExpansionQueue;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\KingdomBuildingExpansion;
use App\Flare\Models\User;
use App\Game\Kingdoms\Events\UpdateBuildingExpansion;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Values\BuildingExpansionTypes;
use App\Game\Kingdoms\Values\ResourceBuildingExpansionBaseValue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpandResourceBuilding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User
     */
    protected $user;

    protected KingdomBuilding $building;

    /**
     * @var int queueId
     */
    protected $queueId;

    /**
     * @var array
     */
    protected $resourceTypes = [
        'wood', 'clay', 'stone', 'iron',
    ];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(KingdomBuilding $building, User $user, int $queueId)
    {
        $this->user = $user;

        $this->building = $building;

        $this->queueId = $queueId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(UpdateKingdom $updateKingdom)
    {

        $queue = BuildingExpansionQueue::find($this->queueId);

        if (is_null($queue)) {
            return;
        }

        if (! $queue->completed_at->lessThanOrEqualTo(now())) {
            $timeLeft = $queue->completed_at->diffInMinutes(now());

            if ($timeLeft <= 15) {
                $time = now()->addMinutes($timeLeft);
            } else {
                $time = now()->addMinutes(15);
            }

            // @codeCoverageIgnoreStart
            ExpandResourceBuilding::dispatch(
                $this->building,
                $this->user,
                $this->queueId,
            )->delay($time);

            return;
            // @codeCoverageIgnoreEnd
        }

        $buildingExpansion = $this->building->buildingExpansion;

        if (is_null($buildingExpansion)) {
            $kingdomBuildingExpansion = KingdomBuildingExpansion::create([
                'kingdom_building_id' => $this->building->id,
                'kingdom_id' => $this->building->kingdom->id,
                'expansion_type' => BuildingExpansionTypes::RESOURCE_EXPANSION,
                'expansion_count' => 1,
                'expansions_left' => ResourceBuildingExpansionBaseValue::MAX_EXPANSIONS - 1,
                'minutes_until_next_expansion' => ResourceBuildingExpansionBaseValue::BASE_MINUTES_REQUIRED * 2,
                'resource_costs' => ResourceBuildingExpansionBaseValue::resourceCostsForExpansion(),
                'gold_bars_cost' => ResourceBuildingExpansionBaseValue::BASE_GOLD_BARS_REQUIRED,
                'resource_increases' => ResourceBuildingExpansionBaseValue::BASE_RESOURCE_GAIN,
            ]);

            $queue->delete();

            $resourceType = $this->getResourceType();

            $this->building->kingdom()->update([
                'max_'.$resourceType => $this->building->kingdom->{'max_'.$resourceType} + $kingdomBuildingExpansion->resource_increases,
            ]);

            event(new UpdateBuildingExpansion($this->user->character, $kingdomBuildingExpansion));

            $updateKingdom->updateKingdom($this->building->kingdom->refresh());

            return;
        }

        if ($buildingExpansion->expansion_count < ResourceBuildingExpansionBaseValue::MAX_EXPANSIONS) {
            $expansionCount = $buildingExpansion->expansion_count + 1;

            $buildingExpansion->update([
                'expansion_count' => $expansionCount,
                'minutes_until_next_expansion' => $buildingExpansion->minutes_until_next_expansion * 2,
                'resource_costs' => ResourceBuildingExpansionBaseValue::resourceCostsForExpansion($expansionCount),
                'gold_bars_cost' => $buildingExpansion->gold_bars_cost + ResourceBuildingExpansionBaseValue::BASE_GOLD_BARS_REQUIRED,
            ]);

            $buildingExpansion = $buildingExpansion->refresh();

            $queue->delete();

            $resourceType = $this->getResourceType();

            $this->building->kingdom()->update([
                'max_'.$resourceType => $this->building->kingdom->{'max_'.$resourceType} + $buildingExpansion->resource_increases,
            ]);

            event(new UpdateBuildingExpansion($this->user->character, $buildingExpansion));

            $updateKingdom->updateKingdom($this->building->kingdom->refresh());
        }
    }

    protected function getResourceType()
    {
        foreach ($this->resourceTypes as $type) {
            if ($this->building->{'increase_in_'.$type} !== 0.0) {
                return $type;
            }
        }

        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
    }
}
