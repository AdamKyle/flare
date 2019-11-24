<?php

namespace Tests\Unit\Flare;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Game\Messages\Builders\ServerMessageBuilder;

class ServerMessageBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function testGetMessageForMessageLengthZero()
    {
        $message = resolve(ServerMessageBuilder::class)->build('message_length_0');

        $this->assertEquals('Your message cannot be empty.', $message);
    }

    public function testGetMessageForMessageLengthMax()
    {
        $message = resolve(ServerMessageBuilder::class)->build('message_to_max');

        $this->assertEquals('Your message is too long.', $message);
    }

    public function testGetMessageForDefault()
    {
        $message = resolve(ServerMessageBuilder::class)->build('');

        $this->assertEquals('', $message);
    }

    public function testGetMessageForMissingUser()
    {
        $message = resolve(ServerMessageBuilder::class)->build('no_matching_user');

        $this->assertEquals('Could not find a user with that name to private message.', $message);
    }

    public function testGetMessageForInvalidCommand()
    {
        $message = resolve(ServerMessageBuilder::class)->build('invalid_command');

        $this->assertEquals('Command not recognized.', $message);
    }

    public function testGetMessageForNoMonster()
    {
        $message = resolve(ServerMessageBuilder::class)->build('no_monster');

        $this->assertEquals('No monster selected. Please select one.', $message);
    }

}
