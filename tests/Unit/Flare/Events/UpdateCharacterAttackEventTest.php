<?php

namespace Tests\Unit\Flare\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Events\UpdateCharacterAttackEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\CharacterSetup;

class UpdateCharacterAttackEventTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateItem;


    public function testUpdateCharacterAttackEvent()
    {
        $user = $this->createUser();

        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6'
        ]);

        $character = (new CharacterSetup)->setupCharacter(['can_attack' => false], $user)
                                         ->equipLeftHand($item)
                                         ->getCharacter();

        event(new UpdateCharacterAttackEvent($character));

        Event::fake();

        event(new UpdateCharacterAttackEvent($character));

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
    }
}
