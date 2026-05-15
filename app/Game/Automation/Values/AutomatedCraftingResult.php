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

    /**
     * Set up the result.
     *
     * @param AutomatedCraftingResultType $resultType
     * @param int $targetItemId
     * @return AutomatedCraftingResult
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

        return $this;
    }

    /**
     * Set the crafted item id.
     *
     * @param int|null $craftedItemId
     * @return AutomatedCraftingResult
     */
    public function setCraftedItemId(?int $craftedItemId): AutomatedCraftingResult
    {
        $this->craftedItemId = $craftedItemId;

        return $this;
    }

    /**
     * Set the crafted item name.
     *
     * @param string|null $craftedItemName
     * @return AutomatedCraftingResult
     */
    public function setCraftedItemName(?string $craftedItemName): AutomatedCraftingResult
    {
        $this->craftedItemName = $craftedItemName;

        return $this;
    }

    /**
     * Set the crafting type.
     *
     * @param string $craftingType
     * @return AutomatedCraftingResult
     */
    public function setCraftingType(string $craftingType): AutomatedCraftingResult
    {
        $this->craftingType = $craftingType;

        return $this;
    }

    /**
     * Set the target item level.
     *
     * @param int $targetItemLevel
     * @return AutomatedCraftingResult
     */
    public function setTargetItemLevel(int $targetItemLevel): AutomatedCraftingResult
    {
        $this->targetItemLevel = $targetItemLevel;

        return $this;
    }

    /**
     * Set the current skill level.
     *
     * @param int $currentSkillLevel
     * @return AutomatedCraftingResult
     */
    public function setCurrentSkillLevel(int $currentSkillLevel): AutomatedCraftingResult
    {
        $this->currentSkillLevel = $currentSkillLevel;

        return $this;
    }

    /**
     * Set whether the character started below the target item level.
     *
     * @param bool $startedBelowTargetLevel
     * @return AutomatedCraftingResult
     */
    public function setStartedBelowTargetLevel(bool $startedBelowTargetLevel): AutomatedCraftingResult
    {
        $this->startedBelowTargetLevel = $startedBelowTargetLevel;

        return $this;
    }

    /**
     * Set whether the target item was crafted.
     *
     * @param bool $craftedTargetItem
     * @return AutomatedCraftingResult
     */
    public function setCraftedTargetItem(bool $craftedTargetItem): AutomatedCraftingResult
    {
        $this->craftedTargetItem = $craftedTargetItem;

        return $this;
    }

    /**
     * Set the number of attempts.
     *
     * @param int $attempts
     * @return AutomatedCraftingResult
     */
    public function setAttempts(int $attempts): AutomatedCraftingResult
    {
        $this->attempts = $attempts;

        return $this;
    }

    /**
     * Set the number of failed rolls.
     *
     * @param int $failedRolls
     * @return AutomatedCraftingResult
     */
    public function setFailedRolls(int $failedRolls): AutomatedCraftingResult
    {
        $this->failedRolls = $failedRolls;

        return $this;
    }

    /**
     * Set the amount of gold spent.
     *
     * @param int $goldSpent
     * @return AutomatedCraftingResult
     */
    public function setGoldSpent(int $goldSpent): AutomatedCraftingResult
    {
        $this->goldSpent = $goldSpent;

        return $this;
    }

    /**
     * Set successful target crafts.
     *
     * @param int $successfulTargetCrafts
     * @return AutomatedCraftingResult
     */
    public function setSuccessfulTargetCrafts(int $successfulTargetCrafts): AutomatedCraftingResult
    {
        $this->successfulTargetCrafts = $successfulTargetCrafts;

        return $this;
    }

    /**
     * Set successful training crafts.
     *
     * @param int $successfulTrainingCrafts
     * @return AutomatedCraftingResult
     */
    public function setSuccessfulTrainingCrafts(int $successfulTrainingCrafts): AutomatedCraftingResult
    {
        $this->successfulTrainingCrafts = $successfulTrainingCrafts;

        return $this;
    }

    /**
     * Get the result type.
     *
     * @return AutomatedCraftingResultType
     */
    public function getResultType(): AutomatedCraftingResultType
    {
        return $this->resultType;
    }

    /**
     * Get the target item id.
     *
     * @return int
     */
    public function getTargetItemId(): int
    {
        return $this->targetItemId;
    }

    /**
     * Get the crafted item id.
     *
     * @return int|null
     */
    public function getCraftedItemId(): ?int
    {
        return $this->craftedItemId;
    }

    /**
     * Get the crafted item name.
     *
     * @return string|null
     */
    public function getCraftedItemName(): ?string
    {
        return $this->craftedItemName;
    }

    /**
     * Get the crafting type.
     *
     * @return string
     */
    public function getCraftingType(): string
    {
        return $this->craftingType;
    }

    /**
     * Get the target item level.
     *
     * @return int
     */
    public function getTargetItemLevel(): int
    {
        return $this->targetItemLevel;
    }

    /**
     * Get the current skill level.
     *
     * @return int
     */
    public function getCurrentSkillLevel(): int
    {
        return $this->currentSkillLevel;
    }

    /**
     * Has the character started below the target item level?
     *
     * @return bool
     */
    public function hasStartedBelowTargetLevel(): bool
    {
        return $this->startedBelowTargetLevel;
    }

    /**
     * Has the target item been crafted?
     *
     * @return bool
     */
    public function hasCraftedTargetItem(): bool
    {
        return $this->craftedTargetItem;
    }

    /**
     * Get the number of attempts.
     *
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * Get the number of failed rolls.
     *
     * @return int
     */
    public function getFailedRolls(): int
    {
        return $this->failedRolls;
    }

    /**
     * Get the amount of gold spent.
     *
     * @return int
     */
    public function getGoldSpent(): int
    {
        return $this->goldSpent;
    }

    /**
     * Get successful target crafts.
     *
     * @return int
     */
    public function getSuccessfulTargetCrafts(): int
    {
        return $this->successfulTargetCrafts;
    }

    /**
     * Get successful training crafts.
     *
     * @return int
     */
    public function getSuccessfulTrainingCrafts(): int
    {
        return $this->successfulTrainingCrafts;
    }
}