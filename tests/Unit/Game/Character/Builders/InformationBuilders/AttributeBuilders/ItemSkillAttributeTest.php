<?php

namespace Tests\Unit\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ItemSkillAttribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateItemSkill;
use Tests\Traits\CreateItemSkillProgression;

class ItemSkillAttributeTest extends TestCase
{
    use CreateClass, CreateGameMap, CreateGameSkill, CreateItem, CreateItemAffix, CreateItemSkill, CreateItemSkillProgression, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?ItemSkillAttribute $itemSkillAttribute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->itemSkillAttribute = resolve(ItemSkillAttribute::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->itemSkillAttribute = null;
    }

    public function test_get_item_skill_attribute_from_artifact_item()
    {

        $item = $this->createItem([
            'type' => 'artifact',
        ]);

        $itemSkill = $this->createItemSkill();

        $this->createItemSkillProgression([
            'item_id' => $item->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 20,
            'current_kill' => 0,
            'is_training' => true,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item->refresh(), true, 'left_hand')->getCharacter();

        $value = $this->itemSkillAttribute->fetchModifier($character, 'str');

        $this->assertEquals(.20, $value);
    }
}
