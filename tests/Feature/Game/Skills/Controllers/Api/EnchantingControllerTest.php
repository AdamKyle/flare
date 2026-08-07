<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\ItemSkill;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class EnchantingControllerTest extends TestCase
{
    use CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $craftingSkill = $this->createGameSkill([
            'name' => 'Enchanting',
            'type' => SkillTypeValue::ENCHANTING,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->assignSkill(
                $craftingSkill,
                10
            )
            ->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_get_enchanting_items()
    {
        $affix = $this->createItemAffix([
            'type' => 'prefix',
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/enchanting/'.$this->character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['affixes']['affixes'][0]['id'], $affix->id);
        $this->assertEquals(0, $jsonData['skill_xp']['current_xp']);
    }

    public function test_paginated_items_endpoint_respects_per_page_search_and_source_filter()
    {
        $itemOne = $this->createItem(['type' => 'body', 'name' => 'Alpha Chestplate']);
        $itemTwo = $this->createItem(['type' => 'body', 'name' => 'Beta Chestplate']);
        $itemThree = $this->createItem(['type' => 'body', 'name' => 'Gamma Chestplate']);

        foreach ([$itemOne, $itemTwo, $itemThree] as $item) {
            $this->character->inventory->slots()->create([
                'inventory_id' => $this->character->inventory->id,
                'item_id' => $item->id,
            ]);
        }

        $firstPage = $this->actingAs($this->character->user)
            ->call('GET', '/api/enchanting/'.$this->character->id.'/items', [
                'source' => 'regular',
                'per_page' => 2,
                'page' => 1,
            ]);

        $firstPageData = json_decode($firstPage->getContent(), true);

        $searchResponse = $this->actingAs($this->character->user)
            ->call('GET', '/api/enchanting/'.$this->character->id.'/items', [
                'source' => 'regular',
                'per_page' => 15,
                'page' => 1,
                'search_text' => 'Beta',
            ]);

        $searchData = json_decode($searchResponse->getContent(), true);

        $this->assertEquals(200, $firstPage->status());
        $this->assertCount(2, $firstPageData['data']);
        $this->assertTrue($firstPageData['meta']['can_load_more']);
        $this->assertCount(1, $searchData['data']);
        $this->assertEquals($itemTwo->id, $searchData['data'][0]['item_id']);
        $this->assertArrayHasKey('preview', $searchData['data'][0]);
        $this->assertEquals($itemTwo->id, $searchData['data'][0]['preview']['item_id']);
    }

    public function test_paginated_items_endpoint_excludes_ineligible_item_types_and_keeps_enchanted_equipment()
    {
        $prefix = $this->createItemAffix(['type' => 'prefix']);

        $equipment = $this->createItem(['type' => 'body']);
        $enchantedEquipment = $this->createItem(['type' => 'body', 'item_prefix_id' => $prefix->id]);
        $questItem = $this->createItem(['type' => 'quest']);
        $alchemyItem = $this->createItem(['type' => 'alchemy']);
        $gemItem = $this->createItem(['type' => 'gem']);
        $trinketItem = $this->createItem(['type' => 'trinket']);
        $artifactItem = $this->createItem(['type' => 'artifact']);

        foreach ([$equipment, $enchantedEquipment, $questItem, $alchemyItem, $gemItem, $trinketItem, $artifactItem] as $item) {
            $this->character->inventory->slots()->create([
                'inventory_id' => $this->character->inventory->id,
                'item_id' => $item->id,
            ]);
        }

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/enchanting/'.$this->character->id.'/items', [
                'source' => 'regular',
                'per_page' => 15,
                'page' => 1,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $returnedItemIds = collect($jsonData['data'])->pluck('item_id')->all();

        $this->assertEquals(200, $response->status());
        $this->assertContains($equipment->id, $returnedItemIds);
        $this->assertContains($enchantedEquipment->id, $returnedItemIds);
        $this->assertNotContains($questItem->id, $returnedItemIds);
        $this->assertNotContains($alchemyItem->id, $returnedItemIds);
        $this->assertNotContains($gemItem->id, $returnedItemIds);
        $this->assertNotContains($trinketItem->id, $returnedItemIds);
        $this->assertNotContains($artifactItem->id, $returnedItemIds);
    }

    public function test_paginated_affixes_endpoint_respects_type_filter_and_per_page()
    {
        $this->createItemAffix(['type' => 'prefix', 'skill_level_required' => 1, 'skill_level_trivial' => 25]);
        $this->createItemAffix(['type' => 'prefix', 'skill_level_required' => 1, 'skill_level_trivial' => 25]);
        $this->createItemAffix(['type' => 'suffix', 'skill_level_required' => 1, 'skill_level_trivial' => 25]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/enchanting/'.$this->character->id.'/affixes', [
                'type' => 'prefix',
                'per_page' => 15,
                'page' => 1,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->status());
        $this->assertCount(2, $jsonData['data']);
        $this->assertSame('prefix', $jsonData['data'][0]['type']);
        $this->assertFalse($jsonData['meta']['can_load_more']);
    }

    public function test_cannot_enchant_when_can_enchant_is_false()
    {
        $this->character->update([
            'can_craft' => false,
        ]);

        $character = $this->character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/enchant/'.$character->id, [
                'slot_id' => 0,
                'affix_ids' => [1],
                'enchant_for_event' => false,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->status());
        $this->assertEquals('You must wait to enchant again.', $jsonData['message']);
    }

    public function test_cannot_enchant_when_inventory_slot_does_not_exist()
    {

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/enchant/'.$this->character->id, [
                'slot_id' => 0,
                'affix_ids' => [1],
                'enchant_for_event' => false,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->status());
        $this->assertEquals('Invalid Slot.', $jsonData['message']);
    }

    public function test_cannot_enchant_quest_items()
    {

        $item = $this->createItem([
            'type' => 'quest',
        ]);

        $enchantment = $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
        ]);

        $character = $this->character->refresh();

        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/enchant/'.$character->id, [
                'slot_id' => $slot->id,
                'affix_ids' => [$enchantment->id],
                'enchant_for_event' => false,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->status());
        $this->assertEquals('You cannot enchant quest items.', $jsonData['message']);
    }

    public function test_cannot_enchant_item_not_enough_gold()
    {
        Event::fake();

        $item = $this->createItem([
            'type' => 'body',
        ]);

        $enchantment = $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
        ]);

        $character = $this->character->refresh();

        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/enchant/'.$character->id, [
                'slot_id' => $slot->id,
                'affix_ids' => [$enchantment->id],
                'enchant_for_event' => false,
            ]);

        Event::assertDispatched(ServerMessageEvent::class, function ($event) {
            return $event->message === 'Not enough gold to enchant that.';
        });

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['affixes']['affixes'][0]['id'], $enchantment->id);
        $this->assertEquals(0, $jsonData['skill_xp']['current_xp']);
    }

    public function test_enchant_item()
    {
        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock) {
                $mock->shouldReceive('numberBetween')->with(1, 400)->twice()->andReturn(1, 400);
            })
        );

        $item = $this->createItem([
            'type' => 'body',
        ]);

        $enchantment = $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
        ]);

        $this->character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $character = $this->character->refresh();

        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/enchant/'.$character->id, [
                'slot_id' => $slot->id,
                'affix_ids' => [$enchantment->id],
                'enchant_for_event' => false,
            ]);

        $character = $character->refresh();

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['affixes']['affixes'][0]['id'], $enchantment->id);
        $this->assertGreaterThan(0, $jsonData['skill_xp']['current_xp']);
        $this->assertLessThan(CurrencyLimit::MAX_GOLD, $character->gold);
        $this->assertTrue($jsonData['enchant_succeeded']);
        $this->assertNotNull($jsonData['result_preview']);
        $this->assertIsInt($jsonData['result_preview']['inventory_slot_id']);
    }

    public function test_enchant_item_fails_the_roll_and_still_returns_a_successful_enchanting_response()
    {
        Event::fake();

        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock) {
                $mock->shouldReceive('numberBetween')->with(1, 400)->twice()->andReturn(400, 1);
            })
        );

        $item = $this->createItem([
            'type' => 'body',
        ]);

        $enchantment = $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
        ]);

        $this->character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
        ]);

        $character = $this->character->refresh();

        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $expectedCost = resolve(EnchantingService::class)->getCostOfEnchantment($character, [$enchantment->id], $item->id);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/enchant/'.$character->id, [
                'slot_id' => $slot->id,
                'affix_ids' => [$enchantment->id],
                'enchant_for_event' => false,
            ]);

        $character = $character->refresh();

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->status());
        $this->assertFalse($jsonData['enchant_succeeded']);
        $this->assertEquals(0, $jsonData['skill_xp']['current_xp']);
        $this->assertEquals(CurrencyLimit::MAX_GOLD - $expectedCost, $character->gold);
        $this->assertEquals(0, InventorySlot::where('id', $slot->id)->count());
        $this->assertNull($jsonData['result_preview']);

        Event::assertDispatched(ServerMessageEvent::class, function ($event) {
            return str_contains($event->message, 'shatters before you');
        });
    }

    public function test_paginated_items_endpoint_with_populated_rows_does_not_lazy_load(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $suffix = $this->createItemAffix(['type' => 'suffix']);

        $itemSkill = ItemSkill::create([
            'name' => 'Chestplate Mastery',
            'description' => 'Increases chestplate proficiency.',
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $decoratedItem = $this->createItem([
            'type' => 'body',
            'name' => 'Decorated Chestplate',
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
            'holy_stacks' => 5,
        ]);

        $decoratedItem->appliedHolyStacks()->create([
            'item_id' => $decoratedItem->id,
            'devouring_darkness_bonus' => 0.1,
            'stat_increase_bonus' => 0.1,
        ]);

        $decoratedItem->itemSkillProgressions()->create([
            'item_id' => $decoratedItem->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 1,
            'current_kill' => 10,
            'is_training' => false,
        ]);

        $plainItem = $this->createItem(['type' => 'body', 'name' => 'Plain Chestplate']);

        foreach ([$decoratedItem, $plainItem] as $item) {
            $this->character->inventory->slots()->create([
                'inventory_id' => $this->character->inventory->id,
                'item_id' => $item->id,
            ]);
        }

        Model::preventLazyLoading();

        try {
            $response = $this->actingAs($this->character->user)
                ->call('GET', '/api/enchanting/'.$this->character->id.'/items', [
                    'source' => 'regular',
                    'per_page' => 15,
                    'page' => 1,
                ]);

            $response->assertOk();

            $data = json_decode($response->getContent(), true);

            $this->assertCount(2, $data['data']);
            $this->assertArrayHasKey('preview', $data['data'][0]);
        } finally {
            Model::preventLazyLoading(false);
        }
    }
}
