<?php

namespace App\Game\Skills\Transformers;

use App\Flare\Models\Skill;
use App\Game\Skills\Services\SkillBonusService;
use App\Game\Skills\Values\SkillBonusAttribute;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use League\Fractal\TransformerAbstract;

class SkillsTransformer extends TransformerAbstract
{
    /**
     * @param SkillBonusService $skillBonusService
     */
    public function __construct(
        private readonly SkillBonusService $skillBonusService,
    ) {}

    /**
     * Transform a Character Skill into its detailed response payload.
     *
     * @param Skill $skill
     * @return array
     */
    public function transform(Skill $skill): array
    {
        $skillBonusBreakDown = $this->skillBonusService->itemBonusBreakdown($skill, SkillBonusAttribute::SKILL_BONUS);
        $skillXpBonusBreakDown = $this->skillBonusService->itemBonusBreakdown($skill, SkillBonusAttribute::SKILL_TRAINING_BONUS);

        return [
            'id' => $skill->id,
            'character_id' => $skill->character_id,
            'name' => $skill->name,
            'description' => $skill->description,
            'skill_bonus' => $this->skillBonusService->skillBonus($skill),
            'skill_xp_bonus' => $this->skillBonusService->skillTrainingBonus($skill),
            'skill_type' => $skill->baseSkill->skillType()->getNamedValue(),
            'xp' => $skill->xp,
            'xp_max' => $skill->xp_max,
            'level' => $skill->level,
            'max_level' => $skill->baseSkill->max_level,
            'can_train' => $skill->baseSkill->can_train,
            'is_training' => $skill->currently_training,
            'xp_towards' => $skill->xp_towards,
            'is_locked' => $skill->is_locked,
            'unit_time_reduction' => $skill->unit_time_reduction,
            'building_time_reduction' => $skill->building_time_reduction,
            'unit_movement_time_reduction' => $skill->unit_movement_time_reduction,
            'base_damage_mod' => $this->skillBonusService->baseDamageMod($skill),
            'base_healing_mod' => $this->skillBonusService->baseHealingMod($skill),
            'base_ac_mod' => $this->skillBonusService->baseAcMod($skill),
            'fight_timeout_mod' => $this->skillBonusService->fightTimeOutMod($skill),
            'move_timeout_mod' => $this->skillBonusService->moveTimeOutMod($skill),
            'class_bonus' => $skill->class_bonus,
            'skill_bonus_break_down' => $skillBonusBreakDown,
            'skill_xp_bonus_break_down' => $skillXpBonusBreakDown,
            'items_affecting_skill' => $this->mergeItemsAffectingSkill($skillBonusBreakDown, $skillXpBonusBreakDown),
        ];
    }

    /**
     * Merge both item breakdowns into one entry per contributing slot, keeping each bonus value separate.
     *
     * @param array $skillBonusBreakDown
     * @param array $skillXpBonusBreakDown
     * @return array
     */
    private function mergeItemsAffectingSkill(array $skillBonusBreakDown, array $skillXpBonusBreakDown): array
    {
        return collect($skillBonusBreakDown)
            ->concat($skillXpBonusBreakDown)
            ->groupBy(['source', 'slot_id'])
            ->flatMap(fn (Collection $slotEntries): Collection => $slotEntries->map(
                fn (Collection $breakDownEntries): array => $this->buildItemAffectingSkill($breakDownEntries)
            ))
            ->values()
            ->all();
    }

    /**
     * Build one contributing item entry from the breakdown entries that share the same slot.
     *
     * @param Collection $breakDownEntries
     * @return array
     */
    private function buildItemAffectingSkill(Collection $breakDownEntries): array
    {
        $skillBonusKey = SkillBonusAttribute::SKILL_BONUS->value;
        $skillTrainingBonusKey = SkillBonusAttribute::SKILL_TRAINING_BONUS->value;

        return [
            ...Arr::except($breakDownEntries->first(), [$skillBonusKey, $skillTrainingBonusKey]),
            $skillBonusKey => $breakDownEntries->pluck($skillBonusKey)->filter()->first() ?? 0.0,
            $skillTrainingBonusKey => $breakDownEntries->pluck($skillTrainingBonusKey)->filter()->first() ?? 0.0,
        ];
    }
}
