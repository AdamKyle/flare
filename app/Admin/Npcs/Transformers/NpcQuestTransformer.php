<?php

namespace App\Admin\Npcs\Transformers;

use App\Flare\Models\Item;
use App\Flare\Models\Quest;
use League\Fractal\TransformerAbstract;

class NpcQuestTransformer extends TransformerAbstract
{
    /**
     * Transform a Quest into its compact NPC relationship representation.
     */
    public function transform(Quest $quest): array
    {
        return [
            'id' => $quest->id,
            'name' => $quest->name,
            'required_item' => $this->transformItem($quest->item),
            'secondary_required_item' => $this->transformItem($quest->secondaryItem),
            'reward_item' => $this->transformItem($quest->rewardItem),
        ];
    }

    /**
     * Transform a related Item into its compact identity representation.
     */
    private function transformItem(?Item $item): ?array
    {
        if (is_null($item)) {
            return null;
        }

        return [
            'id' => $item->id,
            'name' => $item->name,
        ];
    }
}
