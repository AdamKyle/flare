<?php

namespace Tests\Unit\Game\Kingdoms\Service;

use App\Game\Kingdoms\Service\CharacterGoldBarDepositService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterGoldBarDepositServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?CharacterFactory $character = null;

    private ?MockInterface $updateKingdom = null;

    private ?CharacterGoldBarDepositService $characterGoldBarDepositService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->updateKingdom = Mockery::mock(UpdateKingdom::class);

        $this->characterGoldBarDepositService = new CharacterGoldBarDepositService(
            $this->updateKingdom,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();

        $this->character = null;

        $this->updateKingdom = null;

        $this->characterGoldBarDepositService = null;
    }

    public function test_deposit_spreads_gold_bars_across_kingdoms_without_exceeding_one_thousand(): void
    {
        $character = $this->character
            ->kingdomManagement()
            ->assignKingdom(['gold_bars' => 0])
            ->assignKingdom(['gold_bars' => 900])
            ->getCharacter();

        $this->updateKingdom->shouldReceive('updateKingdomAllKingdoms')->once();

        $deposited = $this->characterGoldBarDepositService->deposit($character->id, 500);

        $goldBars = $character->kingdoms()->orderBy('id')->pluck('gold_bars')->all();

        $this->assertSame(500, $deposited);
        $this->assertEquals([400, 1000], $goldBars);
        $this->assertLessThanOrEqual(1000, max($goldBars));
    }

    public function test_deposit_only_fills_the_remaining_kingdom_capacity(): void
    {
        $character = $this->character
            ->kingdomManagement()
            ->assignKingdom(['gold_bars' => 800])
            ->assignKingdom(['gold_bars' => 950])
            ->getCharacter();

        $this->updateKingdom->shouldReceive('updateKingdomAllKingdoms')->once();

        $deposited = $this->characterGoldBarDepositService->deposit($character->id, 2000);

        $this->assertSame(250, $deposited);
        $this->assertEquals([1000, 1000], $character->kingdoms()->orderBy('id')->pluck('gold_bars')->all());
    }

    public function test_deposit_returns_zero_when_every_kingdom_is_full(): void
    {
        $character = $this->character
            ->kingdomManagement()
            ->assignKingdom(['gold_bars' => 1000])
            ->assignKingdom(['gold_bars' => 1000])
            ->getCharacter();

        $this->updateKingdom->shouldNotReceive('updateKingdomAllKingdoms');

        $deposited = $this->characterGoldBarDepositService->deposit($character->id, 2000);

        $this->assertSame(0, $deposited);
        $this->assertEquals([1000, 1000], $character->kingdoms()->orderBy('id')->pluck('gold_bars')->all());
    }

    public function test_deposit_returns_zero_for_a_character_without_kingdoms(): void
    {
        $character = $this->character->getCharacter();

        $this->updateKingdom->shouldNotReceive('updateKingdomAllKingdoms');

        $deposited = $this->characterGoldBarDepositService->deposit($character->id, 500);

        $this->assertSame(0, $deposited);
        $this->assertSame(0, $character->kingdoms()->count());
    }
}
