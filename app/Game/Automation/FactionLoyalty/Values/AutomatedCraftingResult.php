<?php

namespace App\Game\Automation\FactionLoyalty\Values;

use App\Game\Automation\FactionLoyalty\Enums\AutomatedCraftingResultType;

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
     *
     * @param AutomatedCraftingResultType $resultType The crafting result type.
     * @param int $targetItemId The target item id.
     * @return AutomatedCraftingResult The reset result instance.
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
     *
     * @param int|null $craftedItemId The crafted item id.
     * @return AutomatedCraftingResult The result instance.
     */
    public function setCraftedItemId(?int $craftedItemId): AutomatedCraftingResult
    {
        $this->craftedItemId = $craftedItemId;

        return $this;
    }

    /**
     * Set the crafted item name.
     *
     * @param string|null $craftedItemName The crafted item name.
     * @return AutomatedCraftingResult The result instance.
     */
    public function setCraftedItemName(?string $craftedItemName): AutomatedCraftingResult
    {
        $this->craftedItemName = $craftedItemName;

        return $this;
    }

    /**
     * Set the crafting type.
     *
     * @param string $craftingType The crafting type.
     * @return AutomatedCraftingResult The result instance.
     */
    public function setCraftingType(string $craftingType): AutomatedCraftingResult
    {
        $this->craftingType = $craftingType;

        return $this;
    }

    /**
     * Set the target item level.
     *
     * @param int $targetItemLevel The target item level.
     * @return AutomatedCraftingResult The result instance.
     */
    public function setTargetItemLevel(int $targetItemLevel): AutomatedCraftingResult
    {
        $this->targetItemLevel = $targetItemLevel;

        return $this;
    }

    /**
     * Set the current skill level.
     *
     * @param int $currentSkillLevel The character's current skill level.
     * @return AutomatedCraftingResult The result instance.
     */
    public function setCurrentSkillLevel(int $currentSkillLevel): AutomatedCraftingResult
    {
        $this->currentSkillLevel = $currentSkillLevel;

        return $this;
    }

    /**
     * Set whether the character started below the target item level.
     *
     * @param bool $startedBelowTargetLevel Whether the character started below the target level.
     * @return AutomatedCraftingResult The result instance.
     */
    public function setStartedBelowTargetLevel(bool $startedBelowTargetLevel): AutomatedCraftingResult
    {
        $this->startedBelowTargetLevel = $startedBelowTargetLevel;

        return $this;
    }

    /**
     * Set whether the target item was crafted.
     *
     * @param bool $craftedTargetItem Whether the target item was crafted.
     * @return AutomatedCraftingResult The result instance.
     */
    public function setCraftedTargetItem(bool $craftedTargetItem): AutomatedCraftingResult
    {
        $this->craftedTargetItem = $craftedTargetItem;

        return $this;
    }

    /**
     * Set the number of attempts.
     *
     * @param int $attempts The number of crafting attempts.
     * @return AutomatedCraftingResult The result instance.
     */
    public function setAttempts(int $attempts): AutomatedCraftingResult
    {
        $this->attempts = $attempts;

        return $this;
    }

    /**
     * Set the number of failed rolls.
     *
     * @param int $failedRolls The number of failed crafting rolls.
     * @return AutomatedCraftingResult The result instance.
     */
    public function setFailedRolls(int $failedRolls): AutomatedCraftingResult
    {
        $this->failedRolls = $failedRolls;

        return $this;
    }

    /**
     * Set the amount of gold spent.
     *
     * @param int $goldSpent The amount of gold spent.
     * @return AutomatedCraftingResult The result instance.
     */
    public function setGoldSpent(int $goldSpent): AutomatedCraftingResult
    {
        $this->goldSpent = $goldSpent;

        return $this;
    }

    /**
     * Set successful target crafts.
     *
     * @param int $successfulTargetCrafts The successful target craft count.
     * @return AutomatedCraftingResult The result instance.
     */
    public function setSuccessfulTargetCrafts(int $successfulTargetCrafts): AutomatedCraftingResult
    {
        $this->successfulTargetCrafts = $successfulTargetCrafts;

        return $this;
    }

    /**
     * Set successful training crafts.
     *
     * @param int $successfulTrainingCrafts The successful training craft count.
     * @return AutomatedCraftingResult The result instance.
     */
    public function setSuccessfulTrainingCrafts(int $successfulTrainingCrafts): AutomatedCraftingResult
    {
        $this->successfulTrainingCrafts = $successfulTrainingCrafts;

        return $this;
    }

    /**
     * Set the automation log entry id.
     *
     * @param string $logEntryId The log entry id.
     * @return AutomatedCraftingResult The result instance.
     */
    public function setLogEntryId(string $logEntryId): AutomatedCraftingResult
    {
        $this->logEntryId = $logEntryId;

        return $this;
    }

    /**
     * Get the result type.
     *
     * @return AutomatedCraftingResultType The crafting result type.
     */
    public function getResultType(): AutomatedCraftingResultType
    {
        return $this->resultType;
    }

    /**
     * Get the target item id.
     *
     * @return int The target item id.
     */
    public function getTargetItemId(): int
    {
        return $this->targetItemId;
    }

    /**
     * Get the crafted item id.
     *
     * @return int|null The crafted item id.
     */
    public function getCraftedItemId(): ?int
    {
        return $this->craftedItemId;
    }

    /**
     * Get the crafted item name.
     *
     * @return string|null The crafted item name.
     */
    public function getCraftedItemName(): ?string
    {
        return $this->craftedItemName;
    }

    /**
     * Get the crafting type.
     *
     * @return string The crafting type.
     */
    public function getCraftingType(): string
    {
        return $this->craftingType;
    }

    /**
     * Get the target item level.
     *
     * @return int The target item level.
     */
    public function getTargetItemLevel(): int
    {
        return $this->targetItemLevel;
    }

    /**
     * Get the current skill level.
     *
     * @return int The character's current skill level.
     */
    public function getCurrentSkillLevel(): int
    {
        return $this->currentSkillLevel;
    }

    /**
     * Has the character started below the target item level?
     *
     * @return bool True when the character started below the target level.
     */
    public function hasStartedBelowTargetLevel(): bool
    {
        return $this->startedBelowTargetLevel;
    }

    /**
     * Has the target item been crafted?
     *
     * @return bool True when the target item was crafted.
     */
    public function hasCraftedTargetItem(): bool
    {
        return $this->craftedTargetItem;
    }

    /**
     * Get the number of attempts.
     *
     * @return int The number of crafting attempts.
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * Get the number of failed rolls.
     *
     * @return int The number of failed crafting rolls.
     */
    public function getFailedRolls(): int
    {
        return $this->failedRolls;
    }

    /**
     * Get the amount of gold spent.
     *
     * @return int The amount of gold spent.
     */
    public function getGoldSpent(): int
    {
        return $this->goldSpent;
    }

    /**
     * Get successful target crafts.
     *
     * @return int The successful target craft count.
     */
    public function getSuccessfulTargetCrafts(): int
    {
        return $this->successfulTargetCrafts;
    }

    /**
     * Get successful training crafts.
     *
     * @return int The successful training craft count.
     */
    public function getSuccessfulTrainingCrafts(): int
    {
        return $this->successfulTrainingCrafts;
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
