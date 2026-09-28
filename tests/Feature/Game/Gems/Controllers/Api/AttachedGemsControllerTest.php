<?php

namespace Tests\Feature\Game\Gems\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Gem;
use App\Flare\Models\Item;
use App\Game\Gems\Values\CharacterGemModifierType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterGemModifier;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;

class AttachedGemsControllerTest extends TestCase
{
    use CreateCharacterGemModifier, CreateGem, CreateItem, RefreshDatabase;

    private ?Character $character = null;

    private ?Item $item = null;

    private ?Gem $gem = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->item = $this->createItem([
            'socket_count' => 2,
        ]);

        $this->gem = $this->createGem(['name' => 'Sample', 'tier' => 4]);
        $this->createCharacterGemModifier([
            'gem_id' => $this->gem->id,
            'roll_position' => 1,
            'modifier_type' => CharacterGemModifierType::FIRE_ATONEMENT,
            'amount' => 0.10,
        ]);

        $this->item->sockets()->create([
            'item_id' => $this->item->id,
            'gem_id' => $this->gem->id,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->inventoryManagement()->giveItem($this->item->refresh())->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_get_gems_from_item()
    {
        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/socketed-gems/'.$this->character->id.'/'.$this->item->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['socketed_gems']);
        $this->assertSame($this->gem->id, $jsonData['socketed_gems'][0]['id']);
        $this->assertSame('fire_atonement', $jsonData['socketed_gems'][0]['modifiers'][0]['modifier_type']);
        $this->assertSame(0.10, $jsonData['socketed_gems'][0]['modifiers'][0]['amount']);
        $this->assertArrayNotHasKey('weak_against', $jsonData['socketed_gems'][0]);
    }
}
