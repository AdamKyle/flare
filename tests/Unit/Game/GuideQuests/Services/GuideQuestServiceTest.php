<?php

namespace Tests\Unit\Game\GuideQuests\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Item;
use App\Game\GuideQuests\Services\GuideQuestService;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;

class GuideQuestServiceTest extends TestCase {

    use RefreshDatabase, CreateGuideQuest, CreateItem;

    private ?CharacterFactory $character;

    private ?GuideQuestService $guideQuestService;

    private ?Item $item;


    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();

        $this->guideQuestService = resolve(GuideQuestService::class);

        $this->item = $this->createItem(['type' => 'quest']);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character         = null;
        $this->item              = null;
        $this->guideQuestService = null;
    }

    public function testCharacterHasARequirementFromTheGuideQuest() {
        $this->createGuideQuest([
            'required_quest_item_id' => $this->item->id,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->inventoryManagement()
            ->giveItem($this->item)
            ->getCharacter();

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertNotEmpty($questDetails['completed_requirements']);
    }

    public function testHandInGuideQuestAndAlreadyHaveOneOfTheRequirements() {

        $guideQuestToHandIn = $this->createGuideQuest([
            'required_level' => 1,
        ]);

        $this->createGuideQuest([
            'required_quest_item_id' => $this->item->id,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->inventoryManagement()
            ->giveItem($this->item)
            ->getCharacter();

        $this->guideQuestService->handInQuest($character, $guideQuestToHandIn);

        $questDetails = $this->guideQuestService->fetchQuestForCharacter($character);

        $this->assertContains('required_quest_item_id', $questDetails['completed_requirements']);
    }
}
