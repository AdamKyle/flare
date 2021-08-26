<?php

namespace Tests\Unit\Flare\Traits\Controllers;

use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Traits\Controllers\ItemsShowInformation;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Setup\Character\CharacterFactory;

class ItemsShowInformationTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateGameSkill;

    public function setUp(): void {
        parent::setUp();
    }

    public function testGetItemShowForWalkOnWaterItem() {
        $item = $this->createItem(['type' => 'quest', 'effect' => 'walk-on-water']);

        $trait = $this->getObjectForTrait(ItemsShowInformation::class);

        $itemView = $trait->renderItemShow('game.items.item', $item);

        $this->assertInstanceOf(View::class, $itemView);
    }

    public function testGetItemShowForWalkOnDeathWaterItem() {
        $item = $this->createItem(['type' => 'quest', 'effect' => 'walk-on-death-water']);

        $trait = $this->getObjectForTrait(ItemsShowInformation::class);

        $itemView = $trait->renderItemShow('game.items.item', $item);

        $this->assertInstanceOf(View::class, $itemView);
    }

    public function testGetItemShowForAccessLabyrinthItem() {
        $item = $this->createItem(['type' => 'quest', 'effect' => 'labyrinth']);

        $trait = $this->getObjectForTrait(ItemsShowInformation::class);

        $itemView = $trait->renderItemShow('game.items.item', $item);

        $this->assertInstanceOf(View::class, $itemView);
    }

    public function testGetItemShowForAccessDungeonsItem() {
        $item = $this->createItem(['type' => 'quest', 'effect' => 'dungeon']);

        $trait = $this->getObjectForTrait(ItemsShowInformation::class);

        $itemView = $trait->renderItemShow('game.items.item', $item);

        $this->assertInstanceOf(View::class, $itemView);
    }

    public function testGetItemShowForUsableItemThatAffectsSkills() {
        $item = $this->createItem(['type' => 'quest', 'usable' => true, 'affects_skill_type' => SkillTypeValue::TRAINING]);

        $this->createGameSkill([
            'can_train' => true,
            'type' => SkillTypeValue::TRAINING,
        ]);

        $trait = $this->getObjectForTrait(ItemsShowInformation::class);

        $itemView = $trait->renderItemShow('game.items.item', $item);

        $this->assertInstanceOf(View::class, $itemView);
    }

    public function testGetItemShowForUsableItemThatAffectsSkillsLoggedIn() {
        $gameSkill = $this->createGameSkill(['type' => SkillTypeValue::ALCHEMY]);

        $character = (new CharacterFactory())->createBaseCharacter()->assignSkill($gameSkill)->getUser();

        $this->actingAs($character);

        $item = $this->createItem(['type' => 'quest', 'usable' => true, 'affects_skill_type' => SkillTypeValue::TRAINING]);

        $this->createGameSkill([
            'can_train' => true,
            'type' => SkillTypeValue::TRAINING,
        ]);

        $trait = $this->getObjectForTrait(ItemsShowInformation::class);

        $itemView = $trait->renderItemShow('game.items.item', $item);

        $this->assertInstanceOf(View::class, $itemView);
    }
}
