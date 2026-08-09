<?php

namespace Tests\Feature\Game\Shop\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class GemShopControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sell_single_gem_returns_success_and_updates_currencies(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $characterFactory->gemBagManagement()->assignGemsToBag(1, 1);
        $character = $characterFactory->getCharacter();
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
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

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
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $characterFactory->gemBagManagement()->assignGemsToBag(2, 1);
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/character/'.$character->id.'/sell-all-gems');

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertStringStartsWith('You sold the gems for:', $jsonData['message']);
        $this->assertCount(0, $character->gemBag->gemSlots()->get());
    }
}
