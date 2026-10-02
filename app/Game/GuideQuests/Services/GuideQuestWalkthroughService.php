<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGuideQuestProgress;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\GuideQuestWalkthroughStep;

class GuideQuestWalkthroughService
{
    /**
     * @param GuideQuestService $guideQuestService
     */
    public function __construct(private readonly GuideQuestService $guideQuestService) {}

    /**
     * Build the applicable walkthrough definition and persisted Character progress.
     *
     * @param Character $character
     * @param GuideQuest $guideQuest
     * @return array|null
     */
    public function fetch(Character $character, GuideQuest $guideQuest): ?array
    {
        if (! $this->isApplicable($character, $guideQuest)) {
            return null;
        }

        $steps = $guideQuest->walkthroughSteps()->get();

        if ($steps->isEmpty()) {
            return null;
        }

        $progress = CharacterGuideQuestProgress::firstOrCreate(
            [
                'character_id' => $character->id,
                'guide_quest_id' => $guideQuest->id,
            ],
            ['started_at' => now()],
        );

        $currentStepId = $this->resolveCurrentStepId($progress, $steps->pluck('id')->all());

        return [
            'guide_quest_id' => $guideQuest->id,
            'current_step_id' => $currentStepId,
            'walkthrough_completed' => ! is_null($progress->walkthrough_completed_at),
            'steps' => $steps,
        ];
    }

    /**
     * Persist the Character's current walkthrough step or completion state.
     *
     * @param Character $character
     * @param GuideQuest $guideQuest
     * @param int|null $stepId
     * @param bool $completed
     * @return bool
     */
    public function updateProgress(
        Character $character,
        GuideQuest $guideQuest,
        ?int $stepId,
        bool $completed,
    ): bool {
        if (! $this->isApplicable($character, $guideQuest)) {
            return false;
        }

        $step = is_null($stepId) ? null : GuideQuestWalkthroughStep::find($stepId);

        if (! is_null($step) && $step->guide_quest_id !== $guideQuest->id) {
            return false;
        }

        CharacterGuideQuestProgress::updateOrCreate(
            [
                'character_id' => $character->id,
                'guide_quest_id' => $guideQuest->id,
            ],
            [
                'current_guide_quest_walkthrough_step_id' => $completed ? null : $step?->id,
                'started_at' => now(),
                'walkthrough_completed_at' => $completed ? now() : null,
            ],
        );

        return true;
    }

    /**
     * Replace a Guide Quest's ordered walkthrough steps with validated step data.
     *
     * @param GuideQuest $guideQuest
     * @param array $steps
     * @return void
     */
    public function replaceSteps(GuideQuest $guideQuest, array $steps): void
    {
        $normalizedSteps = collect($steps)->values()->map(
            fn (array $step, int $index): array => $this->normalizeStep($step, $index + 1)
        )->all();

        $guideQuest->walkthroughSteps()->delete();
        $guideQuest->walkthroughSteps()->createMany($normalizedSteps);
    }

    /**
     * Determine whether the requested Guide Quest is currently accessible to the Character.
     *
     * @param Character $character
     * @param GuideQuest $guideQuest
     * @return bool
     */
    private function isApplicable(Character $character, GuideQuest $guideQuest): bool
    {
        if (! $character->user->guide_enabled) {
            return false;
        }

        if ($character->questsCompleted()->where('guide_quest_id', $guideQuest->id)->exists()) {
            return false;
        }

        return collect($this->guideQuestService->getCurrentQuestsForCharacter($character))
            ->contains(fn (GuideQuest $currentQuest): bool => $currentQuest->id === $guideQuest->id);
    }

    /**
     * Resolve stale saved progress to the first current walkthrough step.
     *
     * @param CharacterGuideQuestProgress $progress
     * @param array $stepIds
     * @return int|null
     */
    private function resolveCurrentStepId(CharacterGuideQuestProgress $progress, array $stepIds): ?int
    {
        if (! is_null($progress->walkthrough_completed_at)) {
            return null;
        }

        if (in_array($progress->current_guide_quest_walkthrough_step_id, $stepIds, true)) {
            return $progress->current_guide_quest_walkthrough_step_id;
        }

        return $stepIds[0] ?? null;
    }

    /**
     * Normalize one validated walkthrough step into its persisted ordered shape.
     *
     * @param array $step
     * @param int $position
     * @return array
     */
    private function normalizeStep(array $step, int $position): array
    {
        return [
            'position' => $position,
            'title' => $step['title'],
            'content' => $step['content'],
            'target' => $step['target'] ?? null,
            'step_type' => $step['step_type'],
            'required_event' => $step['required_event'] ?? null,
            'optional_if_target_missing' => $step['optional_if_target_missing'] ?? false,
        ];
    }
}
