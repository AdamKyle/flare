<?php

namespace Tests\Unit\Game\Maps\Adventure\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Maps\Adventure\Events\MoveTimeOutEvent;
use App\Game\Maps\Adventure\Events\ShowTimeOutEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Setup\CharacterSetup;

class MoveTimeOutEventTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testShowTimeOutEvent()
    {
        $user = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter(['can_move' => false], $user)->getCharacter();

        Event::fake([ShowTimeOutEvent::class]);

        event(new MoveTimeOutEvent($character));

        $character->refresh();

        $this->assertTrue($character->can_move);
    }
}
