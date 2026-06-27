<?php

namespace Tests\Feature\Game\Kingdoms;

use App\Game\Kingdoms\Values\BuildingCosts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomGoldBarWithdrawValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_negative_amount_to_withdraw_is_rejected_without_changing_gold(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter(['gold' => 2000000000]);
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom(['gold_bars' => 2])->assignBuilding(['name' => BuildingCosts::GOBLIN_COIN_BANK], ['level' => 5])->getKingdom();
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)->call('POST', '/api/kingdoms/withdraw-bars-as-gold/'.$kingdom->id, [
            'amount_to_withdraw' => -1,
        ]);

        $response->assertStatus(302);
        $this->assertSame(2, $kingdom->refresh()->gold_bars);
        $this->assertSame(2000000000, $character->refresh()->gold);
    }

    public function test_zero_amount_to_withdraw_is_rejected(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter(['gold' => 0]);
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom(['gold_bars' => 2])->assignBuilding(['name' => BuildingCosts::GOBLIN_COIN_BANK], ['level' => 5])->getKingdom();
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)->call('POST', '/api/kingdoms/withdraw-bars-as-gold/'.$kingdom->id, [
            'amount_to_withdraw' => 0,
        ]);

        $response->assertStatus(302);
    }

    public function test_valid_positive_withdrawal_still_works(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter(['gold' => 0]);
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom(['gold_bars' => 2])->assignBuilding(['name' => BuildingCosts::GOBLIN_COIN_BANK], ['level' => 5])->getKingdom();
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)->call('POST', '/api/kingdoms/withdraw-bars-as-gold/'.$kingdom->id, [
            'amount_to_withdraw' => 1,
        ]);

        $response->assertStatus(200);
        $this->assertSame(1, $kingdom->refresh()->gold_bars);
        $this->assertSame(2000000000, $character->refresh()->gold);
    }
}
