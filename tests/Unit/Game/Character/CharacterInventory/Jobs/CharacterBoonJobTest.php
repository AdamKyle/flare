<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Jobs;

use App\Game\Character\CharacterInventory\Jobs\CharacterBoonJob;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class CharacterBoonJobTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        Event::fake();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testNoBoonToRemove()
    {
        CharacterBoonJob::dispatch(6764);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function testRemoveBoon()
    {
        $character = $this->character->getCharacter();

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $this->createItem(['type' => 'alchemy'])->id,
            'started' => now(),
            'complete' => now(),
            'last_for_minutes' => 10,
            'amount_used' => 1,
        ]);

        $character = $character->refresh();

        CharacterBoonJob::dispatch($character->boons->first()->id);

        Event::assertDispatched(ServerMessageEvent::class);

        $character = $character->refresh();

        $this->assertEmpty($character->boons);
    }
}
