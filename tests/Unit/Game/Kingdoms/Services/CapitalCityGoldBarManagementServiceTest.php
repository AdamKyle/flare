<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\GameBuilding;
use App\Game\Kingdoms\Service\CapitalCityGoldBarManagementService;
use App\Game\Kingdoms\Values\BuildingCosts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateKingdomBuilding;

class CapitalCityGoldBarManagementServiceTest extends TestCase
{
    use CreateGameBuilding, CreateKingdomBuilding, RefreshDatabase;

    private ?CharacterFactory $characterFactory = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = new CharacterFactory;
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->characterFactory = null;
    }

    public function testCapitalCityGoldBarManagementReturnsTheClearZeroOtherKingdomMessage(): void
    {
        $factory = $this->characterFactory->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $factory->kingdomManagement()->assignKingdom(['is_capital' => true])->getKingdom();
        $character = $factory->getCharacter();

        $result = resolve(CapitalCityGoldBarManagementService::class)
            ->fetchGoldBarDetails($character, $capitalCity);

        $this->assertStringContainsString(
            'This capital city cannot deposit or withdraw Gold Bars because you do not own any other kingdoms on this plane.',
            $result['gold_bar_details']['no_other_kingdoms_message'],
        );
    }

    public function testCapitalCityGoldBarManagementDoesNotReportAllowedGoldBarsAsZeroForZeroOtherKingdomCase(): void
    {
        $factory = $this->characterFactory->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $factory->kingdomManagement()->assignKingdom(['is_capital' => true])->getKingdom();
        $character = $factory->getCharacter();

        $result = resolve(CapitalCityGoldBarManagementService::class)
            ->fetchGoldBarDetails($character, $capitalCity);

        $this->assertTrue($result['gold_bar_details']['no_other_kingdoms']);
    }

    public function testCapitalCityGoldBarManagementStillExcludesCapitalCityFromDistributionCalculations(): void
    {
        $this->createGameBuilding(['name' => BuildingCosts::GOBLIN_COIN_BANK]);

        $factory = $this->characterFactory->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $factory->kingdomManagement()->assignKingdom(['is_capital' => true])->getKingdom();
        $factory->kingdomManagement()->assignKingdom();
        $character = $factory->getCharacter();

        $result = resolve(CapitalCityGoldBarManagementService::class)
            ->fetchGoldBarDetails($character, $capitalCity);

        $this->assertFalse($result['gold_bar_details']['no_other_kingdoms']);
        $this->assertSame(
            1,
            $character->kingdoms()
                ->where('id', '!=', $capitalCity->id)
                ->where('game_map_id', $capitalCity->game_map_id)
                ->count(),
        );
    }

    public function testBackendDepositIsGuardedWhenThereAreZeroOtherKingdoms(): void
    {
        Event::fake();

        $factory = $this->characterFactory->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $factory->kingdomManagement()->assignKingdom(['is_capital' => true])->getKingdom();
        $character = $factory->getCharacter();

        $result = resolve(CapitalCityGoldBarManagementService::class)
            ->depositGoldBars($character, $capitalCity, 1);

        $this->assertEquals(422, $result['status']);
        $this->assertStringContainsString(
            'This capital city cannot deposit or withdraw Gold Bars because you do not own any other kingdoms on this plane.',
            $result['message'],
        );
    }

    public function testBackendWithdrawIsGuardedWhenThereAreZeroOtherKingdoms(): void
    {
        Event::fake();

        $factory = $this->characterFactory->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $factory->kingdomManagement()->assignKingdom(['is_capital' => true])->getKingdom();
        $character = $factory->getCharacter();

        $result = resolve(CapitalCityGoldBarManagementService::class)
            ->convertGoldBars($character, $capitalCity, 1);

        $this->assertEquals(422, $result['status']);
        $this->assertStringContainsString(
            'This capital city cannot deposit or withdraw Gold Bars because you do not own any other kingdoms on this plane.',
            $result['message'],
        );
    }

    public function testNegativeWithdrawIsRejectedWithoutChangingCharacterGoldOrKingdomGoldBars(): void
    {
        $factory = $this->characterFactory->createBaseCharacter()->givePlayerLocation()->updateCharacter(['gold' => 2000000000]);
        $capitalCity = $factory->kingdomManagement()->assignKingdom(['is_capital' => true])->getKingdom();
        $otherKingdom = $factory->kingdomManagement()->assignKingdom(['gold_bars' => 2])->getKingdom();
        $character = $factory->getCharacter();

        $result = resolve(CapitalCityGoldBarManagementService::class)
            ->convertGoldBars($character, $capitalCity, -1);

        $this->assertSame(422, $result['status']);
        $this->assertSame(2000000000, $character->refresh()->gold);
        $this->assertSame(2, $otherKingdom->refresh()->gold_bars);
    }

    public function testZeroWithdrawIsRejected(): void
    {
        $factory = $this->characterFactory->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $factory->kingdomManagement()->assignKingdom(['is_capital' => true])->getKingdom();
        $factory->kingdomManagement()->assignKingdom(['gold_bars' => 2])->getKingdom();
        $character = $factory->getCharacter();

        $result = resolve(CapitalCityGoldBarManagementService::class)
            ->convertGoldBars($character, $capitalCity, 0);

        $this->assertSame(422, $result['status']);
    }

    public function testPositiveWithdrawStillWorks(): void
    {
        Event::fake();

        $factory = $this->characterFactory->createBaseCharacter()->givePlayerLocation()->updateCharacter(['gold' => 0]);
        $capitalCity = $factory->kingdomManagement()->assignKingdom(['is_capital' => true])->getKingdom();
        $otherKingdom = $factory->kingdomManagement()->assignKingdom(['gold_bars' => 2])->getKingdom();
        $this->createGameBuilding(['name' => BuildingCosts::GOBLIN_COIN_BANK]);
        $character = $factory->getCharacter();

        $result = resolve(CapitalCityGoldBarManagementService::class)
            ->convertGoldBars($character, $capitalCity, 1);

        $this->assertSame(200, $result['status']);
        $this->assertSame(2000000000, $character->refresh()->gold);
        $this->assertSame(1, $otherKingdom->refresh()->gold_bars);
    }
}
