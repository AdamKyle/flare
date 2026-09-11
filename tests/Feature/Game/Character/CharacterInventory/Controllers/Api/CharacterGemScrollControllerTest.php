<?php

namespace Tests\Feature\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\CharacterGameMapGemScroll;
use App\Game\Gems\Progression\Values\GemScrollCurrencyType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\GemProgression\GemWorldRewardTestFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class CharacterGemScrollControllerTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private GemWorldRewardTestFactory $gemWorldRewardTestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gemWorldRewardTestFactory = new GemWorldRewardTestFactory;
    }

    public function test_cannot_use_a_scroll_outside_a_generated_gem_world(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $scrollItem = $this->createGemXpScrollItem(0.10);
        $slot = $character->alchemyBag->slots()->create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $scrollItem->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/gem-scrolls/use/'.$slot->id);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertDatabaseHas('alchemy_bag_slots', ['id' => $slot->id]);
    }

    public function test_cannot_use_another_characters_alchemy_bag_slot(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $scrollItem = $this->createGemXpScrollItem(0.10);
        $slot = $otherCharacter->alchemyBag->slots()->create([
            'alchemy_bag_id' => $otherCharacter->alchemyBag->id,
            'character_id' => $otherCharacter->id,
            'item_id' => $scrollItem->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($graph->character->user)
            ->call('POST', '/api/character/'.$graph->character->id.'/gem-scrolls/use/'.$slot->id);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_using_a_scroll_in_a_map_gem_world_binds_the_map_profile(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();
        $scrollItem = $this->createGemXpScrollItem(0.10, 120);
        $slot = $graph->character->alchemyBag->slots()->create([
            'alchemy_bag_id' => $graph->character->alchemyBag->id,
            'character_id' => $graph->character->id,
            'item_id' => $scrollItem->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($graph->character->user)
            ->call('POST', '/api/character/'.$graph->character->id.'/gem-scrolls/use/'.$slot->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('character_game_map_gem_scrolls', [
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'item_id' => $scrollItem->id,
        ]);
        $this->assertDatabaseMissing('alchemy_bag_slots', ['id' => $slot->id]);
    }

    public function test_using_a_scroll_in_a_location_gem_world_binds_the_location_profile(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedLocationGemWorldCharacter();
        $scrollItem = $this->createGemXpScrollItem(0.10, 120);
        $slot = $graph->character->alchemyBag->slots()->create([
            'alchemy_bag_id' => $graph->character->alchemyBag->id,
            'character_id' => $graph->character->id,
            'item_id' => $scrollItem->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($graph->character->user)
            ->call('POST', '/api/character/'.$graph->character->id.'/gem-scrolls/use/'.$slot->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('character_game_location_gem_scrolls', [
            'character_id' => $graph->character->id,
            'game_location_gem_paramter_id' => $graph->locationProfile->id,
            'item_id' => $scrollItem->id,
        ]);
    }

    public function test_activation_rejection_leaves_the_alchemy_bag_slot_untouched(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $firstScroll = $this->createGemXpScrollItem(20.0, 120);
        $graph->character->alchemyBag->slots()->create([
            'alchemy_bag_id' => $graph->character->alchemyBag->id,
            'character_id' => $graph->character->id,
            'item_id' => $firstScroll->id,
            'amount' => 1,
        ]);

        $this->actingAs($graph->character->user)
            ->call('POST', '/api/character/'.$graph->character->id.'/gem-scrolls/use/'.
                AlchemyBagSlot::where('item_id', $firstScroll->id)->first()->id);

        $secondScroll = $this->createGemXpScrollItem(1.0, 120);
        $secondSlot = $graph->character->alchemyBag->slots()->create([
            'alchemy_bag_id' => $graph->character->alchemyBag->id,
            'character_id' => $graph->character->id,
            'item_id' => $secondScroll->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($graph->character->user)
            ->call('POST', '/api/character/'.$graph->character->id.'/gem-scrolls/use/'.$secondSlot->id);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertDatabaseHas('alchemy_bag_slots', ['id' => $secondSlot->id]);
    }

    public function test_fill_up_extends_the_active_scroll_by_the_exact_fill_duration(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $activeItem = $this->createGemXpScrollItem(0.10, 120);
        $activeScroll = CharacterGameMapGemScroll::create([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'item_id' => $activeItem->id,
            'started_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);
        $expiresBefore = $activeScroll->expires_at;

        $fillItem = $this->createGemXpScrollItem(0.15, 90);
        $fillSlot = $graph->character->alchemyBag->slots()->create([
            'alchemy_bag_id' => $graph->character->alchemyBag->id,
            'character_id' => $graph->character->id,
            'item_id' => $fillItem->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($graph->character->user)
            ->call('POST', '/api/character/'.$graph->character->id.'/gem-scrolls/map/'.$activeScroll->id.'/fill/'.$fillSlot->id);

        $this->assertSame(200, $response->getStatusCode());

        $activeScroll = $activeScroll->fresh();
        $this->assertEqualsWithDelta($expiresBefore->addMinutes(90)->timestamp, $activeScroll->expires_at->timestamp, 2);
        $this->assertEqualsWithDelta(0.10, $activeScroll->item->gem_scroll_bonus, 0.0000001);
        $this->assertDatabaseMissing('alchemy_bag_slots', ['id' => $fillSlot->id]);
        $this->assertTrue($activeScroll->expires_at->gt(now()->addHours(8)));
    }

    public function test_currency_fill_up_requires_the_same_target_currency(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $activeItem = $this->createGemCurrencyScrollItem(0.10, GemScrollCurrencyType::GOLD, 120);
        $activeScroll = CharacterGameMapGemScroll::create([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'item_id' => $activeItem->id,
            'started_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);

        $mismatchedFillItem = $this->createGemCurrencyScrollItem(0.10, GemScrollCurrencyType::SHARDS, 90);
        $fillSlot = $graph->character->alchemyBag->slots()->create([
            'alchemy_bag_id' => $graph->character->alchemyBag->id,
            'character_id' => $graph->character->id,
            'item_id' => $mismatchedFillItem->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($graph->character->user)
            ->call('POST', '/api/character/'.$graph->character->id.'/gem-scrolls/map/'.$activeScroll->id.'/fill/'.$fillSlot->id);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertDatabaseHas('alchemy_bag_slots', ['id' => $fillSlot->id]);
    }

    public function test_fill_up_rejects_an_already_expired_active_scroll(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $activeItem = $this->createGemXpScrollItem(0.10, 120);
        $activeScroll = CharacterGameMapGemScroll::create([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'item_id' => $activeItem->id,
            'started_at' => now()->subHours(5),
            'expires_at' => now()->subHour(),
        ]);

        $fillItem = $this->createGemXpScrollItem(0.15, 90);
        $fillSlot = $graph->character->alchemyBag->slots()->create([
            'alchemy_bag_id' => $graph->character->alchemyBag->id,
            'character_id' => $graph->character->id,
            'item_id' => $fillItem->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($graph->character->user)
            ->call('POST', '/api/character/'.$graph->character->id.'/gem-scrolls/map/'.$activeScroll->id.'/fill/'.$fillSlot->id);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertDatabaseHas('alchemy_bag_slots', ['id' => $fillSlot->id]);
    }

    public function test_remove_deletes_the_active_scroll_and_does_not_refund_the_item(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $activeItem = $this->createGemXpScrollItem(0.10, 120);
        $activeScroll = CharacterGameMapGemScroll::create([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'item_id' => $activeItem->id,
            'started_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);

        $response = $this->actingAs($graph->character->user)
            ->call('POST', '/api/character/'.$graph->character->id.'/gem-scrolls/map/'.$activeScroll->id.'/remove');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseMissing('character_game_map_gem_scrolls', ['id' => $activeScroll->id]);
        $this->assertDatabaseMissing('alchemy_bag_slots', ['item_id' => $activeItem->id]);
    }
}
