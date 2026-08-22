<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Enums;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\EnchantingBatchMode;
use Tests\TestCase;

class EnchantingBatchModeTest extends TestCase
{
    public function test_execution_window_size_is_twenty_three(): void
    {
        $this->assertSame(23, EnchantingBatchMode::EVENT->executionWindowSize());
    }

    public function test_allowed_dispositions_is_keep_only(): void
    {
        $this->assertSame([BatchCraftingDisposition::KEEP], EnchantingBatchMode::EVENT->allowedDispositions());
    }

    public function test_allowed_output_destinations_is_empty(): void
    {
        $this->assertSame([], EnchantingBatchMode::EVENT->allowedOutputDestinations());
    }
}
