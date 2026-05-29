<?php

namespace App\Game\Automation\Values;

use App\Game\Automation\Enums\AutomatedFightResultType;

class AutomatedFightResult
{
    private AutomatedFightResultType $resultType;

    private ?int $monsterId = null;

    private ?string $monsterName = null;

    private bool $bountyTarget = false;

    private bool $training = false;

    private ?int $failedBountyMonsterId = null;

    private bool $trainedForFailedBounty = false;

    private int $kills = 0;

    private int $trainingKills = 0;

    private int $bountyKills = 0;

    private int $totalCreatures = 0;

    private int $totalXp = 0;

    private int $totalSkillXp = 0;

    private int $totalFactionPoints = 0;

    private bool $characterDied = false;

    private bool $endedAutomation = false;

    private array $fightData = [];

    private int $stalledAttempt = 0;

    private ?array $warningNotice = null;

    /**
     * Set up the result.
     */
    public function setUp(AutomatedFightResultType $resultType): AutomatedFightResult
    {
        $this->resultType = $resultType;
        $this->monsterId = null;
        $this->monsterName = null;
        $this->bountyTarget = false;
        $this->training = false;
        $this->failedBountyMonsterId = null;
        $this->trainedForFailedBounty = false;
        $this->kills = 0;
        $this->trainingKills = 0;
        $this->bountyKills = 0;
        $this->totalCreatures = 0;
        $this->totalXp = 0;
        $this->totalSkillXp = 0;
        $this->totalFactionPoints = 0;
        $this->characterDied = false;
        $this->endedAutomation = false;
        $this->fightData = [];
        $this->stalledAttempt = 0;
        $this->warningNotice = null;

        return $this;
    }

    /**
     * Set the monster id.
     */
    public function setMonsterId(?int $monsterId): AutomatedFightResult
    {
        $this->monsterId = $monsterId;

        return $this;
    }

    /**
     * Set the monster name.
     */
    public function setMonsterName(?string $monsterName): AutomatedFightResult
    {
        $this->monsterName = $monsterName;

        return $this;
    }

    /**
     * Set whether this is the bounty target.
     */
    public function setBountyTarget(bool $bountyTarget): AutomatedFightResult
    {
        $this->bountyTarget = $bountyTarget;

        return $this;
    }

    /**
     * Set whether this is training.
     */
    public function setTraining(bool $training): AutomatedFightResult
    {
        $this->training = $training;

        return $this;
    }

    /**
     * Set the failed bounty monster id.
     */
    public function setFailedBountyMonsterId(?int $failedBountyMonsterId): AutomatedFightResult
    {
        $this->failedBountyMonsterId = $failedBountyMonsterId;

        return $this;
    }

    /**
     * Set whether training has completed for the failed bounty.
     */
    public function setTrainedForFailedBounty(bool $trainedForFailedBounty): AutomatedFightResult
    {
        $this->trainedForFailedBounty = $trainedForFailedBounty;

        return $this;
    }

    /**
     * Set the total kills.
     */
    public function setKills(int $kills): AutomatedFightResult
    {
        $this->kills = $kills;

        return $this;
    }

    /**
     * Set the training kills.
     */
    public function setTrainingKills(int $trainingKills): AutomatedFightResult
    {
        $this->trainingKills = $trainingKills;

        return $this;
    }

    /**
     * Set the bounty kills.
     */
    public function setBountyKills(int $bountyKills): AutomatedFightResult
    {
        $this->bountyKills = $bountyKills;

        return $this;
    }

    /**
     * Set the total creatures.
     */
    public function setTotalCreatures(int $totalCreatures): AutomatedFightResult
    {
        $this->totalCreatures = $totalCreatures;

        return $this;
    }

    /**
     * Set the total XP.
     */
    public function setTotalXp(int $totalXp): AutomatedFightResult
    {
        $this->totalXp = $totalXp;

        return $this;
    }

