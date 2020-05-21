<?php

namespace Tests\Unit\Game\Maps\Adventure\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Maps\Adventure\Jobs\MoveTimeOutJob;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Setup\CharacterSetup;

class MoveTimeOutJobTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testMoveTimeOutJob()
    {
        $user = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])->getCharacter();

        Event::fake();

        MoveTimeOutJob::dispatch($character);

        $character->refresh();

        $this->assertTrue($character->can_move);
    }
}
