<?php

namespace Tests\Unit\Game\Core\Events;

use App\Flare\Models\Notification;
use App\Game\Core\Events\CreateAdventureNotificationEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Database\Seeders\GameSkillsSeeder;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateUser;

class CreateAdventureNotificationEventTest extends TestCase
{
    use RefreshDatabase,
        CreateAdventure,
        CreateItem,
        CreateUser;

    private $character;

    private $adventure;

    public function setUp(): void
    {
        parent::setUp();

        $this->adventure = $this->createNewAdventure();

        $item = $this->createItem([
            'name' => 'Spear',
            'base_damage' => 6,
            'type' => 'weapon',
            'crafting_type' => 'weapon',
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->getCharacter();


        $skill = $this->character->skills()->join('game_skills', function($join) {
            $join->on('game_skills.id', 'skills.game_skill_id')
                 ->where('name', 'Looting'); 
        })->first();

        $this->character->adventureLogs()->create([
            'character_id'         => $this->character->id,
            'adventure_id'         => $this->adventure->id,
            'complete'             => true,
            'in_progress'          => false,
            'last_completed_level' => 1,
            'logs'                 => 
            [
                "vcCBZhAOqy3Dg9V6a1MRWCthCGFNResjhH7ttUsFFpREdVoH9oNqyrjVny3cX8McbjyGHZYeJ8txcTov" => [
                    [
                        [
                        "attacker" => "Kyle Adams",
                        "defender" => "Goblin",
                        "messages" => [
                            "Kyle Adams hit for 30",
                        ],
                        "is_monster" => false,
                        ],
                    ],
                ]
            ],
            'rewards'              => 
            [
                "exp" => 100,
                "gold" => 75,
                "items" => [
                    [
                    "id" => $item->id,
                    "name" => $item->name,
                    ],
                ],
                "skill" => [
                    "exp"         => 1000,
                    "skill_name"  => $skill->name,
                    "exp_towards" => $skill->xp_towards,
                ],
            ]
        ]);

        $this->character = $this->character->refresh();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }


    public function testAventureNotification()
    {

        event(new CreateAdventureNotificationEvent($this->character->adventureLogs->first()));

        $this->assertFalse(Notification::all()->isEmpty());

        Event::fake();

        event(new CreateAdventureNotificationEvent($this->character->adventureLogs->first()));

        Event::assertDispatched(CreateAdventureNotificationEvent::class);
    }
}
