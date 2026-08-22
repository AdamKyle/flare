<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Enums;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use Tests\TestCase;

class BatchCraftingActionStatusTest extends TestCase
{
    public function test_did_craft_is_true_for_every_successful_status(): void
    {
        $this->assertTrue(BatchCraftingActionStatus::KEPT->didCraft());
        $this->assertTrue(BatchCraftingActionStatus::SOLD->didCraft());
        $this->assertTrue(BatchCraftingActionStatus::DESTROYED->didCraft());
        $this->assertTrue(BatchCraftingActionStatus::LISTED->didCraft());
        $this->assertTrue(BatchCraftingActionStatus::DISENCHANTED->didCraft());
        $this->assertTrue(BatchCraftingActionStatus::USED->didCraft());
        $this->assertTrue(BatchCraftingActionStatus::CRAFTED->didCraft());
    }

    public function test_did_craft_is_false_for_applied_failed_and_skipped(): void
    {
        $this->assertFalse(BatchCraftingActionStatus::APPLIED->didCraft());
        $this->assertFalse(BatchCraftingActionStatus::FAILED->didCraft());
        $this->assertFalse(BatchCraftingActionStatus::SKIPPED->didCraft());
    }
}
