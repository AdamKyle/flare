<?php

namespace Tests\Unit\Game\GuideQuests\Services;

use App\Game\Events\Values\EventType;
use App\Game\GuideQuests\Services\GuideQuestProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateQuestsCompleted;

class GuideQuestProgressionServiceTest extends TestCase
{
    use CreateEvent, CreateGuideQuest, CreateQuestsCompleted, RefreshDatabase;

    public function test_regular_progression_returns_the_first_incomplete_quest_in_the_chain(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $root = $this->createGuideQuest();
        $child = $this->createGuideQuest(['parent_id' => $root->id]);
        $this->createQuestsCompleted([
            'character_id' => $character->id,
            'guide_quest_id' => $root->id,
        ]);

        $quests = (new GuideQuestProgressionService)->currentQuests($character);

        $this->assertSame([$child->id], collect($quests)->pluck('id')->all());
    }

    public function test_running_event_returns_the_next_incomplete_event_quest(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createEvent(['type' => EventType::WINTER_EVENT]);
        $root = $this->createGuideQuest(['only_during_event' => EventType::WINTER_EVENT]);
        $child = $this->createGuideQuest([
            'parent_id' => $root->id,
            'only_during_event' => EventType::WINTER_EVENT,
        ]);
        $this->createQuestsCompleted([
            'character_id' => $character->id,
            'guide_quest_id' => $root->id,
        ]);

        $quests = (new GuideQuestProgressionService)->currentQuests($character);

        $this->assertSame([$child->id], collect($quests)->pluck('id')->all());
    }

    public function test_level_unlocked_quest_is_returned_when_the_character_reaches_its_level(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $quest = $this->createGuideQuest(['unlock_at_level' => $character->level]);

        $quests = (new GuideQuestProgressionService)->currentQuests($character);

        $this->assertSame([$quest->id], collect($quests)->pluck('id')->all());
    }
}
