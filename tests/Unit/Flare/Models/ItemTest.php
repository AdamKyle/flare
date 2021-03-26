<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Item;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ItemTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateItemAffix;

    public function testGetInventorySlot() {

        (new CharacterFactory)->createBaseCharacter()->equipStartingEquipment();

        $item = Item::first();

        $this->assertNotNull($item->slot);
    }

    public function getSuffixItemBaseACMod() {
        $itemSuffix = $this->createItem([
            'base_ac_mod'          => 0.05,
            'type'                 => 'suffix',
        ]);

        $item = $this->createItem([
            'item_suffix_id' => $itemSuffix->id
        ]);

        $this->assertNotEquals(0, $item->getTotalDefence());
    }

    public function getPrefixItemBaseACMod() {
        $itemSuffix = $this->createItem([
            'base_ac_mod'          => 0.05,
            'type'                 => 'prefix',
        ]);

        $item = $this->createItem([
            'item_prefix_id' => $itemSuffix->id
        ]);

        $this->assertNotEquals(0, $item->getTotalDefence());
    }

    public function testGetSkillBonusForPrefix() {
        $itemSuffix = $this->createItemAffix([
            'type'                 => 'prefix',
            'skill_name'           => 'test',
            'skill_training_bonus' => 0.10
        ]);

        $item = $this->createItem([
            'item_prefix_id' => $itemSuffix->id
        ]);
        
        $this->assertNotEquals(0, $item->getSkillTrainingBonus('test'));
    }

    public function testGetSkillBonusForSuffix() {
        $itemSuffix = $this->createItemAffix([
            'skill_name'           => 'test',
            'skill_training_bonus' => 0.10
        ]);

        $item = $this->createItem([
            'item_suffix_id' => $itemSuffix->id
        ]);

        $this->assertNotEquals(0, $item->getSkillTrainingBonus('test'));
    }
}
