<?php

namespace App\Game\Automation\BatchCrafting\Values;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;

class BatchCraftingOperationResult
{
    /**
     * @param  BatchCraftingActionStatus|null  $actionStatus  The recorded action outcome, when the run did not end.
     * @param  BatchCraftingEndReason|null  $endReason  The reason the run ended, when applicable.
     */
    private function __construct(
        private readonly ?BatchCraftingActionStatus $actionStatus,
        private readonly ?BatchCraftingEndReason $endReason,
    ) {}

    /**
     * Build a result that ends the Batch Crafting run with the given reason.
     *
     * @param  BatchCraftingEndReason  $reason  The reason the run ended.
     * @return self A result carrying the end reason.
     */
    public static function ended(BatchCraftingEndReason $reason): self
    {
        return new self(null, $reason);
    }

    /**
     * Build a result representing a crafted item that was kept.
     *
     * @return self A result with the kept action status.
     */
    public static function kept(): self
    {
        return new self(BatchCraftingActionStatus::KEPT, null);
    }

    /**
     * Build a result representing a crafted item that was sold.
     *
     * @return self A result with the sold action status.
     */
    public static function sold(): self
    {
        return new self(BatchCraftingActionStatus::SOLD, null);
    }

    /**
     * Build a result representing a crafted item that was destroyed.
     *
     * @return self A result with the destroyed action status.
     */
    public static function destroyed(): self
    {
        return new self(BatchCraftingActionStatus::DESTROYED, null);
    }

    /**
     * Build a result representing a failed crafting attempt.
     *
     * @return self A result with the failed action status.
     */
    public static function failed(): self
    {
        return new self(BatchCraftingActionStatus::FAILED, null);
    }

    /**
     * Return the action status recorded by this result, when the run did not end.
     *
     * @return BatchCraftingActionStatus|null The recorded action status, or null when the run ended.
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
