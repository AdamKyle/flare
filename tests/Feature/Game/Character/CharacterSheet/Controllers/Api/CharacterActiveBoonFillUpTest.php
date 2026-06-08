<?php

namespace Tests\Feature\Game\Character\CharacterSheet\Controllers\Api;

use App\Flare\Models\Item;
use App\Flare\Values\AutomationType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateItem;

class CharacterActiveBoonFillUpTest extends TestCase
{
    use CreateCharacterAutomation;
    use CreateCharacterBoon;
    use CreateItem;
    use RefreshDatabase;

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testActiveBoonRowsIncludeAmountLeft(): void
    {
        $item = $this->createBoonItem();

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->giveItem($item)
            ->getCharacter();

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(30),
            'amount_used' => 1,
            'last_for_minutes' => 30,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character-sheet/'.$character->id.'/active-boons');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(2, $jsonData['active_boons'][0]['amount_left']);
    }

    public function testFillUpBoonConsumesItemAndExtendsBoon(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        Queue::fake();

        $item = $this->createBoonItem();

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(30),
            'amount_used' => 2,
            'last_for_minutes' => 30,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('test filled up using 1 item(s), adding 30 minutes.', $jsonData['message']);
        $this->assertEquals('2026-01-01 13:00:00', $boon->refresh()->complete->toDateTimeString());
        $this->assertEquals(60, $boon->last_for_minutes);
        $this->assertEquals(0, $character->refresh()->inventory->slots()->where('item_id', $item->id)->count());
        $this->assertEquals(0, $jsonData['boons'][0]['amount_left']);
    }

    public function testFillUpBoonWithoutItemReturnsUnprocessable(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        Queue::fake();

        $item = $this->createBoonItem();

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->getCharacter();

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(30),
            'amount_used' => 1,
            'last_for_minutes' => 30,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You do not have any more of that item.', $jsonData['message']);
        $this->assertEquals('2026-01-01 12:30:00', $boon->refresh()->complete->toDateTimeString());
    }

    /**
     * @dataProvider activeAutomationTypes
     */
    public function testFillUpBoonSucceedsDuringActiveAutomation(int $automationType): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        Queue::fake();

