<?php

namespace Tests\Feature\Game\Character\CharacterInventory\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;

class CharacterGemBagControllerTest extends TestCase {

    use RefreshDatabase, CreateGem;

    private ?CharacterFactory $character = null;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetGemSlot() {
        $character = $this->character->gemBagManagement()->assignGemsToBag()->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/gem-bag');

        $jsonData = json_decode($response->getContent(), true);


        $this->assertCount(1, $jsonData['gem_slots']);
    }

    public function testGetGem() {

        $character = $this->character->gemBagManagement()->assignGemsToBag()->getCharacter();
        $gemSlot = $character->gemBag->gemSlots->first();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/gem-details/' . $gemSlot->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($gemSlot->gem->name, $jsonData['gem']['name']);
    }
}
