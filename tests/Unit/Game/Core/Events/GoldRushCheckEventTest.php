<?php

namespace Tests\Unit\Game\Core\Events;

use App\Flare\Models\Notification;
use App\Game\Core\Events\AttackTimeOutEvent;
use App\Game\Core\Events\CreateAdventureNotificationEvent;
use App\Game\Core\Events\GoldRushCheckEvent;
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
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateUser;

class GoldRushCheckEventTest extends TestCase
{
    use RefreshDatabase,
        CreateAdventure,
        CreateMonster,
        CreateUser,
        CreateGameSkill;

    private $character;

    private $adventure;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()
                                                   ->givePlayerLocation();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testGoldRushEvent() {
        $character = $this->character->getCharacter(false);

        $character->map->gameMap->update([
            'drop_chance_bonus' => 0.01,
        ]);

        event(new GoldRushCheckEvent($character->refresh(), $this->createMonster()));

        $this->assertTrue(true);
    }
}
