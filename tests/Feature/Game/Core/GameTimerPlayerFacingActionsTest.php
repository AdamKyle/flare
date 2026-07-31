<?php

namespace Tests\Feature\Game\Core;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\UnitInQueue;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequestMovement;
use App\Game\Kingdoms\Jobs\RecruitUnits;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Listeners\MoveTimeOutListener;
use App\Game\PassiveSkills\Jobs\TrainPassiveSkill;
use App\Game\PassiveSkills\Services\PassiveSkillTrainingService;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use App\Game\Skills\Values\SkillTypeValue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuildingUnit;
use Tests\Traits\CreateGameUnit;

class GameTimerPlayerFacingActionsTest extends TestCase
{
    use CreateGameBuildingUnit, CreateGameUnit, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-07-02 12:00:00'));
        Queue::fake();
        config(['game_timers.development_cap.enabled' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_passive_skill_training_completion_time_is_capped_in_development(): void
    {
        $this->enableCapForTestingEnvironment();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $passiveSkill = $character->passiveSkills()->first();
        $passiveSkill->update([
            'hours_to_next' => 2,
        ]);

        resolve(PassiveSkillTrainingService::class)->trainSkill($passiveSkill->refresh(), $character);

        $this->assertSame('2026-07-02 12:01:00', $passiveSkill->refresh()->completed_at->toDateTimeString());
        Queue::assertPushed(TrainPassiveSkill::class);
    }

    public function test_passive_skill_training_keeps_real_time_outside_development(): void
    {
        $this->disableCapForTestingEnvironment();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $passiveSkill = $character->passiveSkills()->first();
        $passiveSkill->update([
            'hours_to_next' => 2,
        ]);

        resolve(PassiveSkillTrainingService::class)->trainSkill($passiveSkill->refresh(), $character);

        $this->assertSame('2026-07-02 14:00:00', $passiveSkill->refresh()->completed_at->toDateTimeString());
        Queue::assertPushed(TrainPassiveSkill::class);
    }

    public function test_kingdom_building_timer_is_capped_in_development(): void
    {
        $this->enableCapForTestingEnvironment();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding([
                'max_level' => 5,
                'time_to_build' => 120,
                'time_increase_amount' => 0,
            ], [
                'level' => 1,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'to_level' => 2,
            ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);

        $queue = BuildingInQueue::where('kingdom_id', $kingdom->id)
            ->where('building_id', $building->id)
            ->where('type', BuildingQueueType::UPGRADE)
            ->first();

        $this->assertNotNull($queue);
        $this->assertSame('2026-07-02 12:01:00', $queue->completed_at->toDateTimeString());
    }

    public function test_kingdom_building_timer_keeps_real_time_outside_development(): void
    {
        $this->disableCapForTestingEnvironment();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding([
                'max_level' => 5,
                'time_to_build' => 120,
                'time_increase_amount' => 0,
            ], [
                'level' => 1,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'to_level' => 2,
            ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);

        $queue = BuildingInQueue::where('kingdom_id', $kingdom->id)
            ->where('building_id', $building->id)
            ->where('type', BuildingQueueType::UPGRADE)
            ->first();

        $this->assertNotNull($queue);
        $this->assertSame('2026-07-02 14:02:00', $queue->completed_at->toDateTimeString());
    }

    public function test_unit_recruitment_timer_is_capped_in_development(): void
    {
        $this->enableCapForTestingEnvironment();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $characterFactory->updateSkill('Kingmanship', [
            'skill_type' => SkillTypeValue::EFFECTS_KINGDOM->value,
        ]);
        $kingdom = $characterFactory->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding()
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $unit = $this->createGameUnit([
            'name' => 'Spearmen',
            'time_to_recruit' => 120,
        ]);
        $this->createGameBuildingUnit([
            'game_building_id' => $building->game_building_id,
            'game_unit_id' => $unit->id,
            'required_level' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$kingdom->id.'/recruit-units/'.$unit->id, [
                'amount' => 1,
            ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);

        $queue = UnitInQueue::where('kingdom_id', $kingdom->id)
            ->where('game_unit_id', $unit->id)
            ->first();

        $this->assertNotNull($queue);
        $this->assertSame('2026-07-02 12:01:00', $queue->completed_at->toDateTimeString());
        Queue::assertPushed(RecruitUnits::class);
    }

    public function test_movement_timer_is_capped_in_development(): void
    {
        $this->enableCapForTestingEnvironment();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        resolve(MoveTimeOutListener::class)->handle(new MoveTimeOutEvent($character, 120, false));

        $this->assertFalse($character->refresh()->can_move);
        $this->assertSame('2026-07-02 12:01:00', $character->refresh()->can_move_again_at->toDateTimeString());
    }

    public function test_capital_city_building_travel_timer_is_capped_in_development(): void
    {
        $this->enableCapForTestingEnvironment();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $characterFactory
            ->passiveSkillManagement()
            ->assignPassiveSkill(PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION, 0, [
                'name' => 'Capital City Building Request Travel Time Reduction',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ]);
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
            'x_position' => 16,
            'y_position' => 16,
        ])->getKingdom();
        $targetKingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 2000,
            'current_clay' => 2000,
            'current_stone' => 2000,
            'current_iron' => 2000,
            'current_population' => 2000,
            'x_position' => 1600,
            'y_position' => 16,
        ])->assignBuilding([
            'max_level' => 5,
        ], [
            'level' => 1,
        ])->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/upgrade-building-requests/'.$character->id.'/'.$capitalCity->id, [
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(200);

        $queue = CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->first();

        $this->assertNotNull($queue);
        $this->assertSame('2026-07-02 12:01:00', $queue->completed_at->toDateTimeString());
        Queue::assertPushed(CapitalCityBuildingRequestMovement::class);
    }

    private function enableCapForTestingEnvironment(): void
    {
        config(['game_timers.development_cap.environments' => ['testing']]);
    }

    private function disableCapForTestingEnvironment(): void
    {
        config(['game_timers.development_cap.environments' => ['development']]);
    }
}
