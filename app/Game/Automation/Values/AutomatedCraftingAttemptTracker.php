<?php

namespace App\Game\Automation\Values;

use App\Flare\Models\Item;

class AutomatedCraftingAttemptTracker
{
    private int $attempts = 0;

    private int $failedRolls = 0;

    private int $goldSpent = 0;

    private int $successfulTargetCrafts = 0;

    private int $successfulTrainingCrafts = 0;

    private bool $startedBelowTargetLevel = false;

    private ?Item $lastAttemptedItem = null;

    /**
     * Set up the tracker.
     *
     * @param bool $startedBelowTargetLevel
     * @return AutomatedCraftingAttemptTracker
     */
    public function setUp(bool $startedBelowTargetLevel): AutomatedCraftingAttemptTracker
    {
        $this->attempts = 0;
        $this->failedRolls = 0;
        $this->goldSpent = 0;
        $this->successfulTargetCrafts = 0;
        $this->successfulTrainingCrafts = 0;
        $this->startedBelowTargetLevel = $startedBelowTargetLevel;
        $this->lastAttemptedItem = null;

        return $this;
    }

    /**
     * Track a crafting attempt.
     *
     * @param Item $item
     * @param int $goldSpent
     * @param bool $crafted
     * @param bool $craftedTargetItem
     * @return void
     */
    public function trackAttempt(Item $item, int $goldSpent, bool $crafted, bool $craftedTargetItem): void
    {
        $this->attempts++;
        $this->goldSpent += $goldSpent;
        $this->lastAttemptedItem = $item;

        if (! $crafted) {
            $this->failedRolls++;

            return;
        }

        if ($craftedTargetItem) {
            $this->successfulTargetCrafts++;

            return;
        }

        $this->successfulTrainingCrafts++;
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
     * Has the character started below the target item level?
     *
     * @return bool
     */
    public function hasStartedBelowTargetLevel(): bool
    {
        return $this->startedBelowTargetLevel;
    }

    /**
     * Has a target item been crafted?
     *
     * @return bool
     */
    public function hasCraftedTargetItem(): bool
    {
        return $this->successfulTargetCrafts > 0;
    }

    /**
     * Has a training item been crafted?
     *
     * @return bool
     */
    public function hasCraftedTrainingItem(): bool
    {
        return $this->successfulTrainingCrafts > 0;
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

    /**
     * Get the last attempted item.
     *
     * @return Item|null
     */
    public function getLastAttemptedItem(): ?Item
    {
        return $this->lastAttemptedItem;
    }
}