<?php

namespace Tests\Feature\Game\GuideQuest\Controllers\Api;

use App\Flare\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;

class GuideQuestControllerTest extends TestCase
{
    use CreateGuideQuest, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character = null;

    private ?Item $item = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->item = $this->createItem(['type' => 'quest']);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->item = null;
    }

    public function testNextGuideQuestHasOneOfTheRequirementsWhenCompletingThePreviousQuest()
    {
        $guideQuestToHandIn = $this->createGuideQuest([
            'name' => 'hand in',
            'required_level' => 1,
        ]);

        $this->createGuideQuest([
            'name' => 'secondary guide quest',
            'required_quest_item_id' => $this->item->id,
            'required_level' => 20,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])
            ->inventoryManagement()
            ->giveItem($this->item)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/guide-quests/hand-in/'.$character->user->id.'/'.$guideQuestToHandIn->id, [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['completed_requirements']);
        $this->assertFalse($jsonData['can_hand_in']);
    }
}