    /**
     * Set the total skill XP.
     */
    public function setTotalSkillXp(int $totalSkillXp): AutomatedFightResult
    {
        $this->totalSkillXp = $totalSkillXp;

        return $this;
    }

    /**
     * Set the total faction points.
     */
    public function setTotalFactionPoints(int $totalFactionPoints): AutomatedFightResult
    {
        $this->totalFactionPoints = $totalFactionPoints;

        return $this;
    }

    /**
     * Set whether the character died.
     */
    public function setCharacterDied(bool $characterDied): AutomatedFightResult
    {
        $this->characterDied = $characterDied;

        return $this;
    }

    /**
     * Set whether automation ended.
     */
    public function setEndedAutomation(bool $endedAutomation): AutomatedFightResult
    {
        $this->endedAutomation = $endedAutomation;

        return $this;
    }

    /**
     * Set the fight data.
     */
    public function setFightData(array $fightData): AutomatedFightResult
    {
        $this->fightData = $fightData;

        return $this;
    }

    /**
     * Set the stalled attempt.
     */
    public function setStalledAttempt(int $stalledAttempt): AutomatedFightResult
    {
        $this->stalledAttempt = $stalledAttempt;

        return $this;
    }

    /**
     * Set the warning notice.
     */
    public function setWarningNotice(?array $warningNotice): AutomatedFightResult
    {
        $this->warningNotice = $warningNotice;

        return $this;
    }

    /**
     * Get the result type.
     */
    public function getResultType(): AutomatedFightResultType
    {
        return $this->resultType;
    }

    /**
     * Get the monster id.
     */
    public function getMonsterId(): ?int
    {
        return $this->monsterId;
    }

    /**
     * Get the monster name.
     */
    public function getMonsterName(): ?string
    {
        return $this->monsterName;
    }

    /**
     * Is this the bounty target?
     */
    public function isBountyTarget(): bool
    {
        return $this->bountyTarget;
    }

    /**
     * Is this training?
     */
    public function isTraining(): bool
    {
        return $this->training;
    }

    /**
     * Get the failed bounty monster id.
     */
    public function getFailedBountyMonsterId(): ?int
    {
        return $this->failedBountyMonsterId;
    }

    /**
     * Has training completed for the failed bounty?
     */
    public function hasTrainedForFailedBounty(): bool
    {
        return $this->trainedForFailedBounty;
    }

    /**
     * Get the total kills.
     */
    public function getKills(): int
    {
        return $this->kills;
    }

    /**
     * Get the training kills.
     */
    public function getTrainingKills(): int
    {
        return $this->trainingKills;
    }

    /**
     * Get the bounty kills.
     */
    public function getBountyKills(): int
    {
        return $this->bountyKills;
    }

    /**
     * Get the total creatures.
     */
    public function getTotalCreatures(): int
    {
        return $this->totalCreatures;
    }

    /**
     * Get the total XP.
     */
    public function getTotalXp(): int
    {
        return $this->totalXp;
    }

    /**
     * Get the total skill XP.
     */
    public function getTotalSkillXp(): int
    {
        return $this->totalSkillXp;
    }

    /**
     * Get the total faction points.
     */
    public function getTotalFactionPoints(): int
    {
        return $this->totalFactionPoints;
    }

    /**
     * Did the character die?
     */
    public function hasCharacterDied(): bool
    {
        return $this->characterDied;
    }

    /**
     * Did automation end?
     */
    public function hasEndedAutomation(): bool
    {
        return $this->endedAutomation;
    }

    /**
     * Get the fight data.
     */
    public function getFightData(): array
    {
        return $this->fightData;
    }

    /**
     * Get the stalled attempt.
     */
    public function getStalledAttempt(): int
    {
        return $this->stalledAttempt;
    }

    /**
     * Get the warning notice.
     */
    public function getWarningNotice(): ?array
    {
        return $this->warningNotice;
    }
}
