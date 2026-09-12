<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Game\Automation\BatchCrafting\Enums\CraftSetPosition;
use App\Game\Automation\BatchCrafting\Values\CraftSetPlanEntry;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Values\CraftingSkillGroup;
use Illuminate\Support\Collection;

class CraftSetRecommendationService
{
    public function __construct(
        private readonly CraftingService $craftingService,
    ) {}

    /**
     * Build the authoritative Craft Set recommendation for every required position.
     *
     * Recommends the highest currently craftable item for each required position and
     * reports any required position with no currently craftable candidate. Hand
     * positions are never recommended; the player always chooses them.
     *
     * @param Character $character The character requesting the recommendation.
     * @return array{positions: array<int, array{position: string, item_id: int, crafting_type: string, item_name: string}>, missing_positions: array<int, string>} The recommended positions and any required positions with no craftable candidate.
     */
    public function build(Character $character): array
    {
        $skillsByGroup = $this->craftingService->resolveCraftingSkillsForGroups($character, [
            CraftingSkillGroup::ARMOUR,
            CraftingSkillGroup::RING,
            CraftingSkillGroup::SPELL,
        ]);

        $candidatesByGroup = $this->fetchCandidatesByGroup($skillsByGroup);

        $positions = [];
        $missingPositions = [];

        foreach (CraftSetPosition::orderedCases() as $position) {
            if (! $position->isRequired()) {
                continue;
            }

            $item = $this->resolveCandidate($candidatesByGroup, $position);

            if (is_null($item)) {
                $missingPositions[] = $position->value;

                continue;
            }

            $entry = new CraftSetPlanEntry(
                $position,
                $item->id,
                $position->craftingGroup()->value,
                $item->affix_name ?? $item->name,
            );

            $positions[] = $entry->toArray();
        }

        return [
            'positions' => $positions,
            'missing_positions' => $missingPositions,
        ];
    }

    /**
     * Fetch the required-position candidate items for every Crafting group, in at most three queries.
     *
     * @param Collection<string, Skill|null> $skillsByGroup The resolved Crafting skills keyed by Crafting skill group value.
     * @return array<string, Collection<string, Item>> The candidate items keyed by Crafting group value, then item type.
     */
    private function fetchCandidatesByGroup(Collection $skillsByGroup): array
    {
        return [
            CraftingSkillGroup::ARMOUR->value => $this->fetchCandidates(
                $skillsByGroup->get(CraftingSkillGroup::ARMOUR->value),
                CraftingSkillGroup::ARMOUR->value,
                ['body', 'leggings', 'sleeves', 'gloves', 'feet', 'helmet'],
            ),
            CraftingSkillGroup::RING->value => $this->fetchCandidates(
                $skillsByGroup->get(CraftingSkillGroup::RING->value),
                CraftingSkillGroup::RING->value,
                ['ring'],
            ),
            CraftingSkillGroup::SPELL->value => $this->fetchCandidates(
                $skillsByGroup->get(CraftingSkillGroup::SPELL->value),
                CraftingSkillGroup::SPELL->value,
                ['spell-damage', 'spell-healing'],
            ),
        ];
    }

    /**
     * Fetch the highest craftable candidate items for one Crafting group, when the skill exists.
     *
     * @param Skill|null $skill The character's resolved Crafting skill for the group, when it exists.
     * @param string $craftingType The Crafting group used to query craftable items.
     * @param array<int, string> $itemTypes The required item types for the group.
     * @return Collection<string, Item> The highest craftable item for each item type, keyed by item type.
     */
    private function fetchCandidates(?Skill $skill, string $craftingType, array $itemTypes): Collection
    {
        if (is_null($skill)) {
            return new Collection;
        }

        return $this->craftingService->fetchBestCraftableItemsByTypeForAutomation($skill, $craftingType, $itemTypes);
    }

    /**
     * Resolve the recommended candidate item for a required position from the pre-fetched candidates.
     *
     * Every caller has already filtered to required positions, which always resolve a
     * Crafting group, so the group here is never null.
     *
     * @param array<string, Collection<string, Item>> $candidatesByGroup The candidate items keyed by Crafting group value, then item type.
     * @param CraftSetPosition $position The required position being resolved.
     * @return Item|null The recommended item, or null when no candidate exists for the position.
     */
    private function resolveCandidate(array $candidatesByGroup, CraftSetPosition $position): ?Item
    {
        $group = $position->craftingGroup();

        return $candidatesByGroup[$group->value]->get($position->requiredItemType());
    }
}
