<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\ItemSkill;
use App\Game\Skills\Services\ItemSkillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ItemSkillServiceTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?ItemSkillService $itemSkillService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();

        $this->itemSkillService = resolve(ItemSkillService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->itemSkillService = null;
    }

    public function test_cannot_find_item_for_item_skill_to_train()
    {

        $character = $this->character->getCharacter();

        $result = $this->itemSkillService->trainSkill($character, 0, 0);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No item found. Either it is not equipped, or it does not exist.', $result['message']);
    }

    public function test_cannot_find_item_for_item_skill_to_train_when_you_have_equipped_items()
    {

        $character = $this->character->equipStartingEquipment()->getCharacter();

        $result = $this->itemSkillService->trainSkill($character, 0, 0);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No item found. Either it is not equipped, or it does not exist.', $result['message']);
    }

    public function test_cannot_find_item_for_item_skill_to_stop_training_when_you_have_no_item_equipped()
    {

        $character = $this->character->equipStartingEquipment()->getCharacter();

        $result = $this->itemSkillService->stopTrainingSkill($character, 0, 0);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Item must be equipped to manage the training of a skill.', $result['message']);
    }

    public function test_cannot_find_item_skill_progression_when_you_have_an_item_that_has_skills()
    {
        $item = $this->createItem(['type' => 'artifact']);

        $itemSkill = ItemSkill::create([
            'name' => 'parent',
            'description' => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $item->itemSkillProgressions()->create([
            'item_id' => $item->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 0,
            'current_kill' => 0,
            'is_training' => false,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $result = $this->itemSkillService->trainSkill($character, $item->id, 0);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No skill found on said item.', $result['message']);
    }

    public function test_cannot_stop_training_skill_when_there_is_no_progression_data()
    {

        $itemSkill = ItemSkill::create([
            'name' => 'parent',
            'description' => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $item = $this->createItem(['type' => 'artifact', 'item_skill_id' => $itemSkill->id]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $result = $this->itemSkillService->stopTrainingSkill($character, $item->id, 0);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No skill found on said item.', $result['message']);
    }

    public function test_cannot_find_item_skill_progression_when_you_have_an_item_that_has_skills_but_no_progression()
    {
        $item = $this->createItem(['type' => 'artifact']);

        ItemSkill::create([
            'name' => 'parent',
            'description' => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $result = $this->itemSkillService->trainSkill($character, $item->id, 0);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No skill found on said item.', $result['message']);
    }

    public function test_cannot_train_skill_when_parent_is_not_trained()
    {

        $itemSkill = ItemSkill::create([
            'name' => 'parent 2',
            'description' => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $childItemSkill = ItemSkill::create([
            'name' => 'child 2',
            'description' => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
            'parent_id' => $itemSkill->id,
            'parent_level_needed' => 4,
        ]);

        $item = $this->createItem(['name' => 'Test Item With Skill', 'type' => 'artifact', 'item_skill_id' => $itemSkill->id]);

        $item->itemSkillProgressions()->create([
            'item_id' => $item->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 0,
            'current_kill' => 0,
            'is_training' => false,
        ]);

        $item = $item->refresh();

        $childItemSkillProgression = $item->itemSkillProgressions()->create([
            'item_id' => $item->id,
            'item_skill_id' => $childItemSkill->id,
            'current_level' => 0,
            'current_kill' => 0,
            'is_training' => false,
        ]);

        $item = $item->refresh();

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'artifact')->getCharacter();

        $result = $this->itemSkillService->trainSkill($character, $item->id, $childItemSkillProgression->id);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You must train the parent skill first.', $result['message']);
    }

    public function test_can_train_child_skill()
    {

        $itemSkill = ItemSkill::create([
            'name' => 'parent',
            'description' => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $childItemSkill = ItemSkill::create([
            'name' => 'child',
            'description' => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
            'parent_id' => $itemSkill->id,
            'parent_level_needed' => 4,
        ]);

        $item = $this->createItem(['type' => 'artifact', 'item_skill_id' => $itemSkill->id]);

        $item->itemSkillProgressions()->create([
            'item_id' => $item->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 5,
            'current_kill' => 0,
            'is_training' => false,
        ]);

        $childItemSkillProgression = $item->itemSkillProgressions()->create([
            'item_id' => $item->id,
            'item_skill_id' => $childItemSkill->id,
            'current_level' => 0,
            'current_kill' => 0,
            'is_training' => false,
        ]);

        $item = $item->refresh();

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $result = $this->itemSkillService->trainSkill($character, $item->id, $childItemSkillProgression->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('You are now training: '.$childItemSkillProgression->itemSkill->name, $result['message']);
    }

    public function test_start_training_the_skill_on_the_item()
    {

        $itemSkill = ItemSkill::create([
            'name' => 'test',
            'description' => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $item = $this->createItem(['type' => 'artifact', 'item_skill_id' => $itemSkill->id]);

        $item = $item->refresh();

        $itemSkillProgression = $item->itemSkillProgressions()->create([
            'item_id' => $item->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 0,
            'current_kill' => 0,
            'is_training' => false,
        ]);

        $item = $item->refresh();

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $result = $this->itemSkillService->trainSkill($character, $item->id, $itemSkillProgression->id);

        $itemSkillProgression = $itemSkillProgression->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('You are now training: '.$itemSkillProgression->itemSkill->name, $result['message']);
        $this->assertTrue($itemSkillProgression->is_training);
    }

    public function test_stop_training_the_skill_on_the_item()
    {

        $itemSkill = ItemSkill::create([
            'name' => 'test',
            'description' => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $item = $this->createItem(['type' => 'artifact', 'item_skill_id' => $itemSkill->id]);

        $item = $item->refresh();

        $itemSkillProgression = $item->itemSkillProgressions()->create([
            'item_id' => $item->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 0,
            'current_kill' => 0,
            'is_training' => true,
        ]);

        $item = $item->refresh();

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $result = $this->itemSkillService->stopTrainingSkill($character, $item->id, $itemSkillProgression->id);

        $itemSkillProgression = $itemSkillProgression->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('You stopped training: '.$itemSkillProgression->itemSkill->name, $result['message']);
        $this->assertFalse($itemSkillProgression->is_training);
    }

    public function test_start_training_of_parent_skill_when_training_parent_skill()
    {

        $itemSkill = ItemSkill::create([
            'name' => 'parent',
            'description' => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $childItemSkill = ItemSkill::create([
            'name' => 'child',
            'description' => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
            'parent_id' => $itemSkill->id,
            'parent_level_needed' => 4,
        ]);

        $item = $this->createItem(['type' => 'artifact', 'item_skill_id' => $itemSkill->id]);

        $parentItemSkillProgression = $item->itemSkillProgressions()->create([
            'item_id' => $item->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 5,
            'current_kill' => 0,
            'is_training' => false,
        ]);

        $childItemSkillProgression = $item->itemSkillProgressions()->create([
            'item_id' => $item->id,
            'item_skill_id' => $childItemSkill->id,
            'current_level' => 0,
            'current_kill' => 0,
            'is_training' => true,
        ]);

        $item = $item->refresh();

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $result = $this->itemSkillService->trainSkill($character, $item->id, $parentItemSkillProgression->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('You are now training: '.$parentItemSkillProgression->itemSkill->name, $result['message']);

        $childItemSkillProgression = $childItemSkillProgression->refresh();

        $this->assertFalse($childItemSkillProgression->is_training);
    }
}
