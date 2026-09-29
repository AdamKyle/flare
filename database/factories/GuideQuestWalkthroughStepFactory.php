<?php

namespace Database\Factories;

use App\Flare\Models\GuideQuest;
use App\Flare\Models\GuideQuestWalkthroughStep;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughStepType;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuideQuestWalkthroughStepFactory extends Factory
{
    protected $model = GuideQuestWalkthroughStep::class;

    public function definition(): array
    {
        return [
            'guide_quest_id' => GuideQuest::factory(),
            'position' => 1,
            'title' => fake()->sentence(3),
            'content' => fake()->sentence(),
            'target' => null,
            'step_type' => GuideQuestWalkthroughStepType::CONTINUE->value,
            'required_event' => null,
            'optional_if_target_missing' => false,
        ];
    }
}
