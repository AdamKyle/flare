<?php

namespace Tests\Unit\Game\Maps\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Maps\Jobs\MoveTimeOutJob;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;

class MoveTimeOutJobTest extends TestCase
{
    use RefreshDatabase;


    public function testMoveTimeOutJob()
    {

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter(false);

        Event::fake();

        MoveTimeOutJob::dispatch($character->id);

        $character->refresh();

        $this->assertTrue($character->can_move);
    }


}
