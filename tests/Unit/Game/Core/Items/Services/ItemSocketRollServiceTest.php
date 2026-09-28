<?php

namespace Tests\Unit\Game\Core\Items\Services;

use App\Flare\Models\Item;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Items\Services\ItemSocketRollService;
use App\Game\Core\Items\Values\ItemSocketEligibility;
use Tests\TestCase;

class ItemSocketRollServiceTest extends TestCase
{
    private RandomNumberGenerator $randomNumberGenerator;

    private ChanceCalculator $chanceCalculator;

    private ItemSocketRollService $service;

    /**
     * Create the socket roll subject with controlled randomness.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->randomNumberGenerator = $this->createStub(RandomNumberGenerator::class);
        $this->chanceCalculator = $this->createStub(ChanceCalculator::class);
        $this->service = new ItemSocketRollService(
            $this->randomNumberGenerator,
            $this->chanceCalculator,
            new ItemSocketEligibility,
        );
    }

    /**
     * Prove an initial two-handed Seer roll always grants three sockets.
     *
     * @return void
     */
    public function test_initial_two_handed_seer_roll_grants_three_sockets(): void
    {
        $item = new Item(['type' => 'bow', 'socket_count' => 0]);

        $this->assertSame(3, $this->service->rollForSeer($item));
    }

    /**
     * Prove a later two-handed Seer roll can reach six sockets.
     *
     * @return void
     */
    public function test_later_two_handed_seer_roll_can_reach_six_sockets(): void
    {
        $item = new Item(['type' => 'bow', 'socket_count' => 3]);
        $this->randomNumberGenerator = $this->createMock(RandomNumberGenerator::class);
        $this->service = new ItemSocketRollService($this->randomNumberGenerator, $this->chanceCalculator, new ItemSocketEligibility);
        $this->randomNumberGenerator->expects($this->once())->method('numberBetween')->with(1, 100)->willReturn(100);

        $this->assertSame(6, $this->service->rollForSeer($item));
    }

    /**
     * Prove a Seer roll never reduces the current socket count.
     *
     * @return void
     */
    public function test_seer_roll_never_reduces_socket_count(): void
    {
        $item = new Item(['type' => 'body', 'socket_count' => 5]);
        $this->randomNumberGenerator = $this->createMock(RandomNumberGenerator::class);
        $this->service = new ItemSocketRollService($this->randomNumberGenerator, $this->chanceCalculator, new ItemSocketEligibility);
        $this->randomNumberGenerator->expects($this->once())->method('numberBetween')->with(1, 100)->willReturn(1);

        $this->assertSame(5, $this->service->rollForSeer($item));
    }

    /**
     * Prove an ordinary drop without a successful socket chance remains unsocketed.
     *
     * @return void
     */
    public function test_ordinary_drop_uses_twenty_percent_socket_gate(): void
    {
        $item = new Item(['type' => 'body']);
        $this->chanceCalculator = $this->createMock(ChanceCalculator::class);
        $this->service = new ItemSocketRollService($this->randomNumberGenerator, $this->chanceCalculator, new ItemSocketEligibility);
        $this->chanceCalculator->expects($this->once())->method('passesPercentage')->with(20)->willReturn(false);

        $this->assertSame(0, $this->service->rollForOrdinaryDrop($item));
    }
}
