<?php

namespace Tests\Feature\Game\GuideQuest\Controllers;

use App\Game\GuideQuests\Services\GuideQuestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;

class GuideQuestsControllerTest extends TestCase
{
    use CreateGuideQuest, RefreshDatabase;

    private ?CharacterFactory $character = null;

    private ?GuideQuestService $guideQuestService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->guideQuestService = resolve(GuideQuestService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->guideQuestService = null;
    }

    public function test_should_see_completed_guide_quest()
    {
        $quest = $this->createGuideQuest([
            'required_level' => 1,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $this->guideQuestService->handInQuest($character, $quest);

        $this->actingAs($character->user)
            ->visit('/game/completed-guide-quests/'.$character->user->id)
            ->see($quest->name);
    }

    public function test_should_beable_to_see_single_guidequest()
    {
        $quest = $this->createGuideQuest([
            'required_level' => 1,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $this->guideQuestService->handInQuest($character, $quest);

        $this->actingAs($character->user)
            ->visit('/game/completed-guide-quest/'.$character->id.'/'.$quest->id)
            ->see($quest->name);
    }
}
