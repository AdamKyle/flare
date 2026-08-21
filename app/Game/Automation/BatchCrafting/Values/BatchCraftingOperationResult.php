<?php

namespace App\Game\Automation\BatchCrafting\Values;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;

class BatchCraftingOperationResult
{
    /**
     * @param  BatchCraftingActionStatus|null  $actionStatus
     * @param  BatchCraftingEndReason|null  $endReason
     * @param  int  $goldSpent
     * @param  int  $goldGained
     * @param  int  $additionalSoldCount
     * @param  int  $additionalDestroyedCount
     * @param  int  $xpGained
     */
    private function __construct(
        private readonly ?BatchCraftingActionStatus $actionStatus,
        private readonly ?BatchCraftingEndReason $endReason,
        private readonly int $goldSpent,
        private readonly int $goldGained,
        private readonly int $additionalSoldCount = 0,
        private readonly int $additionalDestroyedCount = 0,
        private readonly int $xpGained = 0,
    ) {}

    /**
     * Build a result that ends the Batch Crafting run with the given reason.
     *
     * @param  BatchCraftingEndReason  $reason  The reason the run ended.
     * @param  int  $goldSpent  The Gold spent before the run ended, when applicable.
     * @return self A result carrying the end reason.
     */
    public static function ended(BatchCraftingEndReason $reason, int $goldSpent = 0): self
    {
        return new self(null, $reason, $goldSpent, 0);
    }

    /**
     * Build a result representing a crafted item that was kept.
     *
     * @param  int  $goldSpent  The Gold spent crafting the item.
     * @return self A result with the kept action status.
     */
    public static function kept(int $goldSpent): self
    {
        return new self(BatchCraftingActionStatus::KEPT, null, $goldSpent, 0);
    }

    /**
     * Build a result representing a crafted item that was sold.
     *
     * @param  int  $goldSpent  The Gold spent crafting the item.
     * @param  int  $goldGained  The Gold gained selling the item.
     * @return self A result with the sold action status.
     */
    public static function sold(int $goldSpent, int $goldGained): self
    {
        return new self(BatchCraftingActionStatus::SOLD, null, $goldSpent, $goldGained);
    }

    /**
     * Build a result representing a new best item that displaced and sold the previous best.
     *
     * @param  int  $goldSpent  The Gold spent crafting the new best item.
     * @param  int  $goldGained  The Gold gained selling the displaced previous best item.
     * @return self A result with the kept action status, the sale Gold gained, and one additional sold count.
     */
    public static function keptWithDisplacedSale(int $goldSpent, int $goldGained): self
    {
        return new self(BatchCraftingActionStatus::KEPT, null, $goldSpent, $goldGained, additionalSoldCount: 1);
    }

    /**
     * Build a result representing a new best item that displaced and destroyed the previous best.
     *
     * @param  int  $goldSpent  The Gold spent crafting the new best item.
     * @return self A result with the kept action status and one additional destroyed count.
     */
    public static function keptWithDisplacedDestroy(int $goldSpent): self
    {
        return new self(BatchCraftingActionStatus::KEPT, null, $goldSpent, 0, additionalDestroyedCount: 1);
    }

    /**
     * Build a result representing a crafted item that was destroyed.
     *
     * @param  int  $goldSpent  The Gold spent crafting the item.
     * @return self A result with the destroyed action status.
     */
    public static function destroyed(int $goldSpent): self
    {
        return new self(BatchCraftingActionStatus::DESTROYED, null, $goldSpent, 0);
    }

    /**
     * Build a result representing a failed crafting attempt.
     *
     * @param  int  $goldSpent  The Gold spent on the failed attempt.
     * @return self A result with the failed action status.
     */
    public static function failed(int $goldSpent): self
    {
        return new self(BatchCraftingActionStatus::FAILED, null, $goldSpent, 0);
    }

