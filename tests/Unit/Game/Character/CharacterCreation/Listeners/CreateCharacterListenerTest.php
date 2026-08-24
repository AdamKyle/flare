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

        $state = new CharacterBuildState();

        $capturedState = null;

        $pipeline = Mockery::mock(CharacterCreationPipeline::class);
        $pipeline->shouldReceive('run')
            ->once()
            ->with(Mockery::on(function (CharacterBuildState $runState) use (&$capturedState) {
                $capturedState = $runState;

                return true;
            }))
            ->andReturnUsing(fn (CharacterBuildState $runState) => $runState);

        $listener = new CreateCharacterListener($pipeline, $state);

        $listener->handle($event);

        $this->assertSame($state, $capturedState);
        $this->assertTrue($capturedState->getUser()->is($user));
        $this->assertTrue($capturedState->getRace()->is($race));
        $this->assertTrue($capturedState->getClass()->is($class));
        $this->assertTrue($capturedState->getMap()->is($map));
        $this->assertSame('Explicit Name', $capturedState->getCharacterName());
        $this->assertNotNull($capturedState->getNow());
    }
}
