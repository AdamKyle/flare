<?php

namespace App\Game\Automation\Values;

use App\Game\Automation\Enums\AutomatedCraftingResultType;

class AutomatedCraftingResult
{
    private AutomatedCraftingResultType $resultType;

    private int $targetItemId = 0;

    private ?int $craftedItemId = null;

    private ?string $craftedItemName = null;

    private string $craftingType = '';

    private int $targetItemLevel = 0;

    private int $currentSkillLevel = 0;

    private bool $startedBelowTargetLevel = false;

    private bool $craftedTargetItem = false;

    private int $attempts = 0;

    private int $failedRolls = 0;

    private int $goldSpent = 0;

    private int $successfulTargetCrafts = 0;

    private int $successfulTrainingCrafts = 0;

    private ?string $logEntryId = null;

    /**
     * Set up the result.
     */
    public function setUp(AutomatedCraftingResultType $resultType, int $targetItemId): AutomatedCraftingResult
    {
        $this->resultType = $resultType;
        $this->targetItemId = $targetItemId;
        $this->craftedItemId = null;
        $this->craftedItemName = null;
        $this->craftingType = '';
        $this->targetItemLevel = 0;
        $this->currentSkillLevel = 0;
        $this->startedBelowTargetLevel = false;
        $this->craftedTargetItem = false;
        $this->attempts = 0;
        $this->failedRolls = 0;
        $this->goldSpent = 0;
        $this->successfulTargetCrafts = 0;
        $this->successfulTrainingCrafts = 0;
        $this->logEntryId = null;

        return $this;
    }

    /**
     * Set the crafted item id.
     */
    public function setCraftedItemId(?int $craftedItemId): AutomatedCraftingResult
    {
        $this->craftedItemId = $craftedItemId;

        return $this;
    }

    /**
     * Set the crafted item name.
     */
    public function setCraftedItemName(?string $craftedItemName): AutomatedCraftingResult
    {
        $this->craftedItemName = $craftedItemName;

        return $this;
    }

    /**
     * Set the crafting type.
     */
    public function setCraftingType(string $craftingType): AutomatedCraftingResult
    {
        $this->craftingType = $craftingType;

        return $this;
    }

    /**
     * Set the target item level.
     */
    public function setTargetItemLevel(int $targetItemLevel): AutomatedCraftingResult
    {
        $this->targetItemLevel = $targetItemLevel;

        return $this;
    }

    /**
     * Set the current skill level.
     */
    public function setCurrentSkillLevel(int $currentSkillLevel): AutomatedCraftingResult
    {
        $this->currentSkillLevel = $currentSkillLevel;

        return $this;
    }

    /**
     * Set whether the character started below the target item level.
     */
    public function setStartedBelowTargetLevel(bool $startedBelowTargetLevel): AutomatedCraftingResult
    {
        $this->startedBelowTargetLevel = $startedBelowTargetLevel;

        return $this;
    }

    /**
     * Set whether the target item was crafted.
     */
    public function setCraftedTargetItem(bool $craftedTargetItem): AutomatedCraftingResult
    {
        $this->craftedTargetItem = $craftedTargetItem;

        return $this;
    }

    /**
     * Set the number of attempts.
     */
    public function setAttempts(int $attempts): AutomatedCraftingResult
    {
        $this->attempts = $attempts;

        return $this;
    }

    /**
     * Set the number of failed rolls.
     */
    public function setFailedRolls(int $failedRolls): AutomatedCraftingResult
    {
        $this->failedRolls = $failedRolls;

        return $this;
    }

    /**
     * Set the amount of gold spent.
     */
    public function setGoldSpent(int $goldSpent): AutomatedCraftingResult
    {
        $this->goldSpent = $goldSpent;

        return $this;
    }

    /**
     * Set successful target crafts.
     */
    public function setSuccessfulTargetCrafts(int $successfulTargetCrafts): AutomatedCraftingResult
    {
        $this->successfulTargetCrafts = $successfulTargetCrafts;

        return $this;
    }

    /**
     * Set successful training crafts.
     */
    public function setSuccessfulTrainingCrafts(int $successfulTrainingCrafts): AutomatedCraftingResult
    {
        $this->successfulTrainingCrafts = $successfulTrainingCrafts;

        return $this;
    }

    /**
     * Set the automation log entry id.
     */
    public function setLogEntryId(string $logEntryId): AutomatedCraftingResult
    {
        $this->logEntryId = $logEntryId;

        return $this;
    }

    /**
     * Get the result type.
     */
    public function getResultType(): AutomatedCraftingResultType
    {
        return $this->resultType;
    }

    /**
     * Get the target item id.
     */
    public function getTargetItemId(): int
    {
        return $this->targetItemId;
    }

    /**
     * Get the crafted item id.
     */
    public function getCraftedItemId(): ?int
    {
        return $this->craftedItemId;
    }

    /**
     * Get the crafted item name.
     */
    public function getCraftedItemName(): ?string
    {
        return $this->craftedItemName;
    }

    /**
     * Get the crafting type.
     */
    public function getCraftingType(): string
    {
        return $this->craftingType;
    }

    /**
     * Get the target item level.
     */
    public function getTargetItemLevel(): int
    {
        return $this->targetItemLevel;
    }

    /**
     * Get the current skill level.
     */
    public function getCurrentSkillLevel(): int
    {
        return $this->currentSkillLevel;
    }

    /**
     * Has the character started below the target item level?
     */
    public function hasStartedBelowTargetLevel(): bool
    {
        return $this->startedBelowTargetLevel;
    }

    /**
     * Has the target item been crafted?
     */
    public function hasCraftedTargetItem(): bool
    {
        return $this->craftedTargetItem;
    }

    /**
     * Get the number of attempts.
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * Get the number of failed rolls.
     */
    public function getFailedRolls(): int
    {
        return $this->failedRolls;
    }

    /**
     * Get the amount of gold spent.
     */
    public function getGoldSpent(): int
    {
        return $this->goldSpent;
    }

    /**
     * Get successful target crafts.
     */
    public function getSuccessfulTargetCrafts(): int
    {
        return $this->successfulTargetCrafts;
    }

    /**
     * Get successful training crafts.
     */
    public function getSuccessfulTrainingCrafts(): int
    {
        return $this->successfulTrainingCrafts;
    }

    /**
     * Get the automation log entry id.
     */
    public function getLogEntryId(): ?string
    {
        return $this->logEntryId;
    }
}
