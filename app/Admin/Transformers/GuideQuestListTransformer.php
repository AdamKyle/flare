<?php

namespace App\Admin\Transformers;

use App\Flare\Models\GuideQuest;
use League\Fractal\TransformerAbstract;

class GuideQuestListTransformer extends TransformerAbstract
{
    /**
     * Transform a Guide Quest into its Admin list-row representation.
     *
     * @param GuideQuest $guideQuest
     * @return array
     */
    public function transform(GuideQuest $guideQuest): array
    {
        $eventType = $guideQuest->eventType();

        return [
            'id' => $guideQuest->id,
            'name' => $guideQuest->name,
            'required_level' => $guideQuest->required_level,
            'unlock_at_level' => $guideQuest->unlock_at_level,
            'parent' => is_null($guideQuest->parent_id) ? null : [
                'id' => $guideQuest->parent_id,
                'name' => $guideQuest->parent_quest_name,
            ],
            'event' => is_null($eventType) ? null : [
                'value' => $guideQuest->only_during_event,
                'label' => $eventType->getNameForEvent(),
            ],
        ];
    }
}
