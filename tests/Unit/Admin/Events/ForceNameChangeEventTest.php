<?php

namespace Tests\Unit\Admin\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Admin\Events\ForceNameChangeEvent;
use Tests\Setup\CharacterSetup;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class ForceNameChangeEventTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testUpdateForceNameChangeEvent()
    {
        $user = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter($user)
                                         ->getCharacter();

        event(new ForceNameChangeEvent($character));

        Event::fake();

        event(new ForceNameChangeEvent($character));

        Event::assertDispatched(ForceNameChangeEvent::class);
    }
}
