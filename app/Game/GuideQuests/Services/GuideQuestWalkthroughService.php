<?php

namespace App\Game\GuideQuests\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGuideQuestProgress;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\GuideQuestWalkthroughStep;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughEvent;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughStepType;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughTarget;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
     * @param ?GuideQuestWalkthroughStep $step
     * @param bool $completed
     * @return bool
     */
    public function updateProgress(
        Character $character,
        GuideQuest $guideQuest,
        ?GuideQuestWalkthroughStep $step,
        bool $completed,
    ): bool {
        if (! $this->isApplicable($character, $guideQuest)) {
            return false;
        }

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
     * Replace a Guide Quest's ordered walkthrough steps after validating controlled values.
     *
     * @param GuideQuest $guideQuest
     * @param array $steps
     * @return void
     */
    public function replaceSteps(GuideQuest $guideQuest, array $steps): void
    {
        $normalizedSteps = collect($steps)->values()->map(
            fn (array $step, int $index): array => $this->validateStep($step, $index + 1)
        )->all();

        DB::transaction(function () use ($guideQuest, $normalizedSteps): void {
            $guideQuest->walkthroughSteps()->delete();
            $guideQuest->walkthroughSteps()->createMany($normalizedSteps);
        });
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
     * Validate and normalize one authored walkthrough step.
     *
     * @param array $step
     * @param int $position
     * @return array
     */
    private function validateStep(array $step, int $position): array
    {
        $type = GuideQuestWalkthroughStepType::tryFrom($step['step_type'] ?? '');
        $target = is_null($step['target'] ?? null) ? null : GuideQuestWalkthroughTarget::tryFrom($step['target']);
        $event = is_null($step['required_event'] ?? null) ? null : GuideQuestWalkthroughEvent::tryFrom($step['required_event']);

        if (is_null($type)) {
            throw ValidationException::withMessages(['steps' => 'A walkthrough step has an unknown type.']);
        }

        if (! is_null($step['target'] ?? null) && is_null($target)) {
            throw ValidationException::withMessages(['steps' => 'A walkthrough step has an unknown target.']);
        }

        if (! is_null($step['required_event'] ?? null) && is_null($event)) {
            throw ValidationException::withMessages(['steps' => 'A walkthrough step has an unknown event.']);
        }

        if ($type === GuideQuestWalkthroughStepType::CLICK && is_null($target)) {
            throw ValidationException::withMessages(['steps' => 'Click walkthrough steps require a target.']);
        }

        if ($type === GuideQuestWalkthroughStepType::EVENT && is_null($event)) {
            throw ValidationException::withMessages(['steps' => 'Event walkthrough steps require an event.']);
        }

        return [
            'position' => $position,
            'title' => $step['title'],
            'content' => $step['content'],
            'target' => $target?->value,
            'step_type' => $type->value,
            'required_event' => $event?->value,
            'optional_if_target_missing' => $step['optional_if_target_missing'] ?? false,
        ];
    }
}
