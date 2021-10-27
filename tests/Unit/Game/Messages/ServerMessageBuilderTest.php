<?php

namespace Tests\Unit\Game\Messages;

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

    public function testGetMessageForDeadCharacter()
    {
        $message = resolve(ServerMessageBuilder::class)->build('dead_character');

        $this->assertEquals('You are dead. Please revive your self by clicking revive.', $message);
    }

    public function testGetMessageFullInventory()
    {
        $message = resolve(ServerMessageBuilder::class)->build('inventory_full');

        $this->assertEquals('Your inventory is full, you cannot pick up this item!', $message);
    }

    public function testGetMessageCantAttack()
    {
        $message = resolve(ServerMessageBuilder::class)->build('cant_attack');

        $this->assertEquals('Please wait for the timer (beside Again!) to state: Ready!', $message);
    }

    public function testGetMessageCannotMoveUp()
    {
        $message = resolve(ServerMessageBuilder::class)->build('cannot_move_up');

        $this->assertEquals('You cannot go that way.', $message);
    }

    public function testGetMessageCannotMoveLeft()
    {
        $message = resolve(ServerMessageBuilder::class)->build('cannot_move_left');

        $this->assertEquals('You cannot go that way.', $message);
    }

    public function testGetMessageCannotMoveDown()
    {
        $message = resolve(ServerMessageBuilder::class)->build('cannot_move_down');

        $this->assertEquals('You cannot go that way.', $message);
    }

    public function testGetMessageCannotMoveRight()
    {
        $message = resolve(ServerMessageBuilder::class)->build('cannot_move_right');

        $this->assertEquals('You cannot go that way.', $message);
    }

    public function testGetMessageCannotMove()
    {
        $message = resolve(ServerMessageBuilder::class)->build('cant_move');

        $this->assertEquals('Please wait for the timer (beside movement options) to state: Ready!', $message);
    }

    public function testGetMessageCannotWalkOnWater()
    {
        $message = resolve(ServerMessageBuilder::class)->build('cannot_walk_on_water');

        $this->assertEquals('You cannot move that way, you are missing the appropriate quest item.', $message);
    }

    public function testGetMessageCannotCraft()
    {
        $message = resolve(ServerMessageBuilder::class)->build('cant_craft');

        $this->assertEquals('You must wait for the timer (beside Craft/Enchant) to state: Ready!', $message);
    }

    public function testGetMessageSomethingsWrong()
    {
        $message = resolve(ServerMessageBuilder::class)->build('something_went_wrong');

        $this->assertEquals('A component was unable to render. Please try refreshing the page.', $message);
    }

    public function testGetMessagesChattingTooMuch()
    {
        $message = resolve(ServerMessageBuilder::class)->build('chatting_to_much');

        $this->assertEquals('You can only chat so much in a one minute window. Slow down!', $message);
    }

    public function testGetMessageMessageTooLong()
    {
        $message = resolve(ServerMessageBuilder::class)->build('message_length_max');

        $this->assertEquals('Your message is far too long.', $message);
    }
}
