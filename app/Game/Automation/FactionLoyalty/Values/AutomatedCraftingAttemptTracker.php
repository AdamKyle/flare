<?php

namespace App\Game\Automation\FactionLoyalty\Values;

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
     * @param  bool  $startedBelowTargetLevel  Whether the character started below the target item level.
     * @return AutomatedCraftingAttemptTracker The reset tracker instance.
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
     * @param  Item  $item  The item attempted.
     * @param  int  $goldSpent  The gold spent on the attempt.
     * @param  bool  $crafted  Whether the attempt produced an item.
     * @param  bool  $craftedTargetItem  Whether the attempt produced the target item.
     * @return void This method does not return a value.
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
     * Has the character started below the target item level?
     *
     * @return bool True when the character started below the target level.
     */
    public function hasStartedBelowTargetLevel(): bool
    {
        return $this->startedBelowTargetLevel;
    }

    /**
     * Has a target item been crafted?
     *
     * @return bool True when a target item has been crafted.
     */
    public function hasCraftedTargetItem(): bool
    {
        return $this->successfulTargetCrafts > 0;
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
     * Get the last attempted item.
     *
     * @return Item|null The last attempted item.
     */
    public function getLastAttemptedItem(): ?Item
    {
        return $this->lastAttemptedItem;
    }
}
