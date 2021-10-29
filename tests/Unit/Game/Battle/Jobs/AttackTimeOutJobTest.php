<?php

namespace Tests\Unit\Game\Battle\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Core\Events\ShowTimeOutEvent;
use App\Game\Core\Jobs\AttackTimeOutJob as JobsAttackTimeOutJob;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;

class AttackTimeOutJobTest extends TestCase
{
    use RefreshDatabase;


    public function testAttackTimeOutJob()
    {
        Event::fake([
            ShowTimeOutEvent::class,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter(false);

        JobsAttackTimeOutJob::dispatch($character);

        $character->refresh();

        $this->assertTrue($character->can_attack);
    }
}
