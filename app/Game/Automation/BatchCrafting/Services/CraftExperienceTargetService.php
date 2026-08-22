<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Skill;
use App\Game\Automation\BatchCrafting\Values\CraftExperienceCycleTarget;
use App\Game\Automation\BatchCrafting\Values\ResolvedCraftExperienceTarget;
use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Values\CraftingSkillGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class CraftExperienceTargetService
{
    public function __construct(
        private readonly CraftingService $craftingService,
    ) {}

    /**
     * Return the real Crafting skill groups that make up the Experience cycle, derived from the
     * authoritative target sequence itself so the group list can never drift from it.
     *
     * @return array<int, CraftingSkillGroup> The Crafting skill groups, in first-appearance order.
     */
    public function skillGroups(): array
    {
        $groups = [];

        foreach ($this->buildCycleTargets() as $target) {
            if (! in_array($target->skillGroup, $groups, true)) {
                $groups[] = $target->skillGroup;
            }
        }

        return $groups;
    }

    /**
     * Return the authoritative Experience cycle size, derived from the real target sequence.
     *
     * @return int The number of targets in one full Experience cycle.
     */
    public function cycleSize(): int
    {
        return count($this->buildCycleTargets());
    }

    /**
     * Resolve the character's four Crafting skills used by the Experience cycle, in one operation.
     *
     * @param  Character  $character  The character running the batch.
     * @return SupportCollection<string, Skill|null> The resolved skills keyed by Crafting skill group value.
     */
    public function resolveCraftingSkills(Character $character): SupportCollection
    {
        return $this->craftingService->resolveCraftingSkillsForGroups($character, $this->skillGroups());
    }

    /**
     * Determine whether at least one meaningful Experience cycle target currently exists.
     *
     * @param  SupportCollection<string, Skill|null>  $skills  The character's already-resolved Crafting skills.
     * @return bool True when at least one actionable target with a real craftable XP item exists.
     */
    public function hasMeaningfulTarget(SupportCollection $skills): bool
    {
        return ! is_null($this->resolveNextTarget($skills, 0));
    }

    /**
     * Determine whether all four real Crafting skills have reached their maximum level.
     *
     * @param  SupportCollection<string, Skill|null>  $skills  The character's already-resolved Crafting skills.
     * @return bool True when every required Crafting skill group has a maxed skill.
     */
    public function allSkillsMaxed(SupportCollection $skills): bool
    {
        foreach ($this->skillGroups() as $group) {
            $skill = $skills->get($group->value);

            if (is_null($skill) || ! $this->craftingService->isSkillMaxed($skill)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve the next actionable cycle target starting from the persisted cycle position.
     *
     * @param  SupportCollection<string, Skill|null>  $skills  The character's already-resolved Crafting skills.
     * @param  int  $startPosition  The persisted cycle position to search from.
     * @return ResolvedCraftExperienceTarget|null The resolved target, or null when none is actionable.
     */
    public function resolveNextTarget(SupportCollection $skills, int $startPosition): ?ResolvedCraftExperienceTarget
    {
        $targets = $this->buildCycleTargets();
        $cycleSize = count($targets);
        $candidatesByGroup = $this->preloadCandidatesByGroup($skills);

        for ($offset = 0; $offset < $cycleSize; $offset++) {
            $index = ($startPosition + $offset) % $cycleSize;
            $target = $targets[$index];
            $skill = $skills->get($target->skillGroup->value);

            if (is_null($skill) || $this->craftingService->isSkillMaxed($skill)) {
                continue;
            }

            $item = $candidatesByGroup[$target->skillGroup->value]->first(fn ($candidate) => $target->matches($candidate));

            if (! is_null($item)) {
                return new ResolvedCraftExperienceTarget($index, $target, $item);
            }
        }

        return null;
    }

    /**
     * Preload the non-trivial craftable candidate items for each of the four Crafting skill groups.
     *
     * Executes at most four queries total, regardless of how many of the 23 cycle targets are
     * later evaluated against these candidates.
     *
     * @param  SupportCollection<string, Skill|null>  $skills  The character's already-resolved Crafting skills.
     * @return array<string, Collection> The candidate items keyed by Crafting skill group value.
     */
    private function preloadCandidatesByGroup(SupportCollection $skills): array
    {
        $weaponSkill = $skills->get(CraftingSkillGroup::WEAPON->value);
        $armourSkill = $skills->get(CraftingSkillGroup::ARMOUR->value);
        $ringSkill = $skills->get(CraftingSkillGroup::RING->value);
        $spellSkill = $skills->get(CraftingSkillGroup::SPELL->value);

        return [
            CraftingSkillGroup::WEAPON->value => $this->resolveGroupCandidates($weaponSkill, CraftingSkillGroup::WEAPON),
            CraftingSkillGroup::ARMOUR->value => $this->resolveGroupCandidates($armourSkill, CraftingSkillGroup::ARMOUR),
            CraftingSkillGroup::RING->value => $this->resolveGroupCandidates($ringSkill, CraftingSkillGroup::RING),
            CraftingSkillGroup::SPELL->value => $this->resolveGroupCandidates($spellSkill, CraftingSkillGroup::SPELL),
        ];
    }

    /**
     * Resolve the candidate items for one Crafting skill group, when the skill is present and not maxed.
     *
     * @param  Skill|null  $skill  The character's resolved Crafting skill for the group, when present.
     * @param  CraftingSkillGroup  $group  The Crafting skill group being resolved.
     * @return Collection The candidate items for the group, or an empty collection when unavailable.
     */
    private function resolveGroupCandidates(?Skill $skill, CraftingSkillGroup $group): Collection
    {
        if (is_null($skill) || $this->craftingService->isSkillMaxed($skill)) {
            return new Collection;
        }

        return $this->craftingService->fetchMeaningfulExperienceCandidates($skill, $group);
    }

    /**
     * Build the deterministic, ordered Craft For Experience cycle target sequence.
     *
     * @return array<int, CraftExperienceCycleTarget> The ordered cycle targets.
     */
    private function buildCycleTargets(): array
    {
        $targets = [];

        foreach (ItemType::validWeapons() as $weaponType) {
            $targets[] = new CraftExperienceCycleTarget(CraftingSkillGroup::WEAPON, $weaponType, null);
        }

        foreach (ArmourType::cases() as $armourType) {
            $targets[] = new CraftExperienceCycleTarget(CraftingSkillGroup::ARMOUR, CraftingSkillGroup::ARMOUR->value, $armourType->value);
        }

        $targets[] = new CraftExperienceCycleTarget(CraftingSkillGroup::RING, CraftingSkillGroup::RING->value, 'ring');
        $targets[] = new CraftExperienceCycleTarget(CraftingSkillGroup::RING, CraftingSkillGroup::RING->value, 'ring');
        $targets[] = new CraftExperienceCycleTarget(CraftingSkillGroup::SPELL, CraftingSkillGroup::SPELL->value, 'spell-damage');
        $targets[] = new CraftExperienceCycleTarget(CraftingSkillGroup::SPELL, CraftingSkillGroup::SPELL->value, 'spell-healing');

        return $targets;
    }
}
