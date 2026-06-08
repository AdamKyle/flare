<?php

namespace Tests\Feature\Game\Kingdoms;

use App\Game\Kingdoms\Values\BuildingCosts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomGoldBarPurchaseValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_negative_amount_to_purchase_is_rejected(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding(['name' => BuildingCosts::GOBLIN_COIN_BANK], ['level' => 5])->getKingdom();
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)->call('POST', '/api/kingdoms/purchase-gold-bars/' . $kingdom->id, [
            'amount_to_purchase' => -1,
        ]);

        $response->assertStatus(302);
    }

    public function test_zero_amount_to_purchase_is_rejected(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding(['name' => BuildingCosts::GOBLIN_COIN_BANK], ['level' => 5])->getKingdom();
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)->call('POST', '/api/kingdoms/purchase-gold-bars/' . $kingdom->id, [
            'amount_to_purchase' => 0,
        ]);

        $response->assertStatus(302);
    }

    public function test_character_gold_is_unchanged_after_rejected_negative_amount(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter(['gold' => 4000000000]);
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding(['name' => BuildingCosts::GOBLIN_COIN_BANK], ['level' => 5])->getKingdom();
        $character = $characterFactory->getCharacter();

        $this->actingAs($character->user)->call('POST', '/api/kingdoms/purchase-gold-bars/' . $kingdom->id, [
            'amount_to_purchase' => -1,
        ]);

        $this->assertSame(4000000000, $character->refresh()->gold);
        $this->assertSame(0, $kingdom->refresh()->gold_bars);
    }

    public function test_valid_positive_purchase_still_works(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter(['gold' => 4000000000]);
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding(['name' => BuildingCosts::GOBLIN_COIN_BANK], ['level' => 5])->getKingdom();
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)->call('POST', '/api/kingdoms/purchase-gold-bars/' . $kingdom->id, [
            'amount_to_purchase' => 1,
        ]);

        $response->assertStatus(200);
        $this->assertSame(2000000000, $character->refresh()->gold);
        $this->assertSame(1, $kingdom->refresh()->gold_bars);
    }
}
