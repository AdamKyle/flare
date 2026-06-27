<?php

namespace Tests\Feature\Game\Kingdoms;

use App\Flare\Models\GameBuilding;
use App\Game\Kingdoms\Values\BuildingCosts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CapitalCityGoldBarWithdrawValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_negative_amount_to_withdraw_is_rejected(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom(['is_capital' => true])->getKingdom();
        $characterFactory->kingdomManagement()->assignKingdom(['gold_bars' => 2])->getKingdom();
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)->call('POST', '/api/kingdom/capital-city/withdraw-gold-bars/'.$character->id.'/'.$capitalCity->id, [
            'amount_to_withdraw' => -1,
        ]);

        $response->assertStatus(302);
    }

    public function test_zero_amount_to_withdraw_is_rejected(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom(['is_capital' => true])->getKingdom();
        $characterFactory->kingdomManagement()->assignKingdom(['gold_bars' => 2])->getKingdom();
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)->call('POST', '/api/kingdom/capital-city/withdraw-gold-bars/'.$character->id.'/'.$capitalCity->id, [
            'amount_to_withdraw' => 0,
        ]);

        $response->assertStatus(302);
    }

    public function test_valid_positive_capital_withdrawal_still_works(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter(['gold' => 0]);
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom(['is_capital' => true])->getKingdom();
        $otherKingdom = $characterFactory->kingdomManagement()->assignKingdom(['gold_bars' => 2])->getKingdom();
        GameBuilding::factory()->create(['name' => BuildingCosts::GOBLIN_COIN_BANK]);
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)->call('POST', '/api/kingdom/capital-city/withdraw-gold-bars/'.$character->id.'/'.$capitalCity->id, [
            'amount_to_withdraw' => 1,
        ]);

        $response->assertStatus(200);
        $this->assertSame(1, $otherKingdom->refresh()->gold_bars);
        $this->assertSame(2000000000, $character->refresh()->gold);
    }
}
