<?php

namespace Tests\Feature\Game\Shop\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class GemShopControllerTest extends TestCase
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

    public function test_sell_single_gem_returns_success_and_updates_currencies(): void
    {
        $this->character->gemBagManagement()->assignGemsToBag(1, 1);
        $character = $this->character->getCharacter();
        $gemSlot = $character->gemBag->gemSlots->first();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/sell-gem/'.$gemSlot->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertStringStartsWith('You sold the gem for:', $jsonData['message']);
        $this->assertNull($character->gemBag->gemSlots()->find($gemSlot->id));
    }

    public function test_sell_single_gem_returns_error_when_gem_does_not_belong_to_the_character(): void
    {
        $character = $this->character->getCharacter();

        $otherCharacterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $otherCharacterFactory->gemBagManagement()->assignGemsToBag(1, 1);
        $otherGemSlot = $otherCharacterFactory->getCharacter()->gemBag->gemSlots->first();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/sell-gem/'.$otherGemSlot->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertStatus(422);
        $this->assertSame('Gem not found. Nothing to sell.', $jsonData['message']);
    }

    public function test_sell_all_gems_returns_success_and_clears_gem_bag(): void
    {
        $this->character->gemBagManagement()->assignGemsToBag(2, 1);
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/sell-all-gems');

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertStringStartsWith('You sold the gems for:', $jsonData['message']);
        $this->assertCount(0, $character->gemBag->gemSlots()->get());
    }
}
