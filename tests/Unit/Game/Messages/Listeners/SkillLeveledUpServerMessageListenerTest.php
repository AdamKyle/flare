<?php

namespace Tests\Unit\Game\Messages\Listeners;

use App\Flare\Events\SkillLeveledUpServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Setup\Character\CharacterFactory;

class SkillLeveledUpServerMessageListenerTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testServerMessageEventLevelUp()
    {
        $user = (new CharacterFactory)->createBaseCharacter()->getUser();

        event(new SkillLeveledUpServerMessageEvent($user, $user->character->skills->first()));

        $this->assertTrue(true);
    }

}
