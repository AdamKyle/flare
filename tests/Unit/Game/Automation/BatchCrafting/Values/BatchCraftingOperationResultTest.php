<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Values;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use Tests\TestCase;

class BatchCraftingOperationResultTest extends TestCase
{
    public function test_ended_result_exposes_the_end_reason(): void
    {
        $result = BatchCraftingOperationResult::ended(BatchCraftingEndReason::AMOUNT_REACHED);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
        $this->assertNull($result->actionStatus());
        $this->assertFalse($result->didCraft());
    }

    public function test_kept_result_exposes_the_kept_action_status(): void
    {
        $result = BatchCraftingOperationResult::kept();

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertNull($result->endReason());
        $this->assertTrue($result->didCraft());
    }

    public function test_sold_result_exposes_the_sold_action_status(): void
    {
        $result = BatchCraftingOperationResult::sold();

        $this->assertSame(BatchCraftingActionStatus::SOLD, $result->actionStatus());
        $this->assertTrue($result->didCraft());
    }

    public function test_destroyed_result_exposes_the_destroyed_action_status(): void
    {
        $result = BatchCraftingOperationResult::destroyed();

        $this->assertSame(BatchCraftingActionStatus::DESTROYED, $result->actionStatus());
        $this->assertTrue($result->didCraft());
    }

    public function test_failed_result_exposes_the_failed_action_status(): void
    {
        $result = BatchCraftingOperationResult::failed();

        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertFalse($result->didCraft());
    }
}
