<?php

namespace Tests\Unit\Game\Maps\Events;

use App\Game\Maps\Events\UpdateLocationBasedCraftingOptions;
use App\Game\Maps\Values\LocationBasedCraftingOptions;
use App\Game\Maps\Values\MapName;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class UpdateLocationBasedCraftingOptionsTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

    public function test_event_maps_location_based_crafting_options_onto_broadcast_payload(): void
    {
        $gameMap = $this->createGameMap(['name' => MapName::PURGATORY->value, 'default' => false]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $event = new UpdateLocationBasedCraftingOptions($character->user);

        $locationBasedCraftingOptions = LocationBasedCraftingOptions::fromCharacter($character);

        $this->assertSame($locationBasedCraftingOptions->toBroadcastArray(), [
            'canUseWorkBench' => $event->canUseWorkBench,
            'canUseQueenOfHearts' => $event->canUseQueenOfHearts,
            'canAccessLabyrinthOracle' => $event->canAccessLabyrinthOracle,
            'canAccessSeerCamp' => $event->canAccessSeerCamp,
        ]);
    }

    public function test_event_broadcasts_on_private_channel_for_user(): void
    {
        $gameMap = $this->createGameMap(['name' => MapName::PURGATORY->value, 'default' => false]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $event = new UpdateLocationBasedCraftingOptions($character->user);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-update-location-base-crafting-options-'.$character->user->id, $channel->name);
    }
}
