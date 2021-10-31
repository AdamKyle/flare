<?php

namespace Tests\Unit\Game\Core\Events;

use App\Flare\Models\Notification;
use App\Game\Core\Events\AttackTimeOutEvent;
use App\Game\Core\Events\CreateAdventureNotificationEvent;
use App\Game\Core\Jobs\AttackTimeOutJob;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Database\Seeders\GameSkillsSeeder;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateUser;

class AttackTimeoutEventTest extends TestCase
{
    use RefreshDatabase,
        CreateAdventure,
        CreateItem,
        CreateUser,
        CreateGameSkill;

    private $character;

    private $adventure;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()
                                                   ->givePlayerLocation()
                                                   ->assignSkill($this->createGameSkill([
                                                       'type'                               => SkillTypeValue::EFFECTS_BATTLE_TIMER,
                                                       'fight_time_out_mod_bonus_per_level' => 0.01,
                                                   ]), 200);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testAttackTimeOutEvent() {
        Queue::fake();

        event(new AttackTimeOutEvent($this->character->getCharacter(false)));

        Queue::assertPushed(AttackTimeOutJob::class);
    }
}
