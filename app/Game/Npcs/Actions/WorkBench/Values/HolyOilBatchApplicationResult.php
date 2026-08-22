<?php

namespace App\Game\Npcs\Actions\WorkBench\Values;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;

class HolyOilBatchApplicationResult
{
    private function __construct(
        public readonly bool $success,
        public readonly InventorySlot|SetSlot|null $resultingSlot,
        public readonly int $goldDustSpent,
        public readonly bool $saturated,
        public readonly ?string $reason,
    ) {}

    /**
     * Build a result representing a successfully applied Holy Oil stack.
     *
     * @param  InventorySlot|SetSlot  $slot  The resulting target slot, carrying the updated item.
     * @param  int  $goldDustSpent  The Gold Dust spent applying the oil.
     * @param  bool  $saturated  Whether the target has now reached its maximum Holy stacks.
     * @return self A successful application result.
     */
    public static function success(InventorySlot|SetSlot $slot, int $goldDustSpent, bool $saturated): self
    {
        return new self(true, $slot, $goldDustSpent, $saturated, null);
    }

    /**
     * Build a result representing a failed Holy Oil application attempt.
     *
     * @param  string  $reason  The factual reason the application failed.
     * @return self A failed application result.
     */
    public static function failed(string $reason): self
    {
        return new self(false, null, 0, false, $reason);
    }
}
