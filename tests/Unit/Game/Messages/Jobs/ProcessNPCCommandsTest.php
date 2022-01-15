<?php

namespace Tests\Unit\Game\Messages\Jobs;

use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Values\NpcCommandTypes;
use App\Flare\Values\NpcTypes;
use App\Game\Messages\Jobs\ProcessNPCCommands;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateNpc;

class ProcessNPCCommandsTest extends TestCase {

    use RefreshDatabase, CreateGameMap, CreateNpc;


    public function testNpcProcessCommandsJob() {
        Event::fake();

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter(false);
        $npc       = $this->createNpc(['type' => NpcTypes::QUEST_GIVER]);

        ProcessNPCCommands::dispatch($character->user, $npc, NpcCommandTypes::QUEST);

        Event::assertDispatched(ServerMessageEvent::class);

    }
}
