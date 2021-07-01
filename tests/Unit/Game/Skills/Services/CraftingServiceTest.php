<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateSkill;

class CraftingServiceTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateGameSkill;

    private $character;

    private $item;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->updateCharacter([
            'gold' => 0,
        ])->assignSkill($this->createGameSkill([
            'type' => SkillTypeValue::CRAFTING,
            'name' => 'Weapon Crafting',
        ]));

        $this->item = $this->createItem([
            'cost' => 10000,
            'can_craft' => true,
            'crafting_type' => 'weapon',
            'skill_level_required' => 0,
        ]);
    }

    public function testItemDoesntExistForCrafting() {
        $craftingService = resolve(CraftingService::class);

        $character = $this->character->getCharacter();

        $this->assertEquals(422, $craftingService->craft($character, ['item_to_craft' => 890, 'type' => 'weapon'])['status']);
    }

    public function testCantAffordCrafting() {
        $craftingService = resolve(CraftingService::class);

        $character = $this->character->getCharacter();

        $this->assertCount(1, $craftingService->craft($character, ['item_to_craft' => $this->item->id, 'type' => 'weapon'])['items']);
    }
}
