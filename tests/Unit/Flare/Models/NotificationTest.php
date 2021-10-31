<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateNotification;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class NotificationTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateCharacter,
        CreateRace,
        CreateClass,
        CreateNotification,
        CreateAdventure;

    public function testModelRelationships() {
        $notification = $this->createNotification([
            'character_id' => $this->createCharacter([
                'user_id'       => $this->createUser()->id,
                'game_race_id'  => $this->createRace()->id,
                'game_class_id' => $this->createClass()->id,
            ])->id,
            'title' => 'Sample',
            'message' => 'Sample',
            'status' => 'test',
            'type' => 'test',
            'read' => 0,
            'url' => 'test',
            'adventure_id' => $this->createNewAdventure()->id
        ]);

        $this->assertNotNull($notification->character);
        $this->assertNotNull($notification->adventure);
    }
}
