<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Listeners;

use App\Game\Character\CharacterCreation\Events\CreateCharacterEvent;
use App\Game\Character\CharacterCreation\Listeners\CreateCharacterListener;
use App\Game\Character\CharacterCreation\Pipeline\CharacterCreationPipeline;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class CreateCharacterListenerTest extends TestCase
{
    use CreateClass, CreateGameMap, CreateRace, CreateUser, RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_handle_builds_state_from_the_event_and_runs_the_pipeline(): void
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

        $pipeline = Mockery::mock(CharacterCreationPipeline::class);
        $pipeline->shouldReceive('run')
            ->once()
            ->with(Mockery::on(function (CharacterBuildState $state) use ($user, $race, $class, $map) {
                return $state->getUser()->is($user)
                    && $state->getRace()->is($race)
                    && $state->getClass()->is($class)
                    && $state->getMap()->is($map)
                    && $state->getCharacterName() === 'Explicit Name'
                    && $state->getNow() !== null;
            }))
            ->andReturnUsing(fn (CharacterBuildState $state) => $state);

        $this->app->instance(CharacterCreationPipeline::class, $pipeline);

        $listener = resolve(CreateCharacterListener::class);

        $listener->handle($event);

        $this->addToAssertionCount(1);
    }
}
