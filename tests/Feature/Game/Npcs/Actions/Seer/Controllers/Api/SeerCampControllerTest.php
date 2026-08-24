<?php

namespace Tests\Feature\Game\Npcs\Actions\Seer\Controllers\Api;

use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateHolyStack;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateItemSkill;
use Tests\Traits\CreateItemSkillProgression;
use Tests\Traits\CreateItemSocket;

class SeerCampControllerTest extends TestCase
{
    use CreateGem, CreateHolyStack, CreateItem, CreateItemAffix, CreateItemSkill, CreateItemSkillProgression, CreateItemSocket, RefreshDatabase;

    private ?CharacterFactory $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        Model::preventLazyLoading(false);

        parent::tearDown();

        $this->character = null;
    }

    public function test_visiting_seer_camp_returns_the_authoritative_costs()
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/visit-seer-camp/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals([
            'socket' => 2000,
            'attach' => 500,
            'replace' => 10,
            'remove_one' => 10,
        ], $jsonData['costs']);
    }

    public function test_fetching_removal_data_returns_costs_calculated_from_actual_attached_gem_count()
    {
        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);

        $item = $this->createItem([
            'type' => 'weapon',
            'socket_count' => 2,
        ]);

        $firstGem = $this->createGem();
        $secondGem = $this->createGem();

        $this->createItemSocket([
            'item_id' => $item->id,
            'gem_id' => $firstGem->id,
        ]);

        $this->createItemSocket([
            'item_id' => $item->id,
            'gem_id' => $secondGem->id,
        ]);

        $item = $item->refresh();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/seer-camp/gems-to-remove/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(10, $jsonData['gems'][0]['remove_one_cost']);
        $this->assertEquals(20, $jsonData['gems'][0]['remove_all_cost']);
    }

    public function test_paginated_items_endpoint_respects_purpose_filter_and_search()
    {
        $socketableWithoutSockets = $this->createItem([
            'type' => 'weapon',
            'name' => 'Alpha Weapon',
            'socket_count' => 0,
        ]);

        $socketableWithSockets = $this->createItem([
            'type' => 'weapon',
            'name' => 'Beta Weapon',
            'socket_count' => 2,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($socketableWithoutSockets)
            ->giveItem($socketableWithSockets)
            ->getCharacter();

        $socketsResponse = $this->actingAs($character->user)
            ->call('GET', '/api/seer-camp/'.$character->id.'/items', [
                'purpose' => 'sockets',
                'per_page' => 15,
                'page' => 1,
            ]);

        $socketsData = json_decode($socketsResponse->getContent(), true);

        $attachResponse = $this->actingAs($character->user)
            ->call('GET', '/api/seer-camp/'.$character->id.'/items', [
                'purpose' => 'attach',
                'per_page' => 15,
                'page' => 1,
                'search_text' => 'Beta',
            ]);

        $attachData = json_decode($attachResponse->getContent(), true);

        $this->assertEquals(200, $socketsResponse->status());
        $this->assertCount(2, $socketsData['data']);
        $this->assertCount(1, $attachData['data']);
        $this->assertEquals('Beta Weapon', $attachData['data'][0]['name']);
    }

    public function test_paginated_gems_endpoint_respects_per_page_and_search()
    {
        $gemOne = $this->createGem(['name' => 'Alpha Gem']);
        $gemTwo = $this->createGem(['name' => 'Beta Gem']);

        $this->character->gemBagManagement()->assignGemToBag($gemOne->id);
        $character = $this->character->gemBagManagement()->assignGemToBag($gemTwo->id)->getCharacter();

        $firstPage = $this->actingAs($character->user)
            ->call('GET', '/api/seer-camp/'.$character->id.'/gems', [
                'per_page' => 1,
                'page' => 1,
            ]);

        $firstPageData = json_decode($firstPage->getContent(), true);

        $searchResponse = $this->actingAs($character->user)
            ->call('GET', '/api/seer-camp/'.$character->id.'/gems', [
                'per_page' => 15,
                'page' => 1,
                'search_text' => 'Beta',
            ]);

        $searchData = json_decode($searchResponse->getContent(), true);

        $this->assertEquals(200, $firstPage->status());
        $this->assertCount(1, $firstPageData['data']);
        $this->assertTrue($firstPageData['meta']['can_load_more']);
        $this->assertCount(1, $searchData['data']);
        $this->assertEquals('Beta Gem', $searchData['data'][0]['gem']['name']);
    }

    public function test_paginated_items_with_gems_endpoint_returns_only_items_with_attached_gems()
    {
        $itemWithGem = $this->createItem(['type' => 'weapon', 'name' => 'Socketed Weapon', 'socket_count' => 1]);
        $itemWithoutGem = $this->createItem(['type' => 'weapon', 'name' => 'Empty Weapon', 'socket_count' => 1]);

        $this->createItemSocket([
            'item_id' => $itemWithGem->id,
            'gem_id' => $this->createGem()->id,
        ]);

        $itemWithGem = $itemWithGem->refresh();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemWithGem)
            ->giveItem($itemWithoutGem)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/seer-camp/'.$character->id.'/items-with-gems', [
                'per_page' => 15,
                'page' => 1,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->status());
        $this->assertCount(1, $jsonData['data']);
        $this->assertEquals('Socketed Weapon', $jsonData['data'][0]['name']);
    }

    public function test_paginated_items_with_gems_endpoint_respects_per_page_and_search()
    {
        $itemOne = $this->createItem(['type' => 'weapon', 'name' => 'Alpha Socketed', 'socket_count' => 1]);
        $itemTwo = $this->createItem(['type' => 'weapon', 'name' => 'Beta Socketed', 'socket_count' => 1]);

        $this->createItemSocket(['item_id' => $itemOne->id, 'gem_id' => $this->createGem()->id]);
        $this->createItemSocket(['item_id' => $itemTwo->id, 'gem_id' => $this->createGem()->id]);

        $itemOne = $itemOne->refresh();
        $itemTwo = $itemTwo->refresh();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemOne)
            ->giveItem($itemTwo)
            ->getCharacter();

        $firstPage = $this->actingAs($character->user)
            ->call('GET', '/api/seer-camp/'.$character->id.'/items-with-gems', [
                'per_page' => 1,
                'page' => 1,
            ]);

        $firstPageData = json_decode($firstPage->getContent(), true);

        $searchResponse = $this->actingAs($character->user)
            ->call('GET', '/api/seer-camp/'.$character->id.'/items-with-gems', [
                'per_page' => 15,
                'page' => 1,
                'search_text' => 'Beta',
            ]);

        $searchData = json_decode($searchResponse->getContent(), true);

        $this->assertEquals(200, $firstPage->status());
        $this->assertCount(1, $firstPageData['data']);
        $this->assertTrue($firstPageData['meta']['can_load_more']);
        $this->assertCount(1, $searchData['data']);
        $this->assertEquals('Beta Socketed', $searchData['data'][0]['name']);
    }

    public function test_paginated_items_with_gems_endpoint_only_returns_requested_characters_items()
    {
        $ownItem = $this->createItem(['type' => 'weapon', 'name' => 'Own Socketed', 'socket_count' => 1]);
        $this->createItemSocket(['item_id' => $ownItem->id, 'gem_id' => $this->createGem()->id]);
        $ownItem = $ownItem->refresh();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($ownItem)
            ->getCharacter();

        $foreignItem = $this->createItem(['type' => 'weapon', 'name' => 'Foreign Socketed', 'socket_count' => 1]);
        $this->createItemSocket(['item_id' => $foreignItem->id, 'gem_id' => $this->createGem()->id]);
        $foreignItem = $foreignItem->refresh();

        (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($foreignItem);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/seer-camp/'.$character->id.'/items-with-gems', [
                'per_page' => 15,
                'page' => 1,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['data']);
        $this->assertEquals('Own Socketed', $jsonData['data'][0]['name']);
    }

    public function test_one_successful_mutation_response_retains_items_gems_costs_and_message()
    {
        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock) {
                $mock->shouldReceive('numberBetween')->with(1, 100)->once()->andReturn(50);
            })
        );

        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);

        $item = $this->createItem([
            'type' => 'weapon',
            'socket_count' => 0,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/seer-camp/add-sockets/'.$character->id, [
                'slot_id' => $slot->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->status());
        $this->assertArrayHasKey('items', $jsonData);
        $this->assertArrayHasKey('gems', $jsonData);
        $this->assertArrayHasKey('costs', $jsonData);
        $this->assertEquals('Attached sockets to item! (Old Socket Count: 0, New Count: 2).', $jsonData['message']);
        $this->assertNotNull($jsonData['result_preview']);
        $this->assertSame(2, $jsonData['result_preview']['socket_count']);
    }

    public function test_expected_validation_failure_does_not_change_gold_bars_or_item_state()
    {
        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);

        $item = $this->createItem([
            'type' => 'weapon',
            'socket_count' => 0,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $goldBarsBefore = $character->kingdoms->sum('gold_bars');

        $response = $this->actingAs($character->user)
            ->json('POST', '/api/seer-camp/add-sockets/'.$character->id, []);

        $response->assertStatus(422);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Error. Invalid Input.', $jsonData['errors']['slot_id'][0]);
        $this->assertEquals($goldBarsBefore, $character->refresh()->kingdoms->sum('gold_bars'));
        $this->assertEquals(0, $item->refresh()->socket_count);
    }

    public function test_paginated_items_endpoint_with_populated_rows_does_not_lazy_load()
    {
        $itemSkill = $this->createItemSkill([
            'name' => 'Weapon Mastery',
            'description' => 'Increases weapon proficiency.',
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $decoratedItem = $this->createItem([
            'type' => 'weapon',
            'name' => 'Decorated Weapon',
            'socket_count' => 2,
            'item_prefix_id' => $this->createItemAffix(['type' => 'prefix'])->id,
            'item_suffix_id' => $this->createItemAffix(['type' => 'suffix'])->id,
            'holy_stacks' => 5,
        ]);

        $this->createItemSocket(['item_id' => $decoratedItem->id, 'gem_id' => $this->createGem()->id]);
        $this->createItemSocket(['item_id' => $decoratedItem->id, 'gem_id' => $this->createGem()->id]);

        $this->createHolyStack([
            'item_id' => $decoratedItem->id,
            'devouring_darkness_bonus' => 0.1,
            'stat_increase_bonus' => 0.1,
        ]);

        $this->createItemSkillProgression([
            'item_id' => $decoratedItem->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 1,
            'current_kill' => 10,
            'is_training' => false,
        ]);

        $decoratedItem = $decoratedItem->refresh();
        $decoratedItemName = $decoratedItem->affix_name;

        $plainItem = $this->createItem(['type' => 'weapon', 'name' => 'Plain Weapon', 'socket_count' => 0]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($decoratedItem)
            ->giveItem($plainItem)
            ->getCharacter();

        Model::preventLazyLoading();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/seer-camp/'.$character->id.'/items', [
                'purpose' => 'sockets',
                'per_page' => 15,
                'page' => 1,
            ]);

        $response->assertOk();

        $data = json_decode($response->getContent(), true);

        $this->assertCount(2, $data['data']);

        $decoratedRow = collect($data['data'])->firstWhere('name', $decoratedItemName);

        $this->assertNotNull($decoratedRow);
        $this->assertSame(2, $decoratedRow['current_sockets']);
        $this->assertArrayHasKey('preview', $decoratedRow);
    }

    public function test_roll_sockets_endpoint_attaches_sockets_to_item(): void
    {
        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);

        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 0]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/seer-camp/add-sockets/'.$character->id, ['slot_id' => $slot->id]);

        $response->assertOk();
    }

    public function test_attach_gem_to_item_endpoint_attaches_the_gem(): void
    {
        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);

        $gem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $character = $this->character->gemBagManagement()->assignGemToBag($gem->id)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $gemSlot = $character->gemBag->gemSlots->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/seer-camp/add-gem/'.$character->id, [
                'slot_id' => $slot->id,
                'gem_slot_id' => $gemSlot->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame('Attached gem to item!', $jsonData['message']);
    }

    public function test_replace_gem_on_item_endpoint_replaces_the_gem(): void
    {
        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);

        $existingGem = $this->createGem();
        $replacementGem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);
        $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $existingGem->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $character = $this->character->gemBagManagement()->assignGemToBag($replacementGem->id)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();
        $gemSlot = $character->gemBag->gemSlots->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/seer-camp/replace-gem/'.$character->id, [
                'slot_id' => $slot->id,
                'gem_slot_id' => $gemSlot->id,
                'gem_slot_to_replace' => $existingGem->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame('Gem has been replaced!', $jsonData['message']);
    }

    public function test_remove_gem_from_item_endpoint_removes_the_gem(): void
    {
        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);

        $gem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 1]);
        $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $gem->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/seer-camp/remove-gem/'.$character->id, [
                'slot_id' => $slot->id,
                'gem_id' => $gem->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame('Gem has been removed from the socket!', $jsonData['message']);
    }

    public function test_remove_all_gems_from_item_endpoint_removes_every_gem(): void
    {
        $this->character->kingdomManagement()->assignKingdom(['gold_bars' => 5000]);

        $firstGem = $this->createGem();
        $secondGem = $this->createGem();
        $item = $this->createItem(['type' => 'weapon', 'socket_count' => 2]);
        $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $firstGem->id]);
        $this->createItemSocket(['item_id' => $item->id, 'gem_id' => $secondGem->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/seer-camp/remove-all-gems/'.$character->id.'/'.$slot->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertSame('All gems have been removed!', $jsonData['message']);
    }
}
