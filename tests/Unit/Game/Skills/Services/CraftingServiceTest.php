<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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
        ])->givePlayerLocation()->assignSkill($this->createGameSkill([
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
        Event::fake([ServerMessageEvent::class]);

        $craftingService = resolve(CraftingService::class);

        $character = $this->character->getCharacter();

        $craftingService->craft($character, ['item_to_craft' => 890, 'type' => 'weapon']);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testCantAffordCrafting() {
        Event::fake([ServerMessageEvent::class]);

        $craftingService = resolve(CraftingService::class);

        $character = $this->character->getCharacter();

        $craftingService->craft($character, ['item_to_craft' => 890, 'type' => 'weapon']);

        Event::assertDispatched(ServerMessageEvent::class);
    }
}
