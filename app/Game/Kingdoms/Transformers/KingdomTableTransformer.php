<?php

namespace App\Game\Kingdoms\Transformers;

use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use App\Flare\Transformers\UnitMovementTransformer;
use League\Fractal\Resource\Collection;
use League\Fractal\TransformerAbstract;

class KingdomTableTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [
        'unitsInMovement',
    ];

    public function transform(Kingdom $kingdom)
    {
        return [
            'id' => $kingdom->id,
            'character_id' => $kingdom->character_id,
            'game_map_name' => $kingdom->gameMap->name,
            'name' => $kingdom->name,
            'x_position' => $kingdom->x_position,
            'y_position' => $kingdom->y_position,
            'current_morale' => $kingdom->current_morale,
            'treasury' => $kingdom->treasury,
            'gold_bars' => $kingdom->gold_bars,
            'is_capital' => $kingdom->is_capital,
        ];
    }

    public function includeUnitsInMovement(Kingdom $kingdom): Collection
    {

        $unitMovementQueues = UnitMovementQueue::where('character_id', $kingdom->character_id)
            ->get();

        return $this->collection($unitMovementQueues, new UnitMovementTransformer);
    }
}
