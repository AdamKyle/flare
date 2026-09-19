<?php

namespace App\Game\BattleRewardProcessing\Values;

use Throwable;

class BattleRewardProcessingResult
{
    /**
     * @param bool $successful
     * @param ?Throwable $failure
     */
    public function __construct(
        private readonly bool $successful,
        private readonly ?Throwable $failure = null,
    ) {}

    /**
     * Build a successful reward-processing result.
     *
     * @return BattleRewardProcessingResult
     */
    public static function success(): BattleRewardProcessingResult
    {
        return new BattleRewardProcessingResult(true);
    }

    /**
     * Build a failed reward-processing result with the original failure preserved for the Job boundary.
     *
     * @param Throwable $failure
     * @return BattleRewardProcessingResult
     */
    public static function failed(Throwable $failure): BattleRewardProcessingResult
    {
        return new BattleRewardProcessingResult(false, $failure);
    }

    /**
     * Determine whether reward processing completed successfully.
     *
     * @return bool
     */
    public function successful(): bool
    {
        return $this->successful;
    }

    /**
     * Return the original reward-processing failure when processing did not succeed.
     *
     * @return ?Throwable
     */
    public function failure(): ?Throwable
    {
        return $this->failure;
    }
}
