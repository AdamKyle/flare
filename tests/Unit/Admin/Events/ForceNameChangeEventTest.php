<?php

namespace Tests\Unit\Admin\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Admin\Events\ForceNameChangeEvent;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class ForceNameChangeEventTest extends TestCase
{
    use RefreshDatabase;


    public function testUpdateForceNameChangeEvent()
    {

        $character = (new CharacterFactory)->createBaseCharacter()
                                           ->getCharacter(false);

        event(new ForceNameChangeEvent($character));

        Event::fake();

        event(new ForceNameChangeEvent($character));

        Event::assertDispatched(ForceNameChangeEvent::class);
    }
}
