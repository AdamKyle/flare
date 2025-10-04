<?php

namespace Tests\Feature\Game\Character\CharacterInventory\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;

class CharacterGemBagControllerTest extends TestCase
{
    use CreateGem, RefreshDatabase;

    private ?CharacterFactory $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_get_gem_slot()
    {
        $character = $this->character->gemBagManagement()->assignGemsToBag()->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/gem-bag',
                [
                    'per_page' => 10,
                    'page' => 1,
                    'search_text' => '',
                ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['data']);
    }

    public function test_get_gem()
    {

        $character = $this->character->gemBagManagement()->assignGemsToBag()->getCharacter();
        $gemSlot = $character->gemBag->gemSlots->first();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/gem-details/'.$gemSlot->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($gemSlot->gem->name, $jsonData['gem']['name']);
    }
}
