<?php

namespace App\Game\Core\Items\Services;

use App\Flare\Models\Item;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Items\Values\ItemSocketEligibility;

class ItemSocketRollService
{
    /**
     * @param RandomNumberGenerator $randomNumberGenerator
     * @param ChanceCalculator $chanceCalculator
     * @param ItemSocketEligibility $itemSocketEligibility
     */
    public function __construct(
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly ChanceCalculator $chanceCalculator,
        private readonly ItemSocketEligibility $itemSocketEligibility,
    ) {}

    /**
     * Roll a Seer socket result without ever reducing the Item's current socket count.
     *
     * @param Item $item
     * @return int
     */
    public function rollForSeer(Item $item): int
    {
        if (! $this->itemSocketEligibility->isEligible($item->type)) {
            return 0;
        }

        $currentSocketCount = $item->socket_count ?? 0;

        if ($this->itemSocketEligibility->isTwoHanded($item->type) && $currentSocketCount <= 0) {
            return 3;
        }

        if ($this->itemSocketEligibility->isTwoHanded($item->type)) {
            return max($currentSocketCount, $this->weightedTarget());
        }

        return max($currentSocketCount, $this->initialSocketCount($item));
    }

    /**
     * Roll sockets for an ordinary equipment drop using the twenty-percent eligibility gate.
     *
     * @param Item $item
     * @return int
     */
    public function rollForOrdinaryDrop(Item $item): int
    {
        if (! $this->itemSocketEligibility->isEligible($item->type)) {
            return 0;
        }

        if (! $this->chanceCalculator->passesPercentage(20)) {
            return 0;
        }

        return $this->initialSocketCount($item);
    }

    /**
     * Roll sockets for a reward that explicitly guarantees a socketed Item.
     *
     * @param Item $item
     * @return int
     */
    public function rollForGuaranteedSocketReward(Item $item): int
    {
        if (! $this->itemSocketEligibility->isEligible($item->type)) {
            return 0;
        }

        return $this->initialSocketCount($item);
    }

    /**
     * Return the category-specific initial socket count for an eligible Item.
     *
     * @param Item $item
     * @return int
     */
    private function initialSocketCount(Item $item): int
    {
        if ($this->itemSocketEligibility->isOneSocketArmour($item->type)) {
            return 1;
        }

        if ($this->itemSocketEligibility->isTwoHanded($item->type)) {
            return 3;
        }

        return min($this->weightedTarget(), $this->itemSocketEligibility->maxSocketCount($item->type));
    }

    /**
     * Roll the established weighted socket target from one through six.
     *
     * @return int
     */
    private function weightedTarget(): int
    {
        $roll = $this->randomNumberGenerator->numberBetween(1, 100);

        return match (true) {
            $roll <= 49 => 1,
            $roll <= 59 => 2,
            $roll <= 79 => 3,
            $roll <= 94 => 4,
            $roll <= 99 => 5,
            default => 6,
        };
    }
}
