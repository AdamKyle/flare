<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Monsters\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Monsters\Partials\QuestItem;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;

class QuestItemTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateMonster;

    public function setUp(): void {
        parent::setUp();
    }

    public function testComponentIsLoaded() {
        $monster = $this->createMonster();

        $item    = $this->createItem([
            'name'          => 'Sample',
            'type'          => 'quest',
            'base_damage'   => null,
            'cost'          => null,
            'crafting_type' => null,
        ]); 

        Livewire::test(QuestItem::class, [
            'monster' => $monster->getAttributes(),
        ])->assertSee('Quest Item Drop Chance:');
    }

    public function testTheComponentCallsUpdate() {
        $monster = $this->createMonster();

        Livewire::test(QuestItem::class)->call('update', $monster->id)->assertSet('monster.name', $monster->name);
    }

    public function testSetQuestItemOnMonster() {
        $monster = $this->createMonster();
        $item    = $this->createItem([
            'name'          => 'Sample',
            'type'          => 'quest',
            'base_damage'   => null,
            'cost'          => null,
            'crafting_type' => null,
        ]); 

        Livewire::test(QuestItem::class, [
            'monster' => $monster,
        ])->set('monster.quest_item_id', $item->id)
          ->set('monster.quest_item_drop_chance', 0.20)
          ->call('validateInput', 'finish', 3);

        $this->assertNotNull($monster->refresh()->quest_item_id);
        $this->assertNotNull($monster->refresh()->quest_item_drop_chance);
    }

    public function testSaveMonster() {
        $monster = $this->createMonster();
        $item    = $this->createItem([
            'name'          => 'Sample',
            'type'          => 'quest',
            'base_damage'   => null,
            'cost'          => null,
            'crafting_type' => null,
        ]); 

        Livewire::test(QuestItem::class, [
            'monster' => $monster,
        ])->call('validateInput', 'finish', 3);

        $this->assertNull($monster->refresh()->quest_item_id);
        $this->assertNull($monster->refresh()->quest_item_drop_chance);
    }

    public function testFailToSetQuestItemOnMonsterMissingDropChance() {
        $monster = $this->createMonster();
        $item    = $this->createItem([
            'name'          => 'Sample',
            'type'          => 'quest',
            'base_damage'   => null,
            'cost'          => null,
            'crafting_type' => null,
        ]); 

        Livewire::test(QuestItem::class, [
            'monster' => $monster,
        ])->set('monster.quest_item_id', $item->id)
          ->call('validateInput', 'finish', 3);

        $this->assertNull($monster->refresh()->quest_item_id);
        $this->assertNull($monster->refresh()->quest_item_drop_chance);
    }

    public function testFailToSetQuestItemOnMonsterMissingQuestItem() {
        $monster = $this->createMonster();
        $item    = $this->createItem([
            'name'          => 'Sample',
            'type'          => 'quest',
            'base_damage'   => null,
            'cost'          => null,
            'crafting_type' => null,
        ]); 

        Livewire::test(QuestItem::class, [
            'monster' => $monster,
        ])->set('monster.quest_item_drop_chance', 0.20)
          ->call('validateInput', 'finish', 3);

        $this->assertNull($monster->refresh()->quest_item_id);
        $this->assertNull($monster->refresh()->quest_item_drop_chance);
    }

    public function testFailToSetQuestItemOnMonsterDropChanceBelowZero() {
        $monster = $this->createMonster();
        $item    = $this->createItem([
            'name'          => 'Sample',
            'type'          => 'quest',
            'base_damage'   => null,
            'cost'          => null,
            'crafting_type' => null,
        ]); 

        Livewire::test(QuestItem::class, [
            'monster' => $monster,
        ])->set('monster.quest_item_id', $item->id)
          ->set('monster.quest_item_drop_chance', -100)
          ->call('validateInput', 'finish', 3);

        $this->assertNull($monster->refresh()->quest_item_id);
        $this->assertNull($monster->refresh()->quest_item_drop_chance);
    }
}
