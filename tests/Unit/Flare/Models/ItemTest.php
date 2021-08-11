<?php

namespace Tests\Unit\Flare\Models;

use App\Flare\Models\GameSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ItemTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateItemAffix,
        CreateGameSkill;

    public function testGetInventorySlot() {

        $character = (new CharacterFactory)->createBaseCharacter()->equipStartingEquipment()->getCharacter();


        $this->assertNotNull($character->inventory->slots()->where('equipped', true)->first());
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

        $this->assertNotEquals(0, $item->getSkillTrainingBonus($this->createGameSkill(['name' => 'test'])));
    }

    public function testGetSkillBonusForSuffix() {
        $itemSuffix = $this->createItemAffix([
            'skill_name'           => 'test',
            'skill_training_bonus' => 0.10
        ]);

        $item = $this->createItem([
            'item_suffix_id' => $itemSuffix->id
        ]);

        $this->assertNotEquals(0, $item->getSkillTrainingBonus($this->createGameSkill(['name' => 'test'])));
    }

    public function testGetSkillBonusForItem() {
        $itemPrefix = $this->createItemAffix([
            'type'                 => 'prefix',
            'skill_name'           => 'Sample',
            'skill_bonus'          => 0.10
        ]);

        $itemSuffix = $this->createItemAffix([
            'base_ac_mod'          => 0.05,
            'skill_name'           => 'Sample',
            'type'                 => 'suffix',
        ]);

        $item = $this->createItem([
            'skill_name'     => 'Sample',
            'skill_bonus'    => 0.01,
            'item_suffix_id' => $itemSuffix->id,
            'item_prefix_id' => $itemPrefix->id,
        ]);

        $this->assertNotEquals(0, $item->getSkillBonus($this->createGameSkill(['name' => 'Sample'])));
    }
}
