<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Enums;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\HolyOilsBatchMode;
use Tests\TestCase;

class HolyOilsBatchModeTest extends TestCase
{
    public function test_execution_window_size_is_null_for_both_modes(): void
    {
        $this->assertNull(HolyOilsBatchMode::SELECTED_ITEMS->executionWindowSize());
        $this->assertNull(HolyOilsBatchMode::INVENTORY_SET->executionWindowSize());
    }

    public function test_allowed_dispositions_are_identical_for_both_modes(): void
    {
        $dispositions = HolyOilsBatchMode::SELECTED_ITEMS->allowedDispositions();

        $this->assertContains(BatchCraftingDisposition::KEEP, $dispositions);
        $this->assertContains(BatchCraftingDisposition::SELL, $dispositions);
        $this->assertContains(BatchCraftingDisposition::DESTROY, $dispositions);
        $this->assertContains(BatchCraftingDisposition::LIST, $dispositions);
        $this->assertContains(BatchCraftingDisposition::DISENCHANT, $dispositions);
        $this->assertSame($dispositions, HolyOilsBatchMode::INVENTORY_SET->allowedDispositions());
    }

    public function test_allowed_output_destinations_is_always_empty(): void
    {
        $this->assertSame([], HolyOilsBatchMode::SELECTED_ITEMS->allowedOutputDestinations());
        $this->assertSame([], HolyOilsBatchMode::INVENTORY_SET->allowedOutputDestinations());
    }
}
