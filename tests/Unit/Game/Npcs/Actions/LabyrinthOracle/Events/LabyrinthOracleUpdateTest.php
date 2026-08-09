<?php

namespace Tests\Unit\Game\Npcs\Actions\LabyrinthOracle\Events;

use App\Game\Npcs\Actions\LabyrinthOracle\Events\LabyrinthOracleUpdate;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class LabyrinthOracleUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcast_on_returns_private_channel_for_the_character_user(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $event = new LabyrinthOracleUpdate($character);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-update-labyrinth-oracle-'.$character->user->id, $channel->name);
    }
}
