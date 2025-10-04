<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Flare\Models\ItemSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class ItemSkillControllerTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_train_item_skill()
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

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/item-skills/train/'.$character->id.'/'.$item->id.'/'.$childItemSkillProgression->id);

        $jsonData = json_decode($response->getContent(), true);

        $character = $character->refresh();

        $itemSkill = $character->inventory->slots()->where('item_id', $item->id)->first()->item->itemSkillProgressions()->where('id', $childItemSkillProgression->id)->first();

        $this->assertEquals('You are now training: '.$childItemSkill->name, $jsonData['message']);
        $this->assertEquals(200, $response->status());
        $this->assertTrue($itemSkill->is_training);
    }

    public function test_stop_training_item_skill()
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
            'is_training' => true,
        ]);

        $item = $item->refresh();

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'right-hand')->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/item-skills/stop-training/'.$character->id.'/'.$item->id.'/'.$childItemSkillProgression->id);

        $jsonData = json_decode($response->getContent(), true);

        $itemSkill = $character->inventory->slots()->where('item_id', $item->id)->first()->item->itemSkillProgressions()->where('id', $childItemSkillProgression->id)->first();

        $this->assertEquals('You stopped training: '.$childItemSkill->name, $jsonData['message']);
        $this->assertEquals(200, $response->status());
        $this->assertFalse($itemSkill->is_training);
    }
}
