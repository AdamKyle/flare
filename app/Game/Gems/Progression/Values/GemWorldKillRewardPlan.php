<?php

namespace App\Game\Gems\Progression\Values;

class GemWorldKillRewardPlan
{
    /**
     * @param GemScrollRollPlan $scrollRoll
     * @param ?GemSpecialItemRollPlan $enhancedItemRoll
     * @param array $itemOpportunityRolls
     */
    public function __construct(
        private readonly GemScrollRollPlan $scrollRoll,
        private readonly ?GemSpecialItemRollPlan $enhancedItemRoll,
        private readonly array $itemOpportunityRolls,
    ) {}

    /**
     * The rolled Gem Scroll drop outcome for this kill.
     *
     * @return GemScrollRollPlan
     */
    public function scrollRoll(): GemScrollRollPlan
    {
        return $this->scrollRoll;
    }

    /**
     * The rolled level-700+ enhanced-equipment outcome for this kill, or null when not eligible.
     *
     * @return ?GemSpecialItemRollPlan
     */
    public function enhancedItemRoll(): ?GemSpecialItemRollPlan
    {
        return $this->enhancedItemRoll;
    }

    /**
     * Every additional Item Scroll rarity opportunity rolled for this kill.
     *
     * @return array
     */
    public function itemOpportunityRolls(): array
    {
        return $this->itemOpportunityRolls;
    }

    /**
     * Serialize this plan into a compact primitive array for checkpoint storage.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'scroll_roll' => $this->scrollRoll->toArray(),
            'enhanced_item_roll' => $this->enhancedItemRoll?->toArray(),
            'item_opportunity_rolls' => array_map(
                fn (GemSpecialItemRollPlan $roll): array => $roll->toArray(),
                $this->itemOpportunityRolls,
            ),
        ];
    }

    /**
     * Hydrate this plan from its checkpoint-stored primitive array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            GemScrollRollPlan::fromArray($data['scroll_roll'] ?? []),
            is_null($data['enhanced_item_roll'] ?? null) ? null : GemSpecialItemRollPlan::fromArray($data['enhanced_item_roll']),
            array_map(
                fn (array $roll): GemSpecialItemRollPlan => GemSpecialItemRollPlan::fromArray($roll),
                $data['item_opportunity_rolls'] ?? [],
            ),
        );
    }
}
