<?php

namespace Tests\Feature\Game\GuideQuest\Controllers\Api;

use App\Flare\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class GuideQuestControllerApiTest extends TestCase
{
    use CreateGuideQuest, CreateItem, CreateItemAffix, RefreshDatabase;

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
            ->call('POST', '/api/guide-quests/hand-in/' . $character->user->id . '/' . $guideQuestToHandIn->id, [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['completed_requirements']);
        $this->assertIsArray($jsonData['can_hand_in']);

        foreach ($jsonData['can_hand_in'] as $canHandIn) {
            $this->assertFalse($canHandIn['can_hand_in']);
        }
    }

    public function testGetCurrentQuest()
    {
        $this->createGuideQuest([
            'name' => 'hand in',
            'required_level' => 1,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/guide-quest/' . $character->user->id, [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['completed_requirements']);
        $this->assertIsArray($jsonData['can_hand_in']);

        foreach ($jsonData['can_hand_in'] as $canHandIn) {
            $this->assertTrue($canHandIn['can_hand_in']);
        }
    }

    public function testCurrentQuestResponseIncludesIndependentBatchCraftedItemRequirements(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $suffix = $this->createItemAffix(['type' => 'suffix']);
        $mace = $this->createItem(['name' => 'Diamond Mace', 'type' => 'mace', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $chest = $this->createItem(['name' => "Paladin's Oath Chest", 'type' => 'body', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $enchantedMace = $this->createItem(['name' => 'Diamond Mace', 'type' => 'mace', 'parent_id' => $mace->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);
        $quest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'inventory', 'item_id' => $mace->id, 'amount' => 2, 'must_be_enchanted' => true],
                ['source' => 'inventory', 'item_id' => $chest->id, 'amount' => 1, 'must_be_enchanted' => true],
            ],
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();
        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $enchantedMace->id]);
        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $enchantedMace->id]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/guide-quest/' . $character->user->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertJsonPath('quests.0.id', $quest->id)
            ->assertJsonPath('can_hand_in.0.can_hand_in', false)
            ->assertJsonPath('completed_requirements.0.required_batch_crafted_item_requirements.0.requirement_index', 0)
            ->assertJsonPath('completed_requirements.0.required_batch_crafted_item_requirements.0.item_id', $mace->id)
            ->assertJsonPath('completed_requirements.0.required_batch_crafted_item_requirements.0.required_amount', 2)
            ->assertJsonPath('completed_requirements.0.required_batch_crafted_item_requirements.0.current_amount', 2)
            ->assertJsonPath('completed_requirements.0.required_batch_crafted_item_requirements.0.must_be_enchanted', true)
            ->assertJsonPath('completed_requirements.0.required_batch_crafted_item_requirements.0.is_complete', true)
            ->assertJsonPath('completed_requirements.0.required_batch_crafted_item_requirements.1.requirement_index', 1)
            ->assertJsonPath('completed_requirements.0.required_batch_crafted_item_requirements.1.item_id', $chest->id)
            ->assertJsonPath('completed_requirements.0.required_batch_crafted_item_requirements.1.required_amount', 1)
            ->assertJsonPath('completed_requirements.0.required_batch_crafted_item_requirements.1.current_amount', 0)
            ->assertJsonPath('completed_requirements.0.required_batch_crafted_item_requirements.1.must_be_enchanted', true)
            ->assertJsonPath('completed_requirements.0.required_batch_crafted_item_requirements.1.is_complete', false);

        $this->assertNotContains('required_batch_crafted_items', $response->json('completed_requirements.0.completed_requirements'));
        $this->assertArrayNotHasKey('slot_ids', $response->json('completed_requirements.0.required_batch_crafted_item_requirements.0'));
    }

    public function testFailToHandInGuideQuest()
    {
        $guideQuestToHandIn = $this->createGuideQuest([
            'name' => 'hand in',
            'required_level' => 10,
        ]);

        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/guide-quests/hand-in/' . $character->user->id . '/' . $guideQuestToHandIn->id, [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('You cannot hand in this guide quest. You must meet all the requirements first.', $jsonData['message']);
    }
}
