<?php

namespace Tests\Traits;

use App\Flare\Models\CharacterGuideQuestProgress;
use App\Flare\Models\GuideQuestWalkthroughStep;

trait CreateGuideQuestWalkthrough
{
    /**
     * Create a Guide Quest walkthrough step fixture.
     *
     * @param array $options
     * @return GuideQuestWalkthroughStep
     */
    public function createGuideQuestWalkthroughStep(array $options = []): GuideQuestWalkthroughStep
    {
        return GuideQuestWalkthroughStep::factory()->create($options);
    }

    /**
     * Create a Character Guide Quest progress fixture.
     *
     * @param array $options
     * @return CharacterGuideQuestProgress
     */
    public function createCharacterGuideQuestProgress(array $options = []): CharacterGuideQuestProgress
    {
        return CharacterGuideQuestProgress::factory()->create($options);
    }
}
