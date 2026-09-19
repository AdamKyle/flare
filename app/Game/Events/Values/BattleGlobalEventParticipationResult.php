<?php

namespace App\Game\Events\Values;

class BattleGlobalEventParticipationResult
{
    /**
     * @param bool $participated
     * @param int $appliedKillCount
     * @param int $thresholdRewardsProcessed
     * @param bool $goalAdvanced
     */
    public function __construct(
        private readonly bool $participated,
        private readonly int $appliedKillCount,
        private readonly int $thresholdRewardsProcessed,
        private readonly bool $goalAdvanced,
    ) {}

    /**
     * Build the no-op result for a Character with no applicable Global Event participation.
     *
     * @return self
     */
    public static function none(): self
    {
        return new self(false, 0, 0, false);
    }

    /**
     * Whether the Character's participation actually changed.
     *
     * @return bool
     */
    public function participated(): bool
    {
        return $this->participated;
    }

    /**
     * The kill count actually applied, capped to the goal's remaining kills.
     *
     * @return int
     */
    public function appliedKillCount(): int
    {
        return $this->appliedKillCount;
    }

    /**
     * How many threshold reward boundaries were processed by this operation.
     *
     * @return int
     */
    public function thresholdRewardsProcessed(): int
    {
        return $this->thresholdRewardsProcessed;
    }

    /**
     * Whether the current stepped goal advanced to the next step.
     *
     * @return bool
     */
    public function goalAdvanced(): bool
    {
        return $this->goalAdvanced;
    }
}
