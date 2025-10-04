<?php

namespace Tests\Feature\Game\Gems\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Gem;
use App\Flare\Models\Item;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;

class GemComparisonControllerTest extends TestCase
{
    use CreateGem, CreateItem, RefreshDatabase;

    private ?Character $character = null;

    private ?Item $item = null;

    private ?Gem $gem = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->item = $this->createItem([
            'socket_count' => 2,
        ]);

        $this->gem = $this->createGem([
            'name' => 'Sample',
            'tier' => 4,
            'primary_atonement_type' => GemTypeValue::FIRE,
            'secondary_atonement_type' => GemTypeValue::ICE,
            'tertiary_atonement_type' => GemTypeValue::WATER,
            'primary_atonement_amount' => 0.10,
            'secondary_atonement_amount' => 0.25,
            'tertiary_atonement_amount' => 0.45,
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

        $attachedGemExpected = [
            'id' => $this->gem->id,
            'tier' => $this->gem->tier,
            'name' => $this->gem->name,
            'primary_atonement_name' => (new GemTypeValue($this->gem->primary_atonement_type))->getNameOfAtonement(),
            'secondary_atonement_name' => (new GemTypeValue($this->gem->secondary_atonement_type))->getNameOfAtonement(),
            'tertiary_atonement_name' => (new GemTypeValue($this->gem->tertiary_atonement_type))->getNameOfAtonement(),
            'primary_atonement_amount' => $this->gem->primary_atonement_amount,
            'secondary_atonement_amount' => $this->gem->secondary_atonement_amount,
            'tertiary_atonement_amount' => $this->gem->tertiary_atonement_amount,
            'weak_against' => (new GemTypeValue($this->gem->secondary_atonement_type))->getNameOfAtonement(),
            'strong_against' => (new GemTypeValue($this->gem->primary_atonement_type))->getNameOfAtonement(),
            'element_atoned_to' => (new GemTypeValue($this->gem->tertiary_atonement_type))->getNameOfAtonement(),
            'element_atoned_to_amount' => $this->gem->tertiary_atonement_amount,

        ];

        $this->assertCount(1, $jsonData['attached_gems']);
        $this->assertEquals($attachedGemExpected, $jsonData['attached_gems'][0]);
        $this->assertTrue($jsonData['has_gems_on_item']);
    }
}