    /**
     * Build a result representing an item that was successfully crafted and contributed rather than retained.
     *
     * @param  int  $goldSpent  The Gold spent crafting the item.
     * @return self A result with the crafted action status.
     */
    public static function crafted(int $goldSpent): self
    {
        return new self(BatchCraftingActionStatus::CRAFTED, null, $goldSpent, 0);
    }

    /**
     * Build a result representing an action slot that was skipped because no eligible target was available.
     *
     * @return self A result with the skipped action status.
     */
    public static function skipped(): self
    {
        return new self(BatchCraftingActionStatus::SKIPPED, null, 0, 0);
    }

    /**
     * Build a result representing an attempt that charged Gold but ended the run at commit time.
     *
     * @param  BatchCraftingEndReason  $reason  The reason the run ended.
     * @param  int  $goldSpent  The Gold spent on the attempt before it ended the run.
     * @return self A result with the failed action status and the supplied end reason.
     */
    public static function failedAndEnded(BatchCraftingEndReason $reason, int $goldSpent): self
    {
        return new self(BatchCraftingActionStatus::FAILED, $reason, $goldSpent, 0);
    }

    /**
     * Build a copy of this result carrying the supplied terminal end reason.
     *
     * @param  BatchCraftingEndReason  $reason  The reason the run ended.
     * @return self A result with the same action status and Gold totals, carrying the end reason.
     */
    public function withEndReason(BatchCraftingEndReason $reason): self
    {
        return new self($this->actionStatus, $reason, $this->goldSpent, $this->goldGained, $this->additionalSoldCount, $this->additionalDestroyedCount, $this->xpGained);
    }

    /**
     * Build a copy of this result carrying the supplied factual XP gained by the craft attempt.
     *
     * @param  int  $xpGained  The factual XP gained by the craft attempt.
     * @return self A result with the same action status and totals, carrying the XP gained.
     */
    public function withXpGained(int $xpGained): self
    {
        return new self($this->actionStatus, $this->endReason, $this->goldSpent, $this->goldGained, $this->additionalSoldCount, $this->additionalDestroyedCount, $xpGained);
    }

    /**
     * Return the action status recorded by this result, when an attempt occurred.
     *
     * @return BatchCraftingActionStatus|null The recorded action status, or null when no attempt occurred.
     */
    public function actionStatus(): ?BatchCraftingActionStatus
    {
        return $this->actionStatus;
    }

    /**
     * Return the reason the Batch Crafting run ended, when applicable.
     *
     * @return BatchCraftingEndReason|null The end reason, or null when the run did not end.
     */
    public function endReason(): ?BatchCraftingEndReason
    {
        return $this->endReason;
    }

    /**
     * Return the Gold spent on this operation.
     *
     * @return int The Gold spent.
     */
    public function goldSpent(): int
    {
        return $this->goldSpent;
    }

    /**
     * Return the Gold gained from this operation.
     *
     * @return int The Gold gained.
     */
    public function goldGained(): int
    {
        return $this->goldGained;
    }

    /**
     * Return the additional number of items sold as a side effect of this operation.
     *
     * @return int The additional sold count, on top of the primary action status.
     */
    public function additionalSoldCount(): int
    {
        return $this->additionalSoldCount;
    }

    /**
     * Return the additional number of items destroyed as a side effect of this operation.
     *
     * @return int The additional destroyed count, on top of the primary action status.
     */
    public function additionalDestroyedCount(): int
    {
        return $this->additionalDestroyedCount;
    }

    /**
     * Return the factual XP gained by this operation's craft attempt.
     *
     * @return int The factual XP gained.
     */
    public function xpGained(): int
    {
        return $this->xpGained;
    }

    /**
     * Determine whether this result represents a successfully crafted item.
     *
     * @return bool True when the recorded action status counts as a successful craft.
     */
    public function didCraft(): bool
    {
        if (is_null($this->actionStatus)) {
            return false;
        }

        return $this->actionStatus->didCraft();
    }
}
