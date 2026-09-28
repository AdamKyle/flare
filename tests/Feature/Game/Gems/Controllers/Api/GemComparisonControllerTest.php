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

class GemComparisonControllerTest extends TestCase
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
            'modifier_type' => CharacterGemModifierType::FIRE_PENETRATION,
            'amount' => 0.08,
        ]);

        $this->item->sockets()->create([
            'item_id' => $this->item->id,
            'gem_id' => $this->gem->id,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()
            ->gemBagManagement()->assignGemsToBag()->getCharacterFactory()
            ->inventoryManagement()->giveItem($this->item->refresh())->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_compare_gems()
    {
        $slotId = $this->character->inventory->slots->filter(function ($slot) {
            return $slot->item_id === $this->item->id;
        })->first()->id;

        $gemSlotId = $this->character->gemBag->gemSlots->first()->id;

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/gem-comparison/'.$this->character->id, [
                'slot_id' => $slotId,
                'gem_slot_id' => $gemSlotId,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['attached_gems']);
        $this->assertSame($this->gem->id, $jsonData['attached_gems'][0]['id']);
        $this->assertSame('fire_penetration', $jsonData['attached_gems'][0]['modifiers'][0]['modifier_type']);
        $this->assertArrayHasKey('added_gem', $jsonData);
        $this->assertArrayHasKey('replacements', $jsonData);
        $this->assertTrue($jsonData['has_gems_on_item']);
    }

    public function test_scratch_missing_slot_id()
    {
        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/gem-comparison/'.$this->character->id, []);

        $response->assertSessionHasErrors('slot_id');
    }
}
