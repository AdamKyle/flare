<?php

namespace Tests\Unit\Game\Messages\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Messages\Models\Message;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class MessageTest extends TestCase
{
    use RefreshDatabase, CreateUser;


    public function testHasMessage()
    {
        $user = $this->createUser();

        $user->messages()->create(['message' => 'hello']);

        $this->assertNotNull(Message::where('message', 'hello')->first());
    }

    public function testMessageBelongsToUser()
    {
        $user = $this->createUser();

        $user->messages()->create(['message' => 'hello']);

        $this->assertEquals(Message::where('message', 'hello')->first()->user->id, $user->id);
    }
}
