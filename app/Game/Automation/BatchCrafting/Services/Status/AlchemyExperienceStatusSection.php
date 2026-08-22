<?php

namespace App\Game\Automation\BatchCrafting\Services\Status;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Skill;
use App\Game\Automation\BatchCrafting\Enums\AlchemyBatchMode;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;

class AlchemyExperienceStatusSection implements BatchCraftingStatusSection
{
    public function __construct(
        private readonly AlchemyService $alchemyService,
        private readonly CraftingService $craftingService,
    ) {}

    /**
     * Determine whether this section builds the mode-specific status facts for the given type and mode.
     *
     * @param  BatchCraftingType  $type  The batch's Batch Crafting type.
     * @param  string  $mode  The batch's persisted mode value.
     * @return bool True when this section owns the given type and mode.
     */
    public function supports(BatchCraftingType $type, string $mode): bool
    {
        return $type === BatchCraftingType::ALCHEMY && $mode === AlchemyBatchMode::EXPERIENCE->value;
    }

    /**
     * Build the Alchemy For Experience mode-specific status facts for the batch.
     *
     * @param  Character  $character  The character the batch belongs to.
     * @param  BatchCrafting  $batchCrafting  The visible Batch Crafting record.
     * @param  array  $progress  The persisted Batch Crafting progress data.
     * @return array The Alchemy For Experience mode-specific status facts.
     */
    public function build(Character $character, BatchCrafting $batchCrafting, array $progress): array
    {
        $skill = $this->alchemyService->findAlchemySkill($character);

        return [
            'current_item_id' => $progress['current_item_id'],
            'current_item_name' => $progress['current_item_name'],
            'current_crafting_type' => null,
            'destination_set_id' => null,
            'destination_set_name' => null,
            'destination_capacity' => null,
            'requested_amount' => null,
            'completed_amount' => null,
            'remaining_amount' => null,
            'set_progress' => null,
            'experience_progress' => null,
            'event_progress' => null,
            'alchemy_experience_progress' => [
                'current_item_id' => $progress['current_item_id'],
                'current_item_name' => $progress['current_item_name'],
                'actions_per_minute' => AlchemyBatchMode::EXPERIENCE->executionWindowSize(),
                'alchemy_xp_gained' => $progress['alchemy_xp_gained'],
                'alchemy_skill' => $this->alchemySkillFacts($skill),
            ],
        ];
    }

    /**
     * Build the factual current Alchemy skill progress for the runtime UI.
     *
     * @param  Skill|null  $skill  The character's already-resolved Alchemy skill, when present.
     * @return array|null The Alchemy skill progress facts, or null when the skill does not exist.
     */
    private function alchemySkillFacts(?Skill $skill): ?array
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
