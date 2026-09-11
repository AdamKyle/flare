<?php

namespace Tests\Unit\Game\Gems\Progression\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Game\Battle\Services\BattleDrop;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\Core\Items\Builders\BuildCosmicItem;
use App\Game\Core\Items\Builders\BuildMythicItem;
use App\Game\Core\Items\Builders\BuildUniqueItem;
use App\Game\Core\Items\Values\ItemSocketEligibility;
use App\Game\Gems\Builders\GemBuilder;
use App\Game\Gems\Progression\Services\GemScrollGenerator;
use App\Game\Gems\Progression\Services\GemWorldRewardDeliveryService;
use App\Game\Gems\Progression\Values\GemScrollRollPlan;
use App\Game\Gems\Progression\Values\GemScrollType;
use App\Game\Gems\Progression\Values\GemWorldKillRewardPlan;
use App\Game\Gems\Progression\Values\GemWorldRewardPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\Setup\GemProgression\GemWorldRewardTestFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBattleRewardRequestStep;

class GemWorldRewardDeliveryServiceTest extends TestCase
{
    use CreateCharacterBattleRewardRequestStep, RefreshDatabase;

    public function test_a_failed_delivery_leaves_the_checkpoint_and_alchemy_bag_unchanged(): void
    {
        $graph = (new GemWorldRewardTestFactory)->buildGeneratedMapGemWorldCharacter();
        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $graph->character->id]);

        $failingGenerator = Mockery::mock(GemScrollGenerator::class);
        $failingGenerator->shouldReceive('createFromPlan')->once()->andThrow(new RuntimeException('Simulated failure'));

        $exceptionWasThrown = false;

        try {
            $this->buildService($failingGenerator)->deliver($step, $graph->character, $this->onePlannedScrollPlan(), []);
        } catch (RuntimeException $exception) {
            $exceptionWasThrown = true;
            $this->assertSame('Simulated failure', $exception->getMessage());
        }

        $this->assertTrue($exceptionWasThrown);

        $step = $step->fresh();

        $this->assertSame(0, $step->checkpoint_json['rewards_delivered_through'] ?? 0);
        $this->assertSame(0, AlchemyBagSlot::where('character_id', $graph->character->id)->count());
    }

    public function test_resuming_after_a_failed_delivery_delivers_exactly_once(): void
    {
        $graph = (new GemWorldRewardTestFactory)->buildGeneratedMapGemWorldCharacter();
        $step = $this->createCharacterBattleRewardRequestStep(['character_id' => $graph->character->id]);
        $plan = $this->onePlannedScrollPlan();

        $failingGenerator = Mockery::mock(GemScrollGenerator::class);
        $failingGenerator->shouldReceive('createFromPlan')->once()->andThrow(new RuntimeException('Simulated failure'));

        try {
            $this->buildService($failingGenerator)->deliver($step, $graph->character, $plan, []);
        } catch (RuntimeException) {
            // Expected: proven by the companion atomicity test above.
        }

        $step = $step->fresh();

        $this->buildService(resolve(GemScrollGenerator::class))
            ->deliver($step, $graph->character->fresh(), $plan, $step->checkpoint_json ?? []);

        $this->assertSame(1, AlchemyBagSlot::where('character_id', $graph->character->id)->count());
    }

    /**
     * Build the real delivery service with the given Gem Scroll generator collaborator.
     */
    private function buildService(GemScrollGenerator $gemScrollGenerator): GemWorldRewardDeliveryService
    {
        return new GemWorldRewardDeliveryService(
            resolve(BattleRewardLedgerService::class),
            $gemScrollGenerator,
            resolve(ItemSocketEligibility::class),
            resolve(GemBuilder::class),
            resolve(BuildUniqueItem::class),
            resolve(BuildMythicItem::class),
            resolve(BuildCosmicItem::class),
            resolve(BattleDrop::class),
        );
    }

    /**
     * Build a single-kill plan whose only reward is a dropped XP Gem Scroll.
     */
    private function onePlannedScrollPlan(): GemWorldRewardPlan
    {
        $scrollRoll = new GemScrollRollPlan(
            dropped: true,
            scrollType: GemScrollType::XP,
            bonus: 0.10,
            durationMinutes: 120,
        );

        return new GemWorldRewardPlan([
            new GemWorldKillRewardPlan($scrollRoll, null, []),
        ]);
    }
}
