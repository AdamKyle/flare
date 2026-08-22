<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Enums;

use App\Game\Automation\BatchCrafting\Enums\AlchemyBatchMode;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use Tests\TestCase;

class AlchemyBatchModeTest extends TestCase
{
    public function test_execution_window_size_is_null_for_amount_and_six_for_experience(): void
    {
        $this->assertNull(AlchemyBatchMode::AMOUNT->executionWindowSize());
        $this->assertSame(6, AlchemyBatchMode::EXPERIENCE->executionWindowSize());
    }

    public function test_allowed_dispositions_for_amount_excludes_keep_best_destroy_rest(): void
    {
        $dispositions = AlchemyBatchMode::AMOUNT->allowedDispositions();

        $this->assertContains(BatchCraftingDisposition::USE_NOW, $dispositions);
        $this->assertNotContains(BatchCraftingDisposition::KEEP_BEST_DESTROY_REST, $dispositions);
    }

    public function test_allowed_dispositions_for_experience_includes_keep_best_destroy_rest(): void
    {
        $dispositions = AlchemyBatchMode::EXPERIENCE->allowedDispositions();

        $this->assertContains(BatchCraftingDisposition::KEEP_BEST_DESTROY_REST, $dispositions);
        $this->assertContains(BatchCraftingDisposition::USE_NOW, $dispositions);
    }

    public function test_allowed_output_destinations_is_always_empty(): void
    {
        $this->assertSame([], AlchemyBatchMode::AMOUNT->allowedOutputDestinations());
        $this->assertSame([], AlchemyBatchMode::EXPERIENCE->allowedOutputDestinations());
    }
}
