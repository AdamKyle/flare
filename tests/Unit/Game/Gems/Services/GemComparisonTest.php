<?php

namespace Tests\Unit\Game\Gems\Services;

use App\Flare\Models\Gem;
use App\Flare\Models\Item;
use App\Game\Gems\Services\GemComparison;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;

class GemComparisonTest extends TestCase
{
    use CreateGem, CreateItem, RefreshDatabase;

    private ?Item $item;

    private ?Gem $gemToAdd;

    private ?CharacterFactory $characterFactory;

    private ?GemComparison $gemComparisonService;

    public function setUp(): void
    {
        parent::setUp();

        $item = $this->createItem([
            'socket_count' => 2,
        ]);

        $gem = $this->createGem([
            'name' => 'Sample',
            'tier' => 4,
            'primary_atonement_type' => GemTypeValue::FIRE,
            'secondary_atonement_type' => GemTypeValue::ICE,
            'tertiary_atonement_type' => GemTypeValue::WATER,
            'primary_atonement_amount' => 0.10,
            'secondary_atonement_amount' => 0.25,
            'tertiary_atonement_amount' => 0.45,
        ]);

        $this->gemToAdd = $this->createGem([
            'name' => 'Sample',
            'tier' => 4,
            'primary_atonement_type' => GemTypeValue::FIRE,
            'secondary_atonement_type' => GemTypeValue::ICE,
            'tertiary_atonement_type' => GemTypeValue::WATER,
            'primary_atonement_amount' => 0.18,
            'secondary_atonement_amount' => 0.28,
            'tertiary_atonement_amount' => 0.25,
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id' => $gem->id,
        ]);

        $this->item = $item->refresh();

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter();

        $this->gemComparisonService = resolve(GemComparison::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->item = null;

        $this->characterFactory = null;

        $this->gemComparisonService = null;
    }

    public function testReturnsErrorMessageWhenItemDoesNotExist()
    {
        $character = $this->characterFactory->getCharacter();

        $result = $this->gemComparisonService->compareGemForItem($character, rand(1000, 5000), rand(1000, 5000));

        $this->assertEquals($result['message'], 'Selected item was not found in your inventory.');
        $this->assertEquals($result['status'], 422);
    }

    public function testReturnsErrorMessageWhenGemDoesNotExist()
    {
        $character = $this->characterFactory->inventoryManagement()->giveItem($this->item)->getCharacter();

        $result = $this->gemComparisonService->compareGemForItem($character, $character->inventory->slots->first()->id, rand(1000, 5000));

        $this->assertEquals($result['message'], 'Selected gem was not found in your gem bag.');
        $this->assertEquals($result['status'], 422);
    }

    public function testWhenComparingAGemToNoGemsOnItem()
    {
        $item = $this->createItem();
        $gem = $this->createGem();

        $character = $this->characterFactory->inventoryManagement()->giveItem($item)->getCharacter();

        $character->gemBag->gemSlots()->create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $gem->id,
            'amount' => 1,
        ]);

        $character = $character->refresh();

        $result = $this->gemComparisonService->compareGemForItem($character, $character->inventory->slots->first()->id, $character->gemBag->gemSlots->first()->id);

        $this->assertEmpty($result['attached_gems']);
        $this->assertNotEmpty($result['socket_data']);
        $this->assertFalse($result['has_gems_on_item']);
        $this->assertNotEmpty($result['gem_to_attach']);
        $this->assertEmpty($result['when_replacing']);
        $this->assertEmpty($result['if_replaced']);
    }

    public function testWhenComparingGemsToGemsOnAnItem()
    {
        $this->item->sockets()->create([
            'item_id' => $this->item->id,
            'gem_id' => $this->createGem([
                'name' => 'Sample VR',
                'tier' => 4,
                'primary_atonement_type' => GemTypeValue::FIRE,
                'secondary_atonement_type' => GemTypeValue::ICE,
                'tertiary_atonement_type' => GemTypeValue::WATER,
                'primary_atonement_amount' => 0.10,
                'secondary_atonement_amount' => 0.26,
                'tertiary_atonement_amount' => 0.45,
            ])->id,
        ]);

        $item = $this->item->refresh();

        $character = $this->characterFactory->inventoryManagement()->giveItem($item)->getCharacter();

        $character->gemBag->gemSlots()->create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $this->gemToAdd->id,
            'amount' => 1,
        ]);

        $character = $character->refresh();

        $result = $this->gemComparisonService->compareGemForItem($character, $character->inventory->slots->first()->id, $character->gemBag->gemSlots->first()->id);

        $this->assertNotEmpty($result['attached_gems']);
        $this->assertNotEmpty($result['socket_data']);
        $this->assertTrue($result['has_gems_on_item']);
        $this->assertNotEmpty($result['gem_to_attach']);
        $this->assertNotEmpty($result['when_replacing']);
        $this->assertNotEmpty($result['if_replacing_atonements']);
        $this->assertNotEmpty($result['original_atonement']);
    }
}
