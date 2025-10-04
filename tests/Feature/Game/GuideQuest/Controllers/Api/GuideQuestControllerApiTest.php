<?php

namespace Tests\Feature\Game\GuideQuest\Controllers\Api;

use App\Flare\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;

class GuideQuestControllerApiTest extends TestCase
{
    use CreateGuideQuest, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character = null;

    private ?Item $item = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->item = $this->createItem(['type' => 'quest']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->item = null;
    }

    public function test_next_guide_quest_has_one_of_the_requirements_when_completing_the_previous_quest()
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
        $this->assertIsArray($jsonData['can_hand_in']);

        foreach ($jsonData['can_hand_in'] as $canHandIn) {
            $this->assertFalse($canHandIn['can_hand_in']);
        }
    }

    public function test_get_current_quest()
    {
        $this->createGuideQuest([
            'name' => 'hand in',
            'required_level' => 1,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/guide-quest/'.$character->user->id, [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['completed_requirements']);
        $this->assertIsArray($jsonData['can_hand_in']);

        foreach ($jsonData['can_hand_in'] as $canHandIn) {
            $this->assertTrue($canHandIn['can_hand_in']);
        }
    }

    public function test_fail_to_hand_in_guide_quest()
    {
        $guideQuestToHandIn = $this->createGuideQuest([
            'name' => 'hand in',
            'required_level' => 10,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/guide-quests/hand-in/'.$character->user->id.'/'.$guideQuestToHandIn->id, [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('You cannot hand in this guide quest. You must meet all the requirements first.', $jsonData['message']);
    }
}
