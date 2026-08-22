<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Enums;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Enums\CraftAndEnchantBatchMode;
use Tests\TestCase;

class CraftAndEnchantBatchModeTest extends TestCase
{
    public function test_execution_window_size_is_null_for_amount_and_set_and_twenty_three_for_experience(): void
    {
        $this->assertNull(CraftAndEnchantBatchMode::AMOUNT->executionWindowSize());
        $this->assertNull(CraftAndEnchantBatchMode::SET->executionWindowSize());
        $this->assertSame(23, CraftAndEnchantBatchMode::EXPERIENCE->executionWindowSize());
    }

    public function test_allowed_dispositions_for_amount_and_set_exclude_keep_best_dispositions(): void
    {
        $dispositions = CraftAndEnchantBatchMode::AMOUNT->allowedDispositions();

        $this->assertContains(BatchCraftingDisposition::KEEP, $dispositions);
        $this->assertContains(BatchCraftingDisposition::DISENCHANT, $dispositions);
        $this->assertNotContains(BatchCraftingDisposition::KEEP_BEST_SELL_REST, $dispositions);
        $this->assertSame($dispositions, CraftAndEnchantBatchMode::SET->allowedDispositions());
    }

    public function test_allowed_dispositions_for_experience_includes_keep_best_dispositions(): void
    {
        $dispositions = CraftAndEnchantBatchMode::EXPERIENCE->allowedDispositions();

        $this->assertContains(BatchCraftingDisposition::KEEP_BEST_SELL_REST, $dispositions);
        $this->assertContains(BatchCraftingDisposition::KEEP_BEST_DESTROY_REST, $dispositions);
        $this->assertContains(BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST, $dispositions);
    }

    public function test_allowed_output_destinations_for_amount_exclude_a_normal_inventory_set(): void
    {
        $destinations = CraftAndEnchantBatchMode::AMOUNT->allowedOutputDestinations();

        $this->assertSame([
            BatchCraftingOutputDestination::INVENTORY,
            BatchCraftingOutputDestination::CRAFTED_ITEMS_SET,
        ], $destinations);
    }

    public function test_allowed_output_destinations_for_set_include_a_normal_inventory_set(): void
    {
        $destinations = CraftAndEnchantBatchMode::SET->allowedOutputDestinations();

        $this->assertContains(BatchCraftingOutputDestination::INVENTORY, $destinations);
        $this->assertContains(BatchCraftingOutputDestination::CRAFTED_ITEMS_SET, $destinations);
        $this->assertContains(BatchCraftingOutputDestination::INVENTORY_SET, $destinations);
    }

    public function test_allowed_output_destinations_for_experience_is_empty(): void
    {
        $this->assertSame([], CraftAndEnchantBatchMode::EXPERIENCE->allowedOutputDestinations());
    }
}
