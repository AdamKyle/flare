<?php

namespace Tests\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Notification;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateNotification;

class CleanNotificationTest extends TestCase
{
    use RefreshDatabase, CreateNotification;

    public function testCleanNotificationsCommand()
    {

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

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
