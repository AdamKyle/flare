<?php

namespace Tests\Feature\Game\Kingdoms;

use App\Game\Kingdoms\Service\PurchasePeopleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class PurchasePeopleValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_negative_amount_to_purchase_is_rejected(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)->call('POST', '/api/kingdoms/purchase-people/' . $kingdom->id, [
            'amount_to_purchase' => -1,
        ]);

        $response->assertStatus(302);
    }

    public function test_zero_amount_to_purchase_is_rejected(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)->call('POST', '/api/kingdoms/purchase-people/' . $kingdom->id, [
            'amount_to_purchase' => 0,
        ]);

        $response->assertStatus(302);
    }

    public function test_direct_negative_purchase_does_not_change_character_gold(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter(['gold' => 100]);
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom(['current_population' => 10])->getKingdom();
        $character = $characterFactory->getCharacter();

        $result = resolve(PurchasePeopleService::class)->setKingdom($kingdom)->purchasePeople(-1);

        $this->assertFalse($result);
        $this->assertSame(100, $character->refresh()->gold);
        $this->assertSame(10, $kingdom->refresh()->current_population);
    }

    public function test_unaffordable_positive_purchase_is_rejected_without_mutation(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter(['gold' => 4]);
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom(['current_population' => 10])->getKingdom();
        $character = $characterFactory->getCharacter();

        $result = resolve(PurchasePeopleService::class)->setKingdom($kingdom)->purchasePeople(1);

        $this->assertFalse($result);
        $this->assertSame(4, $character->refresh()->gold);
        $this->assertSame(10, $kingdom->refresh()->current_population);
    }

    public function test_valid_affordable_purchase_still_works(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter(['gold' => 100]);
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom(['current_population' => 10])->getKingdom();
        $character = $characterFactory->getCharacter();

        $result = resolve(PurchasePeopleService::class)->setKingdom($kingdom)->purchasePeople(2);

        $this->assertTrue($result);
        $this->assertSame(90, $character->refresh()->gold);
        $this->assertSame(12, $kingdom->refresh()->current_population);
    }
}
