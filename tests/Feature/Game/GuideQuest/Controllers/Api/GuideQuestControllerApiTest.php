<?php

namespace Tests\Feature\Game\GuideQuest\Controllers\Api;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateGuideQuestWalkthrough;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class GuideQuestControllerApiTest extends TestCase
{
    use CreateGuideQuest, CreateGuideQuestWalkthrough, CreateItem, CreateItemAffix, RefreshDatabase;

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

    public function test_walkthrough_steps_are_loaded_only_from_the_dedicated_endpoint(): void
    {
        $guideQuest = $this->createGuideQuest([
            'required_level' => 2,
        ]);
        $step = $this->createGuideQuestWalkthroughStep([
            'guide_quest_id' => $guideQuest->id,
            'title' => 'Start a Fight',
        ]);
        $character = $this->character->updateUser(['guide_enabled' => true])->getCharacter();

        $currentQuestResponse = $this->actingAs($character->user)
            ->getJson('/api/character/guide-quest/'.$character->user->id);

        $currentQuestResponse->assertOk()
            ->assertJsonPath('quests.0.has_walkthrough', true)
            ->assertJsonMissingPath('quests.0.walkthrough_steps');

        $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->user->id.'/guide-quests/'.$guideQuest->id.'/walkthrough')
            ->assertOk()
            ->assertJsonPath('guide_quest_id', $guideQuest->id)
            ->assertJsonPath('current_step_id', $step->id)
            ->assertJsonPath('steps.0.title', 'Start a Fight');
    }

    public function test_current_quest_response_includes_independent_batch_crafted_item_requirements(): void
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
            ->call('GET', '/api/character/guide-quest/'.$character->user->id, [
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

    public function test_training_skill_guide_quest_requires_training_at_percentage_and_required_skill_level(): void
    {
        $accuracyGameSkill = GameSkill::where('name', 'Accuracy')->firstOrFail();

        $guideQuest = $this->createGuideQuest([
            'name' => 'Training Skill',
            'required_training_skill' => $accuracyGameSkill->id,
            'required_training_xp_sacrifice' => 0.10,
            'required_skill' => $accuracyGameSkill->id,
            'required_skill_level' => 3,
            'xp_reward' => 20,
        ]);

        $character = $this->character
            ->updateUser(['guide_enabled' => true])
            ->getCharacter();

        $accuracy = $character->skills()
            ->where('game_skill_id', $accuracyGameSkill->id)
            ->firstOrFail();

        $accuracy->update([
            'level' => 2,
            'currently_training' => true,
            'xp_towards' => 0.10,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/guide-quest/'.$character->user->id);

        $response->assertOk()
            ->assertJsonPath('quests.0.id', $guideQuest->id)
            ->assertJsonPath('quests.0.skill_name', 'Accuracy')
            ->assertJsonPath('quests.0.training_skill_name', 'Accuracy')
            ->assertJsonPath('quests.0.required_skill_level', 3)
            ->assertJsonPath('quests.0.required_training_xp_sacrifice', 0.10)
            ->assertJsonPath('quests.0.xp_reward', 20)
            ->assertJsonPath('can_hand_in.0.can_hand_in', false);

        $completedRequirements = $response->json('completed_requirements.0.completed_requirements');

        $this->assertContains('required_training_xp_sacrifice', $completedRequirements);
        $this->assertNotContains('required_skill_level', $completedRequirements);

        $accuracy->update([
            'level' => 3,
            'currently_training' => true,
            'xp_towards' => 0.10,
        ]);

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/guide-quest/'.$character->user->id);

        $response->assertOk()
            ->assertJsonPath('can_hand_in.0.can_hand_in', true);

        $completedRequirements = $response->json('completed_requirements.0.completed_requirements');

        $this->assertContains('required_training_xp_sacrifice', $completedRequirements);
        $this->assertContains('required_skill_level', $completedRequirements);

        $this->actingAs($character->user)
            ->postJson('/api/guide-quests/hand-in/'.$character->user->id.'/'.$guideQuest->id)
            ->assertSuccessful();
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
