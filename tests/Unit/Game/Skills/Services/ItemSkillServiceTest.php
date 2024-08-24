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

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();

        $this->itemSkillService = resolve(ItemSkillService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->itemSkillService = null;
    }

    public function testCannotFindItemForItemSkillToTrain()
    {

        $character = $this->character->getCharacter();

        $result = $this->itemSkillService->trainSkill($character, 0, 0);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No item found. Either it is not equipped, or it does not exist.', $result['message']);
    }

    public function testCannotFindItemForItemSkillToTrainWhenYouHaveEquippedItems()
    {

        $character = $this->character->equipStartingEquipment()->getCharacter();

        $result = $this->itemSkillService->trainSkill($character, 0, 0);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No item found. Either it is not equipped, or it does not exist.', $result['message']);
    }

    public function testCannotFindItemForItemSkillToStopTrainingWhenYouHaveNoItemEquipped()
    {

        $character = $this->character->equipStartingEquipment()->getCharacter();

        $result = $this->itemSkillService->stopTrainingSkill($character, 0, 0);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Item must be equipped to manage the training of a skill.', $result['message']);
    }

    public function testCannotFindItemSkillProgressionWhenYouHaveAnItemThatHasSkills()
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

    public function testCannotStopTrainingSkillWhenThereIsNoProgressionData()
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

    public function testCannotFindItemSkillProgressionWhenYouHaveAnItemThatHasSkillsButNoProgression()
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

    public function testCannotTrainSkillWhenParentIsNotTrained()
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

    public function testCanTrainChildSkill()
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

    public function testStartTrainingTheSkillOnTheItem()
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

    public function testStopTrainingTheSkillOnTheItem()
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

    public function testStartTrainingOfParentSkillWhenTrainingParentSkill()
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
