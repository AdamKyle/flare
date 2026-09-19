<?php

namespace App\Game\BattleRewardProcessing\Values;

use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;

class BattleRewardStepPlan
{
    /**
     * @param array $steps
     */
    public function __construct(
        private readonly array $steps,
    ) {}

    /**
     * The ordered list of planned step names for this request.
     *
     * @return array
     */
    public function steps(): array
    {
        return $this->steps;
    }

    /**
     * Determine whether the given step name is included in this plan.
     *
     * @param BattleRewardStepName $stepName
     * @return bool
     */
    public function contains(BattleRewardStepName $stepName): bool
    {
        return in_array($stepName, $this->steps, true);
    }
}
