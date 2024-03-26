<?php

namespace Tests\Unit\Game\Skills\Handlers;

use App\Flare\Models\Item;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Handlers\UpdateItemSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateItem;

class UpdateItemSkillTest extends TestCase {

    use RefreshDatabase, CreateFactionLoyalty, CreateItem;

    private ?CharacterFactory $character;

    private ?Item $item;

    private ?UpdateItemSkill $updateItemSkill;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter();

        $this->item      = $this->createItem();

        $this->updateItemSkill = resolve(UpdateItemSkill::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;

        $this->item = null;

        $this->updateItemSkill = null;
    }

    public function testDoesNotUpdateItemSkillWhenItDoesNotExist() {
        Event::fake();

        $character = $this->character->getCharacter();

        $this->updateItemSkill->updateItemSkill($character, $this->item);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testDoesNotUpdateItemSkillWhenItIsMaxedLevel() {
        Event::fake();

        $character = $this->character->getCharacter();

        $item = $this->item;

        $itemSkill = $item->itemSkill()->create([
            'name'                 => 'Sample',
            'description'          => 'Sample',
            'base_damage_mod'      => 0.01,
            'max_level'            => 4,
            'total_kills_needed'   => 100,
        ]);

        $item->itemSkillProgressions()->create([
            'item_id'        => $item->id,
            'item_skill_id'  => $itemSkill->id,
            'current_level'  => 4,
            'current_kill'   => 100,
            'is_training'    => true,
        ]);

        $this->updateItemSkill->updateItemSkill($character, $item->refresh());

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testDoesUpdateItemSkill() {
        Event::fake();

        $character = $this->character->getCharacter();

        $item = $this->item;

        $itemSkill = $item->itemSkill()->create([
            'name'                 => 'Sample',
            'description'          => 'Sample',
            'base_damage_mod'      => 0.01,
            'max_level'            => 4,
            'total_kills_needed'   => 100,
        ]);

        $progression = $item->itemSkillProgressions()->create([
            'item_id'        => $item->id,
            'item_skill_id'  => $itemSkill->id,
            'current_level'  => 3,
            'current_kill'   => 98,
            'is_training'    => true,
        ]);

        $this->updateItemSkill->updateItemSkill($character, $item->refresh());

        $progression = $progression->refresh();

        $this->assertEquals(99, $progression->current_kill);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testDoesLevelItemSkill() {
        Event::fake();

        $character = $this->character->getCharacter();

        $item = $this->item;

        $itemSkill = $item->itemSkill()->create([
            'name'                 => 'Sample',
            'description'          => 'Sample',
            'base_damage_mod'      => 0.01,
            'max_level'            => 4,
            'total_kills_needed'   => 100,
        ]);

        $progression = $item->itemSkillProgressions()->create([
            'item_id'        => $item->id,
            'item_skill_id'  => $itemSkill->id,
            'current_level'  => 3,
            'current_kill'   => 99,
            'is_training'    => true,
        ]);

        $this->updateItemSkill->updateItemSkill($character, $item->refresh());

        $progression = $progression->refresh();

        $this->assertEquals(0, $progression->current_kill);
        $this->assertEquals(4, $progression->current_level);

        Event::assertDispatched(ServerMessageEvent::class);
    }


}
