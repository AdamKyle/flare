<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Values;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use Tests\TestCase;

class BatchCraftingOperationResultTest extends TestCase
{
    public function test_ended_result_exposes_the_end_reason_and_defaults_gold_spent_to_zero(): void
    {
        $result = BatchCraftingOperationResult::ended(BatchCraftingEndReason::AMOUNT_REACHED);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
        $this->assertNull($result->actionStatus());
        $this->assertFalse($result->didCraft());
        $this->assertSame(0, $result->goldSpent());
        $this->assertSame(0, $result->goldGained());
    }

    public function test_ended_result_accepts_an_explicit_gold_spent_amount(): void
    {
        $result = BatchCraftingOperationResult::ended(BatchCraftingEndReason::NO_GOLD, 50);

        $this->assertSame(50, $result->goldSpent());
    }

    public function test_kept_result_exposes_the_kept_action_status_and_gold_spent(): void
    {
        $result = BatchCraftingOperationResult::kept(10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertNull($result->endReason());
        $this->assertTrue($result->didCraft());
        $this->assertSame(10, $result->goldSpent());
        $this->assertSame(0, $result->goldGained());
    }

    public function test_sold_result_exposes_the_sold_action_status_gold_spent_and_gold_gained(): void
    {
        $result = BatchCraftingOperationResult::sold(10, 25);

        $this->assertSame(BatchCraftingActionStatus::SOLD, $result->actionStatus());
        $this->assertTrue($result->didCraft());
        $this->assertSame(10, $result->goldSpent());
        $this->assertSame(25, $result->goldGained());
    }

    public function test_destroyed_result_exposes_the_destroyed_action_status_and_gold_spent(): void
    {
        $result = BatchCraftingOperationResult::destroyed(10);

        $this->assertSame(BatchCraftingActionStatus::DESTROYED, $result->actionStatus());
        $this->assertTrue($result->didCraft());
        $this->assertSame(10, $result->goldSpent());
    }

    public function test_failed_result_exposes_the_failed_action_status_and_gold_spent(): void
    {
        $result = BatchCraftingOperationResult::failed(10);

        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertFalse($result->didCraft());
        $this->assertNull($result->endReason());
        $this->assertSame(10, $result->goldSpent());
    }

    public function test_failed_and_ended_result_exposes_failed_status_the_supplied_end_reason_and_gold_spent(): void
    {
        $result = BatchCraftingOperationResult::failedAndEnded(BatchCraftingEndReason::FAILED, 10);

        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertSame(BatchCraftingEndReason::FAILED, $result->endReason());
        $this->assertFalse($result->didCraft());
        $this->assertSame(10, $result->goldSpent());
        $this->assertSame(0, $result->goldGained());
    }

    public function test_crafted_result_exposes_the_crafted_action_status_and_gold_spent(): void
    {
        $result = BatchCraftingOperationResult::crafted(10);

        $this->assertSame(BatchCraftingActionStatus::CRAFTED, $result->actionStatus());
        $this->assertTrue($result->didCraft());
        $this->assertSame(10, $result->goldSpent());
        $this->assertSame(0, $result->goldGained());
    }

    public function test_skipped_result_exposes_the_skipped_action_status_with_no_gold(): void
    {
        $result = BatchCraftingOperationResult::skipped();

        $this->assertSame(BatchCraftingActionStatus::SKIPPED, $result->actionStatus());
        $this->assertFalse($result->didCraft());
        $this->assertSame(0, $result->goldSpent());
        $this->assertSame(0, $result->goldGained());
    }

    public function test_kept_with_displaced_sale_result_exposes_kept_status_gold_gained_and_one_additional_sold_count(): void
    {
        $result = BatchCraftingOperationResult::keptWithDisplacedSale(10, 25);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertTrue($result->didCraft());
        $this->assertSame(10, $result->goldSpent());
        $this->assertSame(25, $result->goldGained());
        $this->assertSame(1, $result->additionalSoldCount());
        $this->assertSame(0, $result->additionalDestroyedCount());
    }

    public function test_kept_with_displaced_destroy_result_exposes_kept_status_and_one_additional_destroyed_count(): void
    {
        $result = BatchCraftingOperationResult::keptWithDisplacedDestroy(10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertTrue($result->didCraft());
        $this->assertSame(10, $result->goldSpent());
        $this->assertSame(0, $result->goldGained());
        $this->assertSame(0, $result->additionalSoldCount());
        $this->assertSame(1, $result->additionalDestroyedCount());
    }

    public function test_default_additional_disposal_counts_are_zero(): void
    {
        $result = BatchCraftingOperationResult::kept(10);

        $this->assertSame(0, $result->additionalSoldCount());
        $this->assertSame(0, $result->additionalDestroyedCount());
    }

    public function test_with_xp_gained_preserves_action_status_and_gold_while_attaching_the_xp(): void
    {
        $result = BatchCraftingOperationResult::kept(10)->withXpGained(15);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(10, $result->goldSpent());
        $this->assertSame(15, $result->xpGained());
    }

    public function test_default_xp_gained_is_zero(): void
    {
        $result = BatchCraftingOperationResult::kept(10);

        $this->assertSame(0, $result->xpGained());
    }

    public function test_with_end_reason_preserves_action_status_and_gold_while_attaching_the_reason(): void
    {
        $result = BatchCraftingOperationResult::kept(10)->withEndReason(BatchCraftingEndReason::AMOUNT_REACHED);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
        $this->assertTrue($result->didCraft());
        $this->assertSame(10, $result->goldSpent());
    }

    public function test_with_end_reason_preserves_additional_disposal_counts_and_xp_gained(): void
    {
        $result = BatchCraftingOperationResult::keptWithDisplacedSale(10, 25)
            ->withXpGained(15)
            ->withEndReason(BatchCraftingEndReason::AMOUNT_REACHED);

        $this->assertSame(1, $result->additionalSoldCount());
        $this->assertSame(15, $result->xpGained());
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
    }
}
