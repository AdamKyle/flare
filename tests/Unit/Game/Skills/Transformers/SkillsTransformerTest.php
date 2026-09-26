<?php

namespace Tests\Unit\Game\Skills\Transformers;

use App\Flare\Models\GameSkill;
use App\Game\Skills\Services\SkillBonusContextService;
use App\Game\Skills\Services\SkillBonusService;
use App\Game\Skills\Transformers\SkillsTransformer;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class SkillsTransformerTest extends TestCase
{
    use CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?GameSkill $gameSkill;

    private ?SkillsTransformer $skillsTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gameSkill = $this->createGameSkill([
            'name' => 'Transformer Test Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
            'can_train' => false,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill($this->gameSkill);

        $this->skillsTransformer = new SkillsTransformer(new SkillBonusService(new SkillBonusContextService));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->gameSkill = null;
        $this->skillsTransformer = null;
    }

    public function test_equipped_item_contribution_identifies_the_equipped_slot(): void
    {
        $prefix = $this->createItemAffix([
            'skill_name' => $this->gameSkill->name,
            'skill_bonus' => 0.10,
            'skill_training_bonus' => null,
        ]);

        $item = $this->createItem(['name' => 'Crafting Blade', 'type' => 'weapon', 'item_prefix_id' => $prefix->id]);

        $inventoryManagement = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand');

        $character = $inventoryManagement->getCharacter();
        $skill = $character->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $result = $this->skillsTransformer->transform($skill);

        $this->assertCount(1, $result['items_affecting_skill']);
        $this->assertSame($item->id, $result['items_affecting_skill'][0]['item_id']);
        $this->assertSame($inventoryManagement->getSlotId(0), $result['items_affecting_skill'][0]['slot_id']);
        $this->assertSame('equipped', $result['items_affecting_skill'][0]['source']);
        $this->assertSame('left-hand', $result['items_affecting_skill'][0]['position']);
        $this->assertEqualsWithDelta(0.10, $result['items_affecting_skill'][0]['skill_bonus'], 0.00001);
        $this->assertSame(0.0, $result['items_affecting_skill'][0]['skill_training_bonus']);
    }

    public function test_quest_item_contribution_identifies_the_quest_slot(): void
    {
        $questItem = $this->createItem([
            'name' => 'Crafting Tome',
            'type' => 'quest',
            'skill_name' => $this->gameSkill->name,
            'skill_bonus' => null,
            'skill_training_bonus' => 0.25,
        ]);

        $inventoryManagement = $this->character->inventoryManagement()->giveItem($questItem);

        $character = $inventoryManagement->getCharacter();
        $skill = $character->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $result = $this->skillsTransformer->transform($skill);

        $this->assertCount(1, $result['items_affecting_skill']);
        $this->assertSame($questItem->id, $result['items_affecting_skill'][0]['item_id']);
        $this->assertSame($inventoryManagement->getSlotId(0), $result['items_affecting_skill'][0]['slot_id']);
        $this->assertSame('quest', $result['items_affecting_skill'][0]['source']);
        $this->assertSame(0.0, $result['items_affecting_skill'][0]['skill_bonus']);
        $this->assertEqualsWithDelta(0.25, $result['items_affecting_skill'][0]['skill_training_bonus'], 0.00001);
    }

    public function test_item_contributing_both_bonuses_is_listed_once_with_each_value_kept_separate(): void
    {
        $questItem = $this->createItem([
            'name' => 'Crafting Codex',
            'type' => 'quest',
            'skill_name' => $this->gameSkill->name,
            'skill_bonus' => 0.05,
            'skill_training_bonus' => 0.15,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($questItem)->getCharacter();
        $skill = $character->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $result = $this->skillsTransformer->transform($skill);

        $this->assertCount(1, $result['items_affecting_skill']);
        $this->assertEqualsWithDelta(0.05, $result['items_affecting_skill'][0]['skill_bonus'], 0.00001);
        $this->assertEqualsWithDelta(0.15, $result['items_affecting_skill'][0]['skill_training_bonus'], 0.00001);
    }

    public function test_equipped_item_without_a_positive_contribution_is_excluded(): void
    {
        $prefix = $this->createItemAffix([
            'skill_name' => 'Some Other Skill',
            'skill_bonus' => 0.10,
            'skill_training_bonus' => 0.10,
        ]);

        $item = $this->createItem(['name' => 'Unrelated Blade', 'type' => 'weapon', 'item_prefix_id' => $prefix->id]);

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();
        $skill = $character->skills->where('game_skill_id', $this->gameSkill->id)->first();

        $result = $this->skillsTransformer->transform($skill);

        $this->assertSame([], $result['items_affecting_skill']);
    }
}
