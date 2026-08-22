<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Values;

use App\Game\Automation\BatchCrafting\Enums\HolyOilTargetKind;
use App\Game\Automation\BatchCrafting\Values\HolyOilApplicationPlanEntry;
use Tests\TestCase;

class HolyOilApplicationPlanEntryTest extends TestCase
{
    public function test_construction_for_an_inventory_target_kind_preserves_the_given_ids(): void
    {
        $entry = new HolyOilApplicationPlanEntry(HolyOilTargetKind::INVENTORY_SLOT, 11, 22);

        $this->assertSame(HolyOilTargetKind::INVENTORY_SLOT, $entry->targetKind);
        $this->assertSame(11, $entry->targetSlotId);
        $this->assertSame(22, $entry->targetItemId);
    }

    public function test_construction_for_a_set_target_kind_preserves_the_given_ids(): void
    {
        $entry = new HolyOilApplicationPlanEntry(HolyOilTargetKind::SET_SLOT, 33, 44);

        $this->assertSame(HolyOilTargetKind::SET_SLOT, $entry->targetKind);
        $this->assertSame(33, $entry->targetSlotId);
        $this->assertSame(44, $entry->targetItemId);
    }

    public function test_to_array_serializes_the_exact_ids_and_kind(): void
    {
        $entry = new HolyOilApplicationPlanEntry(HolyOilTargetKind::SET_SLOT, 33, 44);

        $serialized = $entry->toArray();

        $this->assertSame([
            'target_kind' => HolyOilTargetKind::SET_SLOT->value,
            'target_slot_id' => 33,
            'target_item_id' => 44,
        ], $serialized);
    }

    public function test_from_array_rebuilds_the_exact_entry_from_its_serialized_form(): void
    {
        $rebuilt = HolyOilApplicationPlanEntry::fromArray([
            'target_kind' => HolyOilTargetKind::INVENTORY_SLOT->value,
            'target_slot_id' => 11,
            'target_item_id' => 22,
        ]);

        $this->assertSame(HolyOilTargetKind::INVENTORY_SLOT, $rebuilt->targetKind);
        $this->assertSame(11, $rebuilt->targetSlotId);
        $this->assertSame(22, $rebuilt->targetItemId);
    }
}
