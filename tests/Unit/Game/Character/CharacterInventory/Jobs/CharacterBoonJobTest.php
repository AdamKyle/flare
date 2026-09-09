<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Jobs;

use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Character\CharacterInventory\Events\CharacterBoonsUpdateBroadcastEvent;
use App\Game\Character\CharacterInventory\Jobs\CharacterBoonJob;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateItem;

class CharacterBoonJobTest extends TestCase
{
    use CreateCharacterBoon, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        Event::fake();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_no_boon_to_remove()
    {
        CharacterBoonJob::dispatch(6764);

        Event::assertNotDispatched(ServerMessageEvent::class);
    }

    public function test_remove_boon()
    {
        Queue::fake([CharacterAttackTypesCacheBuilder::class]);

        $character = $this->character->getCharacter();

        $this->createCharacterBoon([
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
        Event::assertDispatched(CharacterBoonsUpdateBroadcastEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);
        Event::assertNotDispatched(UpdateBaseCharacterInformation::class);
        Queue::assertPushed(CharacterAttackTypesCacheBuilder::class, 1);

        $character = $character->refresh();

        $this->assertEmpty($character->boons);
    }
}
