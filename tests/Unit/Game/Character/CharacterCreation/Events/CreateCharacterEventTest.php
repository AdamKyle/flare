<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Events;

use App\Game\Character\CharacterCreation\Events\CreateCharacterEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class CreateCharacterEventTest extends TestCase
{
    use CreateClass, CreateGameMap, CreateRace, CreateUser, RefreshDatabase;

    public function test_uses_the_explicit_character_name_when_provided(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass();
        $map = $this->createGameMap();

        $request = Request::create('/register', 'POST', [
            'race' => $race->id,
            'class' => $class->id,
            'name' => 'Request Name',
        ]);

        $event = new CreateCharacterEvent($user, $map, $request, 'Explicit Name');

        $this->assertTrue($event->user->is($user));
        $this->assertTrue($event->map->is($map));
        $this->assertTrue($event->race->is($race));
        $this->assertTrue($event->class->is($class));
        $this->assertSame('Explicit Name', $event->characterName);
    }

    public function test_falls_back_to_the_request_name_when_no_character_name_is_provided(): void
    {
        $user = $this->createUser();
        $race = $this->createRace();
        $class = $this->createClass();
        $map = $this->createGameMap();

        $request = Request::create('/register', 'POST', [
            'race' => $race->id,
            'class' => $class->id,
            'name' => 'Request Name',
        ]);

        $event = new CreateCharacterEvent($user, $map, $request);

        $this->assertSame('Request Name', $event->characterName);
    }
}
