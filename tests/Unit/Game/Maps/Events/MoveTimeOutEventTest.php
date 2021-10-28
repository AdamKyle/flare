<?php

namespace Tests\Unit\Game\Maps\Events;

use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Jobs\MoveTimeOutJob;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameSkill;

class MoveTimeOutEventTest extends TestCase
{
    use RefreshDatabase, CreateGameSkill;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()
                                                   ->givePlayerLocation()
                                                   ->assignSkill($this->createGameSkill([
                                                       'type'                              => SkillTypeValue::EFFECTS_DIRECTIONAL_MOVE_TIMER,
                                                       'move_time_out_mod_bonus_per_level' => 0.001,
                                                   ]), 2000)
                                                   ->assignSkill($this->createGameSkill([
                                                       'type'                              => SkillTypeValue::EFFECTS_MINUTE_MOVE_TIMER,
                                                       'move_time_out_mod_bonus_per_level' => 0.001,
                                                   ]), 2000);

        Queue::fake();
    }

    public function testMovementTimeOutBelowZero() {
        event(new MoveTimeOutEvent($this->character->getCharacter(false), 1, true));

        Queue::assertPushed(MoveTimeOutJob::class);
    }

    public function testMovementWithoutTimeout() {
        event(new MoveTimeOutEvent($this->character->getCharacter(false), 0, false));

        Queue::assertPushed(MoveTimeOutJob::class);
    }
}
