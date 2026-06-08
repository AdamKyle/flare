<?php

namespace Tests\Feature\Game\Kingdoms;

use App\Flare\Models\GameBuilding;
use App\Game\Kingdoms\Service\CapitalCityGoldBarManagementService;
use App\Game\Kingdoms\Values\BuildingCosts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CapitalCityGoldBarDepositValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_negative_deposit_amount_is_rejected(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom(['is_capital' => true])->getKingdom();
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)->call('POST', '/api/kingdom/capital-city/deposit-gold-bars/'.$character->id.'/'.$capitalCity->id, [
            'amount_to_purchase' => -1,
        ]);

        $response->assertStatus(302);
    }

    public function test_zero_deposit_amount_is_rejected(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom(['is_capital' => true])->getKingdom();
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)->call('POST', '/api/kingdom/capital-city/deposit-gold-bars/'.$character->id.'/'.$capitalCity->id, [
            'amount_to_purchase' => 0,
        ]);

        $response->assertStatus(302);
    }

    public function test_direct_negative_deposit_does_not_change_character_gold(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter(['gold' => 4000000000]);
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom(['is_capital' => true])->getKingdom();
        $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        GameBuilding::factory()->create(['name' => BuildingCosts::GOBLIN_COIN_BANK]);
        $character = $characterFactory->getCharacter();

        $result = resolve(CapitalCityGoldBarManagementService::class)->depositGoldBars($character, $capitalCity, -1);

        $this->assertSame(422, $result['status']);
        $this->assertSame(4000000000, $character->refresh()->gold);
    }

    public function test_valid_positive_deposit_still_works(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter(['gold' => 4000000000]);
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom(['is_capital' => true])->getKingdom();
        $receivingKingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        GameBuilding::factory()->create(['name' => BuildingCosts::GOBLIN_COIN_BANK]);
        $character = $characterFactory->getCharacter();

        $result = resolve(CapitalCityGoldBarManagementService::class)->depositGoldBars($character, $capitalCity, 1);

        $this->assertSame(200, $result['status']);
        $this->assertSame(2000000000, $character->refresh()->gold);
        $this->assertSame(1, $receivingKingdom->refresh()->gold_bars);
    }
}
