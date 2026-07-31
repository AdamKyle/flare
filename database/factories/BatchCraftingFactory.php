<?php

namespace Database\Factories;

use App\Flare\Models\BatchCrafting;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchCraftingFactory extends Factory
{
    protected $model = BatchCrafting::class;

    public function definition()
    {
        return [
            'character_id' => 1,
            'user_id' => 1,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'started_at' => now(),
            'ends_at' => now()->addHours(8),
            'status' => 'running',
            'progress' => [],
            'info_acknowledged' => false,
            'selected_items' => [],
            'selected_oils' => [],
            'crafted_count' => 0,
            'sold_count' => 0,
            'destroyed_count' => 0,
            'listed_count' => 0,
            'kept_count' => 0,
            'applied_count' => 0,
            'skipped_count' => 0,
            'failed_count' => 0,
        ];
    }
}
