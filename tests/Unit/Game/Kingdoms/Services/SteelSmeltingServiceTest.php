<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Game\Kingdoms\Service\SteelSmeltingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class SteelSmeltingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_iron_spend_is_allowed(): void
    {
        Queue::fake();

        $kingdom = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->kingdomManagement()->assignKingdom([
            'current_iron' => 20,
            'current_steel' => 0,
            'max_steel' => 100,
        ])->getKingdom();

        $result = resolve(SteelSmeltingService::class)->smeltSteel(10, $kingdom);

        $this->assertSame(200, $result['status']);
        $this->assertSame(0, $kingdom->refresh()->current_iron);
    }

    public function test_overspend_is_rejected_without_mutating_iron(): void
    {
        $kingdom = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->kingdomManagement()->assignKingdom([
            'current_iron' => 19,
            'current_steel' => 0,
            'max_steel' => 100,
        ])->getKingdom();

        $result = resolve(SteelSmeltingService::class)->smeltSteel(10, $kingdom);

        $this->assertSame(422, $result['status']);
        $this->assertSame(19, $kingdom->refresh()->current_iron);
    }
}
