<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Enums;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use Tests\TestCase;

class BatchCraftingDispositionTest extends TestCase
{
    public function test_keeps_best_is_true_for_every_keep_best_disposition(): void
    {
        $this->assertTrue(BatchCraftingDisposition::KEEP_BEST_SELL_REST->keepsBest());
        $this->assertTrue(BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->keepsBest());
        $this->assertTrue(BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST->keepsBest());
    }

    public function test_keeps_best_is_false_for_every_non_keep_best_disposition(): void
    {
        $this->assertFalse(BatchCraftingDisposition::KEEP->keepsBest());
        $this->assertFalse(BatchCraftingDisposition::SELL->keepsBest());
        $this->assertFalse(BatchCraftingDisposition::DESTROY->keepsBest());
        $this->assertFalse(BatchCraftingDisposition::LIST->keepsBest());
        $this->assertFalse(BatchCraftingDisposition::DISENCHANT->keepsBest());
        $this->assertFalse(BatchCraftingDisposition::USE_NOW->keepsBest());
    }

    public function test_legacy_keep_highest_value_is_not_a_legal_case(): void
    {
        $this->assertNull(BatchCraftingDisposition::tryFrom('keep_highest'));
    }
}
