<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\BattleRewardProcessing\Services\BattleRewardStepPlanService;
use App\Game\BattleRewardProcessing\Values\BattleRewardStepPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBattleReward;

class BattleRewardLedgerServiceTest extends TestCase
{
    use CreateCharacterBattleReward, RefreshDatabase;

    public function test_ensure_steps_bulk_creates_every_planned_step_exactly_once(): void
    {
        $request = $this->createCharacterBattleRewardRequest([
            'source_type' => BattleRewardRequestSourceType::BATTLE,
        ]);
        $plan = new BattleRewardStepPlan(BattleRewardStepName::ordered());

        $steps = resolve(BattleRewardLedgerService::class)->ensureSteps($request, $plan);

        $this->assertSame(count(BattleRewardStepName::ordered()), $steps->count());
        $this->assertTrue($steps->every(fn ($step) => $step->status === BattleRewardStepStatus::PENDING));
    }

    public function test_ensure_steps_called_twice_does_not_create_duplicate_rows(): void
    {
        $request = $this->createCharacterBattleRewardRequest([
            'source_type' => BattleRewardRequestSourceType::BATTLE,
        ]);
        $plan = new BattleRewardStepPlan(BattleRewardStepName::ordered());

        resolve(BattleRewardLedgerService::class)->ensureSteps($request, $plan);
        resolve(BattleRewardLedgerService::class)->ensureSteps($request, $plan);

        $this->assertSame(count(BattleRewardStepName::ordered()), $request->steps()->count());
    }

    public function test_quest_plan_contains_only_final_player_updates_and_message_outbox(): void
    {
        $request = $this->createCharacterBattleRewardRequest([
            'source_type' => BattleRewardRequestSourceType::QUEST,
        ]);
        $plan = resolve(BattleRewardStepPlanService::class)->planQuest();

        $steps = resolve(BattleRewardLedgerService::class)->ensureSteps($request, $plan);

        $this->assertSame(
            [BattleRewardStepName::FINAL_PLAYER_UPDATES, BattleRewardStepName::MESSAGE_OUTBOX],
            $steps->pluck('step_name')->all(),
        );
    }

    public function test_faction_loyalty_plan_retains_all_seven_required_steps(): void
    {
        $request = $this->createCharacterBattleRewardRequest([
            'source_type' => BattleRewardRequestSourceType::FACTION_LOYALTY,
        ]);
        $plan = resolve(BattleRewardStepPlanService::class)->planFactionLoyalty();

        $steps = resolve(BattleRewardLedgerService::class)->ensureSteps($request, $plan);

        $this->assertSame(BattleRewardStepName::orderedForFactionLoyalty(), $steps->pluck('step_name')->all());
    }

    public function test_legacy_battle_only_row_is_excluded_from_quest_ledger_execution(): void
    {
        $request = $this->createCharacterBattleRewardRequest([
            'source_type' => BattleRewardRequestSourceType::QUEST,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $request->character_id,
            'step_name' => BattleRewardStepName::CURRENCY_REWARDS,
            'status' => BattleRewardStepStatus::PENDING,
        ]);

        $steps = resolve(BattleRewardLedgerService::class)->stepsForRequest($request);

        $this->assertFalse($steps->contains(fn ($step) => $step->step_name === BattleRewardStepName::CURRENCY_REWARDS));
    }

    public function test_completed_step_result_returns_the_persisted_result_for_a_completed_step(): void
    {
        $request = $this->createCharacterBattleRewardRequest([
            'source_type' => BattleRewardRequestSourceType::BATTLE,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $request->character_id,
            'step_name' => BattleRewardStepName::XP,
            'status' => BattleRewardStepStatus::COMPLETED,
            'result_json' => ['applied_xp' => 500],
        ]);

        $result = resolve(BattleRewardLedgerService::class)->completedStepResult($request, BattleRewardStepName::XP);

        $this->assertSame(['applied_xp' => 500], $result);
    }

    public function test_completed_step_result_returns_null_for_a_non_completed_step(): void
    {
        $request = $this->createCharacterBattleRewardRequest([
            'source_type' => BattleRewardRequestSourceType::BATTLE,
        ]);
        $this->createCharacterBattleRewardRequestStep([
            'character_battle_reward_request_id' => $request->id,
            'character_id' => $request->character_id,
            'step_name' => BattleRewardStepName::XP,
            'status' => BattleRewardStepStatus::RUNNING,
        ]);

        $result = resolve(BattleRewardLedgerService::class)->completedStepResult($request, BattleRewardStepName::XP);

        $this->assertNull($result);
    }
}
