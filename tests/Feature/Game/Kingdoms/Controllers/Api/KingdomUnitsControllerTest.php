<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\GameUnit;
use App\Flare\Models\UnitInQueue;
use App\Flare\Values\AutomationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomUnitsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_cancel_rejects_capital_city_owned_unit_queue(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create();
        $queue = UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => 1,
            'capital_city_unit_queue_id' => 123,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/recruit-units/cancel', [
                'queue_id' => $queue->id,
            ]);

        $response->assertStatus(422);
        $this->assertNotNull(UnitInQueue::find($queue->id));
    }

    public function test_manual_recruit_rejects_during_automation(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->assignUnits([], 1)->getKingdom();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();
        $gameUnit = $kingdom->units()->first()->gameUnit;

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$kingdom->id.'/recruit-units/'.$gameUnit->id, [
                'amount' => 1,
                'recruitment_type' => 'resources',
            ]);

        $response->assertStatus(422);
        $this->assertSame(0, UnitInQueue::where('kingdom_id', $kingdom->id)->count());
    }

    public function test_manual_recruit_cancel_rejects_during_automation(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create();
        $queue = UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => 1,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/recruit-units/cancel', [
                'queue_id' => $queue->id,
            ]);

        $response->assertStatus(422);
        $this->assertNotNull(UnitInQueue::find($queue->id));
    }

    public function testNonOwnerCannotRecruitUnitsInAnotherCharactersKingdom(): void
    {
        Queue::fake();

        $ownerFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $ownerFactory->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignUnits([], 1)
            ->getKingdom();
        $gameUnit = $kingdom->units()->first()->gameUnit;

        $nonOwner = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($nonOwner->user)
            ->call('POST', '/api/kingdoms/' . $kingdom->id . '/recruit-units/' . $gameUnit->id, [
                'amount' => 1,
                'recruitment_type' => 'resources',
            ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Nope. Not allowed to do that.']);
        $this->assertSame(0, UnitInQueue::where('kingdom_id', $kingdom->id)->count());
        $this->assertSame(2000, $kingdom->refresh()->current_wood);
        $this->assertSame(2000, $kingdom->refresh()->current_clay);
        $this->assertSame(2000, $kingdom->refresh()->current_stone);
        $this->assertSame(2000, $kingdom->refresh()->current_iron);
        $this->assertSame(2000, $kingdom->refresh()->current_population);
    }

    public function testNonOwnerCannotCancelAnotherCharactersUnitQueue(): void
    {
        $ownerFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $ownerFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 1000,
            'current_clay' => 1000,
            'current_stone' => 1000,
            'current_iron' => 1000,
            'current_population' => 1000,
        ])->getKingdom();
        $owner = $ownerFactory->getCharacter();
        $unit = GameUnit::factory()->create();
        $queue = UnitInQueue::factory()->create([
            'character_id' => $owner->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => 1,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->addHour(),
        ]);

        $nonOwner = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($nonOwner->user)
            ->call('POST', '/api/kingdoms/recruit-units/cancel', [
                'queue_id' => $queue->id,
            ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Not allowed to do that.']);
        $this->assertNotNull(UnitInQueue::find($queue->id));
        $this->assertSame(1000, $kingdom->refresh()->current_wood);
        $this->assertSame(1000, $kingdom->refresh()->current_clay);
        $this->assertSame(1000, $kingdom->refresh()->current_stone);
        $this->assertSame(1000, $kingdom->refresh()->current_iron);
        $this->assertSame(1000, $kingdom->refresh()->current_population);
    }
}
