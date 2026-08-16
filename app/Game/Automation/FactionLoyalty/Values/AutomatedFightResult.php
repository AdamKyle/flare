<?php

namespace App\Game\Automation\FactionLoyalty\Values;

use App\Game\Automation\FactionLoyalty\Enums\AutomatedFightResultType;

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

    private ?string $logEntryId = null;

    /**
     * Set up the result.
     *
     * @param  AutomatedFightResultType  $resultType  The fight result type.
     * @return AutomatedFightResult The reset result instance.
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
        $this->logEntryId = null;

        return $this;
    }

    /**
     * Set the monster id.
     *
     * @param  int|null  $monsterId  The monster id.
     * @return AutomatedFightResult The result instance.
     */
    public function setMonsterId(?int $monsterId): AutomatedFightResult
    {
        $this->monsterId = $monsterId;

        return $this;
    }

    /**
     * Set the monster name.
     *
     * @param  string|null  $monsterName  The monster name.
     * @return AutomatedFightResult The result instance.
     */
    public function setMonsterName(?string $monsterName): AutomatedFightResult
    {
        $this->monsterName = $monsterName;

        return $this;
    }

    /**
     * Set whether this is the bounty target.
     *
     * @param  bool  $bountyTarget  Whether the monster is the bounty target.
     * @return AutomatedFightResult The result instance.
     */
    public function setBountyTarget(bool $bountyTarget): AutomatedFightResult
    {
        $this->bountyTarget = $bountyTarget;

        return $this;
    }

    /**
     * Set whether this is training.
     *
     * @param  bool  $training  Whether the fight is recovery training.
     * @return AutomatedFightResult The result instance.
     */
    public function setTraining(bool $training): AutomatedFightResult
    {
        $this->training = $training;

        return $this;
    }

    /**
     * Set the failed bounty monster id.
     *
     * @param  int|null  $failedBountyMonsterId  The failed bounty monster id.
     * @return AutomatedFightResult The result instance.
     */
    public function setFailedBountyMonsterId(?int $failedBountyMonsterId): AutomatedFightResult
    {
        $this->failedBountyMonsterId = $failedBountyMonsterId;

        return $this;
    }

    /**
     * Set whether training has completed for the failed bounty.
     *
     * @param  bool  $trainedForFailedBounty  Whether recovery training has completed.
     * @return AutomatedFightResult The result instance.
     */
    public function setTrainedForFailedBounty(bool $trainedForFailedBounty): AutomatedFightResult
    {
        $this->trainedForFailedBounty = $trainedForFailedBounty;

        return $this;
    }

    /**
     * Set the total kills.
     *
     * @param  int  $kills  The total kill count.
     * @return AutomatedFightResult The result instance.
     */
    public function setKills(int $kills): AutomatedFightResult
    {
        $this->kills = $kills;

        return $this;
    }

    /**
     * Set the training kills.
     *
     * @param  int  $trainingKills  The training kill count.
     * @return AutomatedFightResult The result instance.
     */
    public function setTrainingKills(int $trainingKills): AutomatedFightResult
    {
        $this->trainingKills = $trainingKills;

        return $this;
    }

    /**
     * Set the bounty kills.
     *
     * @param  int  $bountyKills  The bounty kill count.
     * @return AutomatedFightResult The result instance.
     */
    public function setBountyKills(int $bountyKills): AutomatedFightResult
    {
        $this->bountyKills = $bountyKills;

        return $this;
    }

    /**
     * Set the total creatures.
     *
     * @param  int  $totalCreatures  The total creature count.
     * @return AutomatedFightResult The result instance.
     */
    public function setTotalCreatures(int $totalCreatures): AutomatedFightResult
    {
        $this->totalCreatures = $totalCreatures;

        return $this;
    }

    /**
     * Set the total XP.
     *
     * @param  int  $totalXp  The total xp awarded.
     * @return AutomatedFightResult The result instance.
     */
    public function setTotalXp(int $totalXp): AutomatedFightResult
    {
        $this->totalXp = $totalXp;

        return $this;
    }

    /**
     * Set the total skill XP.
     *
     * @param  int  $totalSkillXp  The total skill xp awarded.
     * @return AutomatedFightResult The result instance.
     */
    public function setTotalSkillXp(int $totalSkillXp): AutomatedFightResult
    {
        $this->totalSkillXp = $totalSkillXp;

        return $this;
    }

    /**
     * Set the total faction points.
     *
     * @param  int  $totalFactionPoints  The total faction points awarded.
     * @return AutomatedFightResult The result instance.
     */
    public function setTotalFactionPoints(int $totalFactionPoints): AutomatedFightResult
    {
        $this->totalFactionPoints = $totalFactionPoints;

        return $this;
    }

    /**
     * Set whether the character died.
     *
     * @param  bool  $characterDied  Whether the character died.
     * @return AutomatedFightResult The result instance.
     */
    public function setCharacterDied(bool $characterDied): AutomatedFightResult
    {
        $this->characterDied = $characterDied;

        return $this;
    }

    /**
     * Set whether automation ended.
     *
     * @param  bool  $endedAutomation  Whether the automation ended.
     * @return AutomatedFightResult The result instance.
     */
    public function setEndedAutomation(bool $endedAutomation): AutomatedFightResult
    {
        $this->endedAutomation = $endedAutomation;

        return $this;
    }

    /**
     * Set the fight data.
     *
     * @param  array  $fightData  The fight result payload.
     * @return AutomatedFightResult The result instance.
     */
    public function setFightData(array $fightData): AutomatedFightResult
    {
        $this->fightData = $fightData;

        return $this;
    }

    /**
     * Set the stalled attempt.
     *
     * @param  int  $stalledAttempt  The stalled attempt count.
     * @return AutomatedFightResult The result instance.
     */
    public function setStalledAttempt(int $stalledAttempt): AutomatedFightResult
    {
        $this->stalledAttempt = $stalledAttempt;

        return $this;
    }

    /**
     * Set the warning notice.
     *
     * @param  array|null  $warningNotice  The warning notice payload.
     * @return AutomatedFightResult The result instance.
     */
    public function setWarningNotice(?array $warningNotice): AutomatedFightResult
    {
        $this->warningNotice = $warningNotice;

        return $this;
    }

    /**
     * Set the automation log entry id.
     *
     * @param  string  $logEntryId  The log entry id.
     * @return AutomatedFightResult The result instance.
     */
    public function setLogEntryId(string $logEntryId): AutomatedFightResult
    {
        $this->logEntryId = $logEntryId;

        return $this;
    }

    /**
     * Get the result type.
     *
     * @return AutomatedFightResultType The fight result type.
     */
    public function getResultType(): AutomatedFightResultType
    {
        return $this->resultType;
    }

    /**
     * Get the monster id.
     *
     * @return int|null The monster id.
     */
    public function getMonsterId(): ?int
    {
        return $this->monsterId;
    }

    /**
     * Get the monster name.
     *
     * @return string|null The monster name.
     */
    public function getMonsterName(): ?string
    {
        return $this->monsterName;
    }

    /**
     * Is this the bounty target?
     *
     * @return bool True when this is the bounty target.
     */
    public function isBountyTarget(): bool
    {
        return $this->bountyTarget;
    }

    /**
     * Is this training?
     *
     * @return bool True when this is recovery training.
     */
    public function isTraining(): bool
    {
        return $this->training;
    }

    /**
     * Get the failed bounty monster id.
     *
     * @return int|null The failed bounty monster id.
     */
    public function getFailedBountyMonsterId(): ?int
    {
        return $this->failedBountyMonsterId;
    }

    /**
     * Has training completed for the failed bounty?
     *
     * @return bool True when recovery training has completed.
     */
    public function hasTrainedForFailedBounty(): bool
    {
        return $this->trainedForFailedBounty;
    }

    /**
     * Get the total kills.
     *
     * @return int The total kill count.
     */
    public function getKills(): int
    {
        return $this->kills;
    }

    /**
     * Get the training kills.
     *
     * @return int The training kill count.
     */
    public function getTrainingKills(): int
    {
        return $this->trainingKills;
    }

    /**
     * Get the bounty kills.
     *
     * @return int The bounty kill count.
     */
    public function getBountyKills(): int
    {
        return $this->bountyKills;
    }

    /**
     * Get the total creatures.
     *
     * @return int The total creature count.
     */
    public function getTotalCreatures(): int
    {
        return $this->totalCreatures;
    }

    /**
     * Get the total XP.
     *
     * @return int The total xp awarded.
     */
    public function getTotalXp(): int
    {
        return $this->totalXp;
    }

    /**
     * Get the total skill XP.
     *
     * @return int The total skill xp awarded.
     */
    public function getTotalSkillXp(): int
    {
        return $this->totalSkillXp;
    }

    /**
     * Get the total faction points.
     *
     * @return int The total faction points awarded.
     */
    public function getTotalFactionPoints(): int
    {
        return $this->totalFactionPoints;
    }

    /**
     * Did the character die?
     *
     * @return bool True when the character died.
     */
    public function hasCharacterDied(): bool
    {
        return $this->characterDied;
    }

    /**
     * Did automation end?
     *
     * @return bool True when the automation ended.
     */
    public function hasEndedAutomation(): bool
    {
        return $this->endedAutomation;
    }

    /**
     * Get the fight data.
     *
     * @return array The fight result payload.
     */
    public function getFightData(): array
    {
        return $this->fightData;
    }

    /**
     * Get the stalled attempt.
     *
     * @return int The stalled attempt count.
     */
    public function getStalledAttempt(): int
    {
        return $this->stalledAttempt;
    }

    /**
     * Get the warning notice.
     *
     * @return array|null The warning notice payload.
     */
    public function getWarningNotice(): ?array
    {
        return $this->warningNotice;
    }

    /**
     * Get the automation log entry id.
     *
     * @return string|null The log entry id.
     */
    public function getLogEntryId(): ?string
    {
        return $this->logEntryId;
    }
}
