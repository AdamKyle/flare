<?php

namespace Database\Factories;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGuideQuestProgress;
use App\Flare\Models\GuideQuest;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterGuideQuestProgressFactory extends Factory
{
    protected $model = CharacterGuideQuestProgress::class;

    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'guide_quest_id' => GuideQuest::factory(),
            'current_guide_quest_walkthrough_step_id' => null,
            'started_at' => null,
            'walkthrough_completed_at' => null,
            'hand_in_ready_at' => null,
            'attention_acknowledged_at' => null,
        ];
    }
}
