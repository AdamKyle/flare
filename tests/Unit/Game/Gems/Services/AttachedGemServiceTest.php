<?php

namespace Tests\Unit\Game\Gems\Services;

use App\Flare\Models\Item;
use App\Game\Gems\Services\AttachedGemService;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;

class AttachedGemServiceTest extends TestCase
{
    use CreateGem, CreateItem, RefreshDatabase;

    private ?Item $item;

    private ?CharacterFactory $characterFactory;

    private ?AttachedGemService $attachedGemService;

    protected function setUp(): void
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

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id' => $gem->id,
        ]);

        $this->item = $item->refresh();

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter();

        $this->attachedGemService = resolve(AttachedGemService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->item = null;

        $this->characterFactory = null;

        $this->attachedGemService = null;
    }

    public function test_get_error_when_character_does_not_have_item()
    {
        $character = $this->characterFactory->getCharacter();

        $result = $this->attachedGemService->getGemsFromItem($character, $this->item);

        $this->assertEquals($result['message'], 'No item found in your inventory.');
        $this->assertEquals($result['status'], 422);
    }

    public function test_get_gem_data_from_item_when_character_has_item()
    {
        $character = $this->characterFactory->inventoryManagement()->giveItem($this->item)->getCharacter();

        $result = $this->attachedGemService->getGemsFromItem($character, $this->item);

        $this->assertArrayHasKey('socketed_gems', $result);
    }
}
