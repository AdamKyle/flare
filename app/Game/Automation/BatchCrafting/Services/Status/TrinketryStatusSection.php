<?php

namespace App\Game\Automation\BatchCrafting\Services\Status;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Skill;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\TrinketryBatchMode;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\TrinketCraftingService;

class TrinketryStatusSection implements BatchCraftingStatusSection
{
    public function __construct(
        private readonly TrinketCraftingService $trinketCraftingService,
        private readonly CraftingService $craftingService,
        private readonly BatchCraftingSetService $batchCraftingSetService,
    ) {}

    /**
     * Determine whether this section builds the mode-specific status facts for the given type and mode.
     *
     * @param BatchCraftingType $type The batch's Batch Crafting type.
     * @param string $mode The batch's persisted mode value.
     * @return bool True when this section owns the given type and mode.
     */
    public function supports(BatchCraftingType $type, string $mode): bool
    {
        return $type === BatchCraftingType::TRINKETRY && $mode === TrinketryBatchMode::EXPERIENCE->value;
    }

    /**
     * Build the Trinketry mode-specific status facts for the batch.
     *
     * @param Character $character The character the batch belongs to.
     * @param BatchCrafting $batchCrafting The visible Batch Crafting record.
     * @param array $progress The persisted Batch Crafting progress data.
     * @return array The Trinketry mode-specific status facts.
     */
    public function build(Character $character, BatchCrafting $batchCrafting, array $progress): array
    {
        $skill = $this->trinketCraftingService->findTrinketrySkill($character);
        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);
        $destination = $this->resolveRetainedDestination($character, $disposition, $progress);

        return [
            'current_item_id' => $progress['current_item_id'],
            'current_item_name' => $progress['current_item_name'],
            'current_crafting_type' => null,
            'destination_set_id' => $destination['id'],
            'destination_set_name' => $destination['name'],
            'destination_capacity' => null,
            'requested_amount' => null,
            'completed_amount' => null,
            'remaining_amount' => null,
            'set_progress' => null,
            'experience_progress' => null,
            'event_progress' => null,
            'trinketry_progress' => [
                'current_item_id' => $progress['current_item_id'],
                'current_item_name' => $progress['current_item_name'],
                'actions_per_minute' => TrinketryBatchMode::EXPERIENCE->executionWindowSize(),
                'trinketry_xp_gained' => $progress['trinketry_xp_gained'],
                'trinketry_skill' => $this->trinketrySkillFacts($skill),
                'destination_set_id' => $destination['id'],
                'destination_set_name' => $destination['name'],
            ],
        ];
    }

    /**
     * Resolve the Crafted Items Set destination facts, only when the currently displayed item is factually retained there.
     *
     * A direct Keep disposition always places its current item in the Crafted Items Set. A Keep
     * Best disposition only factually retains the current item when it matches the recorded
     * kept-best entry; otherwise the displayed item was already destroyed as not-the-best and its
     * location must not be claimed.
     *
     * @param Character $character The character the batch belongs to.
     * @param BatchCraftingDisposition $disposition The batch's configured disposition.
     * @param array $progress The persisted Batch Crafting progress data.
     * @return array{id: int|null, name: string|null} The resolved Crafted Items Set destination facts.
     */
    private function resolveRetainedDestination(Character $character, BatchCraftingDisposition $disposition, array $progress): array
    {
        $empty = ['id' => null, 'name' => null];

        if ($disposition === BatchCraftingDisposition::DESTROY || is_null($progress['current_item_id'])) {
            return $empty;
        }

        if ($disposition->keepsBest()) {
            $keptBest = $progress['trinketry_kept_best'] ?? null;

            if (is_null($keptBest) || $keptBest['item_id'] !== $progress['current_item_id']) {
                return $empty;
            }
        }

        $set = $this->batchCraftingSetService->findBatchCraftingSet($character);

        return is_null($set) ? $empty : ['id' => $set->id, 'name' => $set->name];
    }

    /**
     * Build the factual current Trinketry skill progress for the runtime UI.
     *
     * @param Skill|null $skill The character's already-resolved Trinketry skill, when present.
     * @return array|null The Trinketry skill progress facts, or null when the skill does not exist.
     */
    private function trinketrySkillFacts(?Skill $skill): ?array
    {
        if (is_null($skill)) {
            return null;
        }

        return [
            'skill_name' => $skill->name,
            'level' => $skill->level,
            'max_level' => $skill->max_level,
            'current_xp' => $skill->xp,
            'next_level_xp' => $skill->xp_max,
            'is_maxed' => $this->craftingService->isSkillMaxed($skill),
        ];
    }
}
