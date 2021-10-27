<?php

namespace Tests\Unit\Game\Messages\Listeners;

use App\Flare\Events\KingdomServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Setup\Character\CharacterFactory;

class KingdomServerMessageTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testMessageSentOutForKingdomServerMessages()
    {
        $user = (new CharacterFactory)->createBaseCharacter()->getUser();

        event(new KingdomServerMessageEvent($user, 'under-attack', 'sample'));

        $this->assertTrue(true);
    }
}
