<?php

namespace Tests\Console\Messages;

use App\Game\Messages\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\CreateMessage;
use Tests\Traits\CreateUser;

class CleanChatTest extends TestCase
{
    use CreateMessage, CreateUser, RefreshDatabase;

    public function testClearChat()
    {
        $this->createMessage($this->createUser());

        DB::table('messages')->update([
            'created_at' => now()->subMonths(6),
        ]);

        $this->assertEquals(0, $this->artisan('clean:chat'));

        $this->assertTrue(Message::all()->isEmpty());
    }
}
