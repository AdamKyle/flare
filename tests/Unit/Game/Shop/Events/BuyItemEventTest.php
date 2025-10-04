<?php

namespace Tests\Unit\Game\Shop\Events;

use App\Game\Shop\Events\BuyItemEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateItem;

class BuyItemEventTest extends TestCase
{
    use CreateClass, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $gameClass = $this->createClass([
            'name' => 'Merchant',
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter([], $gameClass)->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_merchant_should_get_a_discount()
    {
        $character = $this->character->getCharacter();

        $character->update([
            'gold' => 10,
        ]);

        $item = $this->createItem([
            'name' => 'something',
            'type' => 'weapon',
            'cost' => 10,
        ]);

        $character = $character->refresh();

        event(new BuyItemEvent($item, $character));

        $character = $character->refresh();

        $this->assertNotEmpty($character->inventory->slots);
        $this->assertEquals(3, $character->gold);
    }
}
