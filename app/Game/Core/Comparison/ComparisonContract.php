<?php

namespace App\Game\Core\Comparison;

use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Collection;

interface ComparisonContract {

    public function fetchDetails(Item $toCompare, Collection $inventorySlots): array;
}