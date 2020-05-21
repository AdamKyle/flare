<?php

namespace Tests\Unit\Flare\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Events\UpdateCharacterSheetEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Setup\CharacterSetup;

class UpdateCharacterSheetEventTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testUpdateCharacterSheetEvent()
    {
        $user = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter($user, ['can_attack' => false])
                                         ->getCharacter();

        event(new UpdateCharacterSheetEvent($character));

        Event::fake();

        event(new UpdateCharacterSheetEvent($character));

        Event::assertDispatched(UpdateCharacterSheetEvent::class);
    }
}
