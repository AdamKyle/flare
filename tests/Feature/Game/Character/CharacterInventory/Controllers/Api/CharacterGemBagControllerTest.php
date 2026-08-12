<?php

namespace Tests\Feature\Game\Character\CharacterInventory\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterGemBagControllerTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_get_gem_slots_returns_paginated_gems(): void
    {
        $character = $this->character->gemBagManagement()->assignGemsToBag()->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/gem-bag');

        $response->assertOk();
        $this->assertArrayHasKey('data', $response->json());
    }

    public function test_get_gem_returns_gem_data_for_an_owned_slot(): void
    {
        $character = $this->character->gemBagManagement()->assignGemsToBag()->getCharacter();
        $gemSlot = $character->gemBag->gemSlots()->first();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/gem-details/'.$gemSlot->id);

        $response->assertOk();
        $this->assertArrayHasKey('gem', $response->json());
    }

    public function test_get_gem_returns_error_for_a_slot_not_owned_by_the_character(): void
    {
        $character = $this->character->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->gemBagManagement()->assignGemsToBag()->getCharacter();
        $otherGemSlot = $otherCharacter->gemBag->gemSlots()->first();

        $response = $this->actingAs($character->user)
            ->getJson('/api/character/'.$character->id.'/gem-details/'.$otherGemSlot->id);

        $response->assertStatus(422);
        $this->assertSame('No. Not yours!', $response->json('message'));
    }
}
