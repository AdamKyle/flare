<?php

namespace App\Game\Npcs\Actions\Seer\Transformers;

use App\Flare\Models\GemBagSlot;
use App\Game\Gems\Transformers\GemTransformer;
use League\Fractal\TransformerAbstract;

class SeerGemTransformer extends TransformerAbstract
{
    public function __construct(private readonly GemTransformer $gemTransformer) {}

    public function transform(GemBagSlot $slot): array
    {
        return [
            'slot_id' => $slot->id,
            'amount' => $slot->amount,
            'gem' => $this->gemTransformer->transform($slot->gem),
        ];
    }
}
