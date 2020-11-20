<?php

namespace Tests\Console;

use App\Flare\Models\Notification;
use Database\Seeders\GameSkillsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\CharacterSetup;
use Tests\TestCase;
use Tests\Traits\CreateNotification;
use Tests\Traits\CreateUser;

class CleanNotificationTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateNotification;

    public function testCleanNotificationsCommand()
    {
        $this->seed(GameSkillsSeeder::class);
        
        $user = $this->createUser();

        $character = (new CharacterSetup)->setupCharacter($user)->getCharacter();

        $this->createNotification([
            'character_id' => $character->id,
            'title' => 'Sample',
            'message' => 'Sample',
            'status' => 'succes',
            'type' => 'notification',
            'read' => true,
            'url' => 'test',
        ]);   

        $this->assertEquals(0, $this->artisan('clean:notifications'));

        $this->assertEquals(0, Notification::where('read', true)->count());
    }
}
