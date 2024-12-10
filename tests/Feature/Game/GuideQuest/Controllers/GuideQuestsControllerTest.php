<?php

namespace Tests\Feature\Game\GuideQuest\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\GuideQuests\Services\GuideQuestService;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;

class GuideQuestsControllerTest extends TestCase
{
    use CreateGuideQuest, RefreshDatabase;

    private ?CharacterFactory $character = null;

    private ?GuideQuestService $guideQuestService = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->guideQuestService = resolve(GuideQuestService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->guideQuestService = null;
    }

    public function testShouldSeeCompletedGuideQuest()
    {
        $quest = $this->createGuideQuest([
            'required_level' => 1
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $this->guideQuestService->handInQuest($character, $quest);

        $this->actingAs($character->user)
            ->visit('/game/completed-guide-quests/' . $character->user->id)
            ->see($quest->name);
    }

    public function testShouldBeableToSeeSingleGuidequest()
    {
        $quest = $this->createGuideQuest([
            'required_level' => 1
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $this->guideQuestService->handInQuest($character, $quest);

        $this->actingAs($character->user)
            ->visit('/game/completed-guide-quest/' . $character->id . '/' . $quest->id)
            ->see($quest->name);
    }
}
