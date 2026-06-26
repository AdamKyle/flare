<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;

class BattleRewardMessageContext
{
    private ?int $requestId = null;

    private ?int $characterId = null;

    private ?int $userId = null;

    private ?BattleRewardStepName $stepName = null;

    public function start(int $requestId, int $characterId, int $userId): void
    {
        $this->requestId = $requestId;
        $this->characterId = $characterId;
        $this->userId = $userId;
        $this->stepName = null;
    }

    public function setStep(BattleRewardStepName $stepName): void
    {
        $this->stepName = $stepName;
    }

    public function clearStep(): void
    {
        $this->stepName = null;
    }

    public function clear(): void
    {
        $this->requestId = null;
        $this->characterId = null;
        $this->userId = null;
        $this->stepName = null;
    }

    public function active(): bool
    {
        return ! is_null($this->requestId)
            && ! is_null($this->characterId)
            && ! is_null($this->userId);
    }

    public function requestId(): ?int
    {
        return $this->requestId;
    }

    public function characterId(): ?int
    {
        return $this->characterId;
    }

    public function userId(): ?int
    {
        return $this->userId;
    }

    public function stepName(): ?BattleRewardStepName
    {
        return $this->stepName;
    }
}
