<?php

namespace App\Admin\Import\GuideQuests\Sheets;

use App\Flare\Models\GuideQuest;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughEvent;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughStepType;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughTarget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\ToCollection;

class GuideQuestWalkthroughStepsSheet implements ToCollection
{
    /**
     * Replace walkthrough steps for every Guide Quest represented by the sheet.
     *
     * @param Collection $rows
     * @return void
     */
    public function collection(Collection $rows): void
    {
        $headings = $rows->shift()?->toArray() ?? [];
        $groupedSteps = [];

        foreach ($rows as $row) {
            $step = array_combine($headings, $row->toArray());
            $guideQuestName = $step['guide_quest_name'] ?? null;

            if (blank($guideQuestName)) {
                continue;
            }

            $groupedSteps[$guideQuestName][] = $step;
        }

        foreach ($groupedSteps as $guideQuestName => $steps) {
            $guideQuest = GuideQuest::where('name', $guideQuestName)->firstOrFail();
            $orderedSteps = collect($steps)->sortBy('position')->values()->map(
                fn (array $step, int $index): array => $this->normalizeStep($step, $index + 1)
            )->all();

            DB::transaction(function () use ($guideQuest, $orderedSteps): void {
                $guideQuest->walkthroughSteps()->delete();
                $guideQuest->walkthroughSteps()->createMany($orderedSteps);
            });
        }
    }

    /**
     * Validate controlled identifiers and normalize one imported walkthrough step.
     *
     * @param array $step
     * @param int $position
     * @return array
     */
    private function normalizeStep(array $step, int $position): array
    {
        $type = GuideQuestWalkthroughStepType::tryFrom($step['step_type'] ?? '');
        $target = blank($step['target'] ?? null) ? null : GuideQuestWalkthroughTarget::tryFrom($step['target']);
        $event = blank($step['required_event'] ?? null) ? null : GuideQuestWalkthroughEvent::tryFrom($step['required_event']);

        if (is_null($type) || (! blank($step['target'] ?? null) && is_null($target))) {
            throw new InvalidArgumentException('The walkthrough sheet contains an unknown step type or target.');
        }

        if (! blank($step['required_event'] ?? null) && is_null($event)) {
            throw new InvalidArgumentException('The walkthrough sheet contains an unknown event.');
        }

        if ($type === GuideQuestWalkthroughStepType::CLICK && is_null($target)) {
            throw new InvalidArgumentException('Click walkthrough steps require a target.');
        }

        if ($type === GuideQuestWalkthroughStepType::EVENT && is_null($event)) {
            throw new InvalidArgumentException('Event walkthrough steps require an event.');
        }

        return [
            'position' => $position,
            'title' => $step['title'],
            'content' => $step['content'],
            'target' => $target?->value,
            'step_type' => $type->value,
            'required_event' => $event?->value,
            'optional_if_target_missing' => filter_var($step['optional_if_target_missing'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
