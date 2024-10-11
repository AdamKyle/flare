<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
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

    public function setUp(): void
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

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetEnchantingItems()
    {
        $affix = $this->createItemAffix([
            'type' => 'prefix',
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/enchanting/' . $this->character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['affixes']['affixes'][0]['id'], $affix->id);
        $this->assertEquals(0, $jsonData['skill_xp']['current_xp']);
    }

    public function testCannotEnchantWhenCanEnchantIsFalse()
    {
        $this->character->update([
            'can_craft' => false,
        ]);

        $character = $this->character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/enchant/' . $character->id, [
                'slot_id' => 0,
                'affix_ids' => [1],
                'enchant_for_event' => false,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->status());
        $this->assertEquals('You must wait to enchant again.', $jsonData['message']);
    }

    public function testCannotEnchantWhenInventorySlotDoesNotExist()
    {

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/enchant/' . $this->character->id, [
                'slot_id' => 0,
                'affix_ids' => [1],
                'enchant_for_event' => false,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->status());
        $this->assertEquals('Invalid Slot.', $jsonData['message']);
    }

    public function testCannotEnchantQuestItems()
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
            ->call('POST', '/api/enchant/' . $character->id, [
                'slot_id' => $slot->id,
                'affix_ids' => [$enchantment->id],
                'enchant_for_event' => false,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->status());
        $this->assertEquals('You cannot enchant quest items.', $jsonData['message']);
    }

    public function testCannotEnchantItemNotEnoughGold()
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
            ->call('POST', '/api/enchant/' . $character->id, [
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

    public function testEnchantItem()
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
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
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);

        $character = $this->character->refresh();

        $slot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/enchant/' . $character->id, [
                'slot_id' => $slot->id,
                'affix_ids' => [$enchantment->id],
                'enchant_for_event' => false,
            ]);

        $character = $character->refresh();

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['affixes']['affixes'][0]['id'], $enchantment->id);
        $this->assertGreaterThan(0, $jsonData['skill_xp']['current_xp']);
        $this->assertLessThan(MaxCurrenciesValue::MAX_GOLD, $character->gold);
    }
}
