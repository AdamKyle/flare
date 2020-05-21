<?php

namespace Tests\Unit\Flare\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Events\UpdateCharacterInventoryEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\CharacterSetup;

class UpdateCharacterInventoryEventTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateItem;


    public function testUpdateCharacterInventoryEvent()
    {
        $user = $this->createUser();

        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6'
        ]);

        $character = (new CharacterSetup)->setupCharacter($user, ['can_attack' => false])
                                         ->giveItem($item)
                                         ->equipLeftHand()
                                         ->getCharacter();

        event(new UpdateCharacterInventoryEvent($character));

        Event::fake();

        event(new UpdateCharacterInventoryEvent($character));

        Event::assertDispatched(UpdateCharacterInventoryEvent::class);
    }
}