        $item = $this->createBoonItem();

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(30),
            'amount_used' => 2,
            'last_for_minutes' => 30,
        ]);

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => $automationType,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('2026-01-01 13:00:00', $boon->refresh()->complete->toDateTimeString());
        $this->assertEquals(60, $boon->last_for_minutes);
        $this->assertEquals(0, $character->refresh()->inventory->slots()->where('item_id', $item->id)->count());
    }

    public static function activeAutomationTypes(): array
    {
        return [
            'exploration' => [AutomationType::EXPLORING],
            'delve' => [AutomationType::DELVE],
            'faction loyalty' => [AutomationType::FACTION_LOYALTY],
        ];
    }

    public function testFillUpBoonUsesTwoItemsWhenTwoAvailable(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        Queue::fake();

        $item = $this->createTwoHourBoonItem();

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->giveItem($item)
            ->getCharacter();

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(60),
            'amount_used' => 3,
            'last_for_minutes' => 60,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('test filled up using 2 item(s), adding 240 minutes.', $jsonData['message']);
        $this->assertEquals('2026-01-01 17:00:00', $boon->refresh()->complete->toDateTimeString());
        $this->assertEquals(300, $boon->last_for_minutes);
        $this->assertEquals(0, $character->refresh()->inventory->slots()->where('item_id', $item->id)->count());
    }

    public function testFillUpBoonUsesFourItemsWhenEightAvailable(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        Queue::fake();

        $item = $this->createTwoHourBoonItem();

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->giveItem($item)
            ->giveItem($item)
            ->giveItem($item)
            ->giveItem($item)
            ->giveItem($item)
            ->giveItem($item)
            ->giveItem($item)
            ->getCharacter();

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(60),
            'amount_used' => 4,
            'last_for_minutes' => 60,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('test filled up using 4 item(s), adding 420 minutes.', $jsonData['message']);
        $this->assertEquals('2026-01-01 20:00:00', $boon->refresh()->complete->toDateTimeString());
        $this->assertEquals(480, $boon->last_for_minutes);
        $this->assertEquals(4, $character->refresh()->inventory->slots()->where('item_id', $item->id)->count());
    }

    public function testFillUpBoonUsesFourItemsWhenFourAvailable(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        Queue::fake();

        $item = $this->createTwoHourBoonItem();

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->giveItem($item)
            ->giveItem($item)
            ->giveItem($item)
            ->getCharacter();

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(60),
            'amount_used' => 4,
            'last_for_minutes' => 60,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('test filled up using 4 item(s), adding 420 minutes.', $jsonData['message']);
        $this->assertEquals('2026-01-01 20:00:00', $boon->refresh()->complete->toDateTimeString());
        $this->assertEquals(480, $boon->last_for_minutes);
        $this->assertEquals(0, $character->refresh()->inventory->slots()->where('item_id', $item->id)->count());
    }

    public function testFillUpBoonCapsAmountUsedAtMaxStackCount(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        Queue::fake();

        $item = $this->createTwoHourBoonItem();

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->giveItem($item)
            ->getCharacter();

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(360),
            'amount_used' => 4,
            'last_for_minutes' => 360,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(4, $boon->refresh()->amount_used);
        $this->assertEquals('2026-01-01 20:00:00', $boon->complete->toDateTimeString());
        $this->assertEquals(480, $boon->last_for_minutes);
        $this->assertEquals(1, $character->refresh()->inventory->slots()->where('item_id', $item->id)->count());

        Carbon::setTestNow(Carbon::parse('2026-01-01 12:30:00'));

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(4, $boon->refresh()->amount_used);
        $this->assertEquals('2026-01-01 20:30:00', $boon->complete->toDateTimeString());
        $this->assertEquals(480, $boon->last_for_minutes);
        $this->assertEquals(0, $character->refresh()->inventory->slots()->where('item_id', $item->id)->count());
    }

    public function testNonStackingTwoHourBoonRefillCapsAtTwoHours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 120,
            'type' => 'alchemy',
            'can_stack' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->giveItem($item)
            ->getCharacter();

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(60),
            'amount_used' => 1,
            'last_for_minutes' => 60,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('2026-01-01 14:00:00', $boon->refresh()->complete->toDateTimeString());
        $this->assertEquals(120, $boon->last_for_minutes);
        $this->assertEquals(1, $boon->amount_used);
        $this->assertEquals(1, $character->refresh()->inventory->slots()->where('item_id', $item->id)->count());
    }

    public function testNonStackingTwoHourBoonWithBadAmountUsedRefillCapsAtTwoHours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 120,
            'type' => 'alchemy',
            'can_stack' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->giveItem($item)
            ->getCharacter();

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(60),
            'amount_used' => 4,
            'last_for_minutes' => 60,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('2026-01-01 14:00:00', $boon->refresh()->complete->toDateTimeString());
        $this->assertEquals(120, $boon->last_for_minutes);
        $this->assertEquals(4, $boon->amount_used);
        $this->assertEquals(1, $character->refresh()->inventory->slots()->where('item_id', $item->id)->count());
    }

    public function testStackingThreeSixtyMinuteBoonsRefillCapsAtOneHundredEightyMinutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 60,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->giveItem($item)
            ->giveItem($item)
            ->giveItem($item)
            ->giveItem($item)
            ->getCharacter();

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(60),
            'amount_used' => 3,
            'last_for_minutes' => 60,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('2026-01-01 15:00:00', $boon->refresh()->complete->toDateTimeString());
        $this->assertEquals(180, $boon->last_for_minutes);
        $this->assertEquals(3, $boon->amount_used);
        $this->assertEquals(3, $character->refresh()->inventory->slots()->where('item_id', $item->id)->count());
    }

    public function testStackingFourTwoHourBoonsRefillCapsAtFourHundredEightyMinutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        Queue::fake();

        $item = $this->createTwoHourBoonItem();

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->giveItem($item)
            ->giveItem($item)
            ->giveItem($item)
            ->giveItem($item)
            ->getCharacter();

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(60),
            'amount_used' => 4,
            'last_for_minutes' => 60,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('2026-01-01 20:00:00', $boon->refresh()->complete->toDateTimeString());
        $this->assertEquals(480, $boon->last_for_minutes);
        $this->assertEquals(4, $boon->amount_used);
        $this->assertEquals(1, $character->refresh()->inventory->slots()->where('item_id', $item->id)->count());
    }

    public function testRefillNeverChangesAmountUsed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 60,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(30),
            'amount_used' => 2,
            'last_for_minutes' => 30,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(2, $boon->refresh()->amount_used);
    }

    public function testCappedRefillConsumesNothingAndReturnsExistingError(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 60,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(180),
            'amount_used' => 3,
            'last_for_minutes' => 180,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character-sheet/'.$character->id.'/fill-up-boon/'.$boon->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(
            'Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours. Non stacking items cannot be used more then once, while another one is running.',
            $jsonData['message']
        );
        $this->assertEquals(1, $character->refresh()->inventory->slots()->where('item_id', $item->id)->count());
        $this->assertEquals(3, $boon->refresh()->amount_used);
        $this->assertEquals('2026-01-01 15:00:00', $boon->complete->toDateTimeString());
    }

    private function createBoonItem(): Item
    {
        return $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);
    }

    private function createTwoHourBoonItem(): Item
    {
        return $this->createItem([
            'usable' => true,
            'lasts_for' => 120,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);
    }
}
