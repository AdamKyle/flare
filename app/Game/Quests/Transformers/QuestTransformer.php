<?php

namespace App\Game\Quests\Transformers;

use App\Flare\Models\Quest;
use League\Fractal\TransformerAbstract;

class QuestTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [
        'child_quests',
    ];

    /**
     * Gets the response data for the character sheet
     */
    public function transform(Quest $quest): array
    {
        return [
            'id' => $quest->id,
            'name' => $quest->name,
            'is_parent' => $quest->is_parent,
            'parent_quest_id' => $quest->parent_quest_id,
            'required_quest_id' => $quest->required_quest_id,
            'belongs_to_map_name' => $quest->belongs_to_map_name,
            'required_quest_chain_details' => $quest->required_quest_chain_details,
        ];
    }

    public function includeChildQuests(Quest $quest)
    {
        $children = $quest->childQuests;

        if (is_null($children)) {
            return null;
        }

        return $this->collection($children, new QuestTransformer);
    }
}
