<?php

namespace Tests\Unit\Game\Messages\Jobs;

use App\Flare\Events\NpcComponentShowEvent;
use App\Flare\Models\GameMap;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\NpcTypes;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Jobs\ProcessNPCCommands;
use App\Game\Messages\Services\NpcCommandService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class NpcCommandServiceTest extends TestCase {

    use RefreshDatabase, CreateGameMap, CreateNpc, CreateMonster, CreateKingdom, CreateItem;

    private $character;

    private $npcCommandService;

    public function setUp(): void{
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();

        $this->npcCommandService = resolve(NpcCommandService::class);

        Event::fake();
    }

    public function testCannotInteractWithAutomation() {
        $character = $this->character->assignAutomation([
            'monster_id' => $this->createMonster()->id,
        ])->getCharacter(false);

        $npc = $this->createNpc();

        $this->npcCommandService->handleNPC($character, $npc, $npc->commands()->first()->command);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testCannotInteractWhenDead() {
        $character = $this->character->updateCharacter([
            'is_dead' => true
        ])->getCharacter(false);

        $npc = $this->createNpc();

        $this->npcCommandService->handleForType($character, $npc);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testCannotInteractWhenAdventuring() {
        $character = $this->character->updateCharacter([
            'can_adventure' => false
        ])->getCharacter(false);

        $npc = $this->createNpc();

        $this->npcCommandService->handleForType($character, $npc);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testCannotInteractWhenNotAtSameLocation() {
        $character = $this->character->getCharacter(false);

        $npc = $this->createNpc([
            'must_be_at_same_location' => true,
            'x_position'               => 499,
            'y_position'               => 499,
        ]);

        $this->npcCommandService->handleForType($character, $npc);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testTakeKingdom() {
        $this->createKingdom([
            'npc_owned'   => true,
            'game_map_id' => GameMap::first()->id
        ]);

        $character = $this->character->getCharacter(false);

        $npc = $this->createNpc();

        $this->npcCommandService->handleForType($character, $npc);

        Event::assertDispatched(ServerMessageEvent::class);

        $character = $character->refresh();

        $this->assertCount(1, $character->kingdoms);
    }

    public function testConjureCommandService() {
        $character = $this->character->getCharacter(false);

        $npc = $this->createNpc([
            'type' => NpcTypes::SUMMONER
        ]);

        $this->npcCommandService->handleForType($character, $npc);

        Event::assertDispatched(NpcComponentShowEvent::class);
    }

    public function testEnchantressCannotInteractWithMissingQuestItem() {
        $map = $this->createGameMap(['name' => 'Hell']);

        $character = $this->character->getCharacter(false);

        $character->map()->update([
            'game_map_id' => $map->id,
        ]);

        $character = $character->refresh();

        $npc = $this->createNpc([
            'type' => NpcTypes::SPECIAL_ENCHANTS
        ]);

        $this->npcCommandService->handleForType($character, $npc);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testEnchantressCannotInteractWithNotInHell() {
        $character = $this->character->getCharacter(false);

        $npc = $this->createNpc([
            'type' => NpcTypes::SPECIAL_ENCHANTS
        ]);

        $this->npcCommandService->handleForType($character, $npc);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testEnchantress() {
        $map = $this->createGameMap(['name' => 'Hell']);

        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'type'   => 'quest',
            'effect' => ItemEffectsValue::QUEEN_OF_HEARTS
        ]))->getCharacter(false);

        $character->map()->update([
            'game_map_id' => $map->id,
        ]);

        $character = $character->refresh();

        $npc = $this->createNpc([
            'type' => NpcTypes::SPECIAL_ENCHANTS
        ]);

        $this->npcCommandService->handleForType($character, $npc);

        Event::assertDispatched(NpcComponentShowEvent::class);
    }

    public function testQuestNpc() {

        Queue::fake();

        $character = $this->character->getCharacter(false);

        $character = $character->refresh();

        $npc = $this->createNpc([
            'type' => NpcTypes::QUEST_GIVER
        ]);

        $this->npcCommandService->handleForType($character, $npc);

        Queue::assertPushed(ProcessNPCCommands::class);
    }

}
