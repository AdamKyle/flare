<?php

namespace Tests\Unit\Game\Shop\Events;

use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Shop\Events\BuyItemEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class BuyItemEventTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_completed_purchase_broadcasts_the_updated_currencies_and_inventory_count(): void
    {
        Event::fake([UpdateCharacterCurrenciesEvent::class, UpdateCharacterInventoryCountEvent::class]);

        $character = $this->character->getCharacter();
        $item = $this->createItem(['type' => 'weapon', 'cost' => 10]);

        event(new BuyItemEvent($item, $character));

        Event::assertDispatched(UpdateCharacterCurrenciesEvent::class);
        Event::assertDispatched(UpdateCharacterInventoryCountEvent::class);
    }

    public function test_handling_a_completed_purchase_does_not_charge_gold_or_add_items(): void
    {
        $character = $this->character->getCharacter();
        $character->update(['gold' => 10]);

        $item = $this->createItem(['type' => 'weapon', 'cost' => 10]);

        event(new BuyItemEvent($item, $character->refresh()));

        $character = $character->refresh();

        $this->assertSame(10, $character->gold);
        $this->assertNull($character->inventory->slots->firstWhere('item_id', $item->id));
    }
}
