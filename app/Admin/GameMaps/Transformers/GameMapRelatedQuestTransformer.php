<?php

namespace App\Admin\GameMaps\Transformers;

use App\Flare\Models\Quest;
use League\Fractal\TransformerAbstract;

class GameMapRelatedQuestTransformer extends TransformerAbstract
{
    /**
     * Transform a Quest into its compact Game Map relationship representation.
     *
     * `resolved_kind` is a non-persisted property `GameMapService::paginateRelatedQuests()`
     * attaches to each Quest before transforming, since resolving `QuestKind` requires
     * batch-checking which Quests in the page have children.
     *
     * @param  Quest  $quest  Quest to transform, carrying a `resolved_kind` property.
     * @return array{id: int, name: string, kind: string, npc: array{id: int, name: string}|null} Compact Quest relationship representation.
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
