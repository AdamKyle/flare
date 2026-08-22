<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Enums;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\TrinketryBatchMode;
use Tests\TestCase;

class TrinketryBatchModeTest extends TestCase
{
    public function test_execution_window_size_is_six(): void
    {
        $this->assertSame(6, TrinketryBatchMode::EXPERIENCE->executionWindowSize());
    }

    public function test_allowed_dispositions_is_keep_destroy_and_keep_best_destroy_rest_only(): void
    {
        $dispositions = TrinketryBatchMode::EXPERIENCE->allowedDispositions();

        $this->assertCount(3, $dispositions);
        $this->assertContains(BatchCraftingDisposition::KEEP, $dispositions);
        $this->assertContains(BatchCraftingDisposition::DESTROY, $dispositions);
        $this->assertContains(BatchCraftingDisposition::KEEP_BEST_DESTROY_REST, $dispositions);
    }

    public function test_allowed_output_destinations_is_empty(): void
    {
        $this->assertSame([], TrinketryBatchMode::EXPERIENCE->allowedOutputDestinations());
    }
}
