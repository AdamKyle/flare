<?php

namespace Tests\Unit\Flare\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Events\UpdateCharacterAttackEvent;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\Character\CharacterFactory;

class UpdateCharacterAttackEventTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateItem;


    public function testUpdateCharacterAttackEvent()
    {
        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6'
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
                                         ->updateCharacter([
                                            'can_attack' => false,
                                         ])
                                         ->inventoryManagement()
                                         ->giveItem($item)
                                         ->equipLeftHand($item->name)
                                         ->getCharacterFactory()
                                         ->getCharacter();

        event(new UpdateCharacterAttackEvent($character));

        Event::fake();

        event(new UpdateCharacterAttackEvent($character));

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
    }
}
