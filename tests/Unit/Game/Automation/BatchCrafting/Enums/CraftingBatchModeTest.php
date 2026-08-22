<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Enums;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchMode;
use Tests\TestCase;

class CraftingBatchModeTest extends TestCase
{
    public function test_execution_window_size_is_null_for_amount_and_set(): void
    {
        $this->assertNull(CraftingBatchMode::AMOUNT->executionWindowSize());
        $this->assertNull(CraftingBatchMode::SET->executionWindowSize());
    }

    public function test_execution_window_size_is_six_for_experience(): void
    {
        $this->assertSame(6, CraftingBatchMode::EXPERIENCE->executionWindowSize());
    }

    public function test_execution_window_size_is_twenty_three_for_event(): void
    {
        $this->assertSame(23, CraftingBatchMode::EVENT->executionWindowSize());
    }

    public function test_allowed_dispositions_for_amount_and_set(): void
    {
        $expected = [BatchCraftingDisposition::KEEP, BatchCraftingDisposition::SELL, BatchCraftingDisposition::DESTROY];

        $this->assertSame($expected, CraftingBatchMode::AMOUNT->allowedDispositions());
        $this->assertSame($expected, CraftingBatchMode::SET->allowedDispositions());
    }

    public function test_allowed_dispositions_for_experience(): void
    {
        $this->assertSame([
            BatchCraftingDisposition::KEEP,
            BatchCraftingDisposition::SELL,
            BatchCraftingDisposition::DESTROY,
            BatchCraftingDisposition::KEEP_BEST_SELL_REST,
            BatchCraftingDisposition::KEEP_BEST_DESTROY_REST,
        ], CraftingBatchMode::EXPERIENCE->allowedDispositions());
    }

    public function test_allowed_dispositions_for_event_is_keep_only(): void
    {
        $this->assertSame([BatchCraftingDisposition::KEEP], CraftingBatchMode::EVENT->allowedDispositions());
    }

    public function test_allowed_output_destinations_for_amount(): void
    {
        $this->assertSame(
            [BatchCraftingOutputDestination::INVENTORY, BatchCraftingOutputDestination::CRAFTED_ITEMS_SET],
            CraftingBatchMode::AMOUNT->allowedOutputDestinations()
        );
    }

    public function test_allowed_output_destinations_for_set(): void
    {
        $this->assertSame([
            BatchCraftingOutputDestination::INVENTORY,
            BatchCraftingOutputDestination::CRAFTED_ITEMS_SET,
            BatchCraftingOutputDestination::INVENTORY_SET,
        ], CraftingBatchMode::SET->allowedOutputDestinations());
    }

    public function test_allowed_output_destinations_for_experience_and_event_is_empty(): void
    {
        $this->assertSame([], CraftingBatchMode::EXPERIENCE->allowedOutputDestinations());
        $this->assertSame([], CraftingBatchMode::EVENT->allowedOutputDestinations());
    }
}
