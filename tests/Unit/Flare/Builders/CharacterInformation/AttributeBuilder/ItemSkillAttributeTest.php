<?php

namespace Tests\Unit\Flare\Builders\CharacterInformation\AttributeBuilder;

use App\Flare\Builders\CharacterInformation\AttributeBuilders\ItemSkillAttribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\ItemSkill;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ItemSkillAttributeTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateItemAffix, CreateGameMap, CreateClass, CreateGameSkill;

    private ?CharacterFactory $character;

    private ?ItemSkillAttribute $itemSkillAttribute;

    public function setUp(): void {
        parent::setUp();

        $this->character            = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $this->itemSkillAttribute   = resolve(ItemSkillAttribute::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character          = null;
        $this->itemSkillAttribute = null;
    }

    public function testGetItemSkillAttributeFromArtifactItem() {

        $item = $this->createItem([
            'type' => 'artifact',
        ]);

        $itemSkill = ItemSkill::create([
            'name'               => 'Sample',
            'description'        => 'Test',
            'str_mod'            => 0.01,
            'max_level'          => 100,
            'total_kills_needed' => 1000,
        ]);

        $item->itemSkillProgressions()->create([
            'item_id'        => $item->id,
            'item_skill_id'  => $itemSkill->id,
            'current_level'  => 20,
            'current_kill'   => 0,
            'is_training'    => true,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item->refresh(), true, 'left_hand')->getCharacter();

        $value = $this->itemSkillAttribute->fetchModifier($character, 'str');

        $this->assertEquals(.20, $value);
    }
}
