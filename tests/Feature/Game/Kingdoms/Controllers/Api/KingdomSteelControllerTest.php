<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\SmeltingProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomSteelControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_smelt_steel_in_own_kingdom(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()
            ->assignKingdom([
                'current_iron' => 2000,
                'max_steel' => 2000,
                'current_steel' => 0,
                'current_population' => 2000,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/smelt-iron/'.$kingdom->id, [
                'amount_to_smelt' => 1,
            ]);

        $response->assertOk();
        $this->assertSame(1998, $kingdom->refresh()->current_iron);
        $this->assertSame(1, SmeltingProgress::where('kingdom_id', $kingdom->id)->count());
    }

    public function test_owner_can_cancel_smelting_in_own_kingdom(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()
            ->assignKingdom([
                'current_iron' => 1000,
                'max_iron' => 2000,
                'current_population' => 2000,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $queue = SmeltingProgress::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->addMinutes(50),
            'amount_to_smelt' => 100,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/cancel-smelting/'.$kingdom->id);

        $response->assertOk();
        $this->assertNull(SmeltingProgress::find($queue->id));
        $this->assertGreaterThan(1000, $kingdom->refresh()->current_iron);
    }

    public function test_non_owner_cannot_smelt_steel_in_another_characters_kingdom(): void
    {
        Queue::fake();

        $ownerFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $ownerFactory->kingdomManagement()
            ->assignKingdom([
                'current_iron' => 2000,
                'current_population' => 2000,
                'current_steel' => 0,
                'max_steel' => 2000,
            ])
            ->getKingdom();

        $nonOwner = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($nonOwner->user)
            ->call('POST', '/api/kingdoms/smelt-iron/'.$kingdom->id, [
                'amount_to_smelt' => 1,
            ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Nope. Not allowed to do that.']);
        $this->assertSame(2000, $kingdom->refresh()->current_iron);
        $this->assertSame(0, SmeltingProgress::where('kingdom_id', $kingdom->id)->count());
    }

    public function test_non_owner_cannot_cancel_smelting_in_another_characters_kingdom(): void
    {
        Queue::fake();

        $ownerFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $ownerFactory->kingdomManagement()
            ->assignKingdom([
                'current_iron' => 1000,
                'max_iron' => 2000,
            ])
            ->getKingdom();
        $queue = SmeltingProgress::create([
            'character_id' => $ownerFactory->getCharacter()->id,
            'kingdom_id' => $kingdom->id,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->addMinutes(50),
            'amount_to_smelt' => 100,
        ]);

        $nonOwner = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($nonOwner->user)
            ->call('POST', '/api/kingdoms/cancel-smelting/'.$kingdom->id,
                [], [], [], ['HTTP_ACCEPT' => 'application/json']
            );

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Nope. Not allowed to do that.']);
        $this->assertNotNull(SmeltingProgress::find($queue->id));
        $this->assertSame(1000, $kingdom->refresh()->current_iron);
    }
}
