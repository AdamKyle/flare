<?php

namespace App\Game\Npcs\Actions\WorkBench\Transformers;

use App\Flare\Models\AlchemyBagSlot;
use App\Game\Core\Items\Values\HolyItemLevel;
use League\Fractal\TransformerAbstract;

class WorkBenchHolyOilTransformer extends TransformerAbstract
{
    public function transform(AlchemyBagSlot $slot): array
    {
        $level = HolyItemLevel::from($slot->item->holy_level);

        return [
            'id' => $slot->id,
            'item_id' => $slot->item->id,
            'name' => $slot->item->name,
            'level' => $level->value,
            'stack_amount' => $slot->amount,
            'possible_stat_bonus_minimum' => 1,
            'possible_stat_bonus_maximum' => $level->maximumBonus(),
            'possible_devoidance_minimum' => 1 / 1000,
            'possible_devoidance_maximum' => $level->maximumBonus() / 1000,
        ];
    }
}
