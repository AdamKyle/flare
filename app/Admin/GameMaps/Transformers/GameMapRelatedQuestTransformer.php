<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\Quest;
use League\Fractal\TransformerAbstract;

class GameMapRelatedQuestTransformer extends TransformerAbstract
{
    /**
     * Transform a Quest into its compact Game Map relationship representation.
     */
    public function transform(Quest $quest): array
    {
        return [
            'id' => $quest->id,
            'name' => $quest->name,
            'kind' => $quest->resolved_kind->value,
            'npc' => is_null($quest->npc) ? null : [
                'id' => $quest->npc->id,
                'name' => $quest->npc->real_name,
            ],
        ];
    }
}
