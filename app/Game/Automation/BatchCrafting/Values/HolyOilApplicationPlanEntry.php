<?php

namespace App\Game\Automation\BatchCrafting\Values;

use App\Game\Automation\BatchCrafting\Enums\HolyOilTargetKind;

class HolyOilApplicationPlanEntry
{
    public function __construct(
        public readonly HolyOilTargetKind $targetKind,
        public readonly int $targetSlotId,
        public readonly int $targetItemId,
    ) {}

    /**
     * Serialize this plan entry for persistence within the batch's progress data.
     *
     * @return array The serialized plan entry.
     */
    public function toArray(): array
    {
        return [
            'target_kind' => $this->targetKind->value,
            'target_slot_id' => $this->targetSlotId,
            'target_item_id' => $this->targetItemId,
        ];
    }

    /**
     * Rebuild a plan entry from its persisted array representation.
     *
     * @param  array  $data  The persisted plan entry data.
     * @return self The rebuilt plan entry.
     */
    public static function fromArray(array $data): self
    {
        return new self(HolyOilTargetKind::from($data['target_kind']), $data['target_slot_id'], $data['target_item_id']);
    }
}
