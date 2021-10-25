<?php

namespace Tests\Unit\Game\Maps\Adventure\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Events\ShowTimeOutEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Setup\Character\CharacterFactory;

class MoveTimeOutEventTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testShowTimeOutEvent()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Event::fake([ShowTimeOutEvent::class]);

        event(new MoveTimeOutEvent($character));

        $character->refresh();

        $this->assertTrue($character->can_move);
    }
}
