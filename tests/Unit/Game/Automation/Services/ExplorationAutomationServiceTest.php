<?php

namespace Tests\Unit\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\LocationType;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationStatus;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Jobs\Exploration;
use App\Game\Automation\Services\ExplorationAutomationService;
use App\Game\Battle\Events\UpdateCharacterStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Monster\MonsterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateExplorationLog;
use Tests\Traits\CreateExplorationWarning;

class ExplorationAutomationServiceTest extends TestCase
{
    use CreateCharacterAutomation;
    use CreateExplorationLog;
    use CreateExplorationWarning;
    use RefreshDatabase;

    private ExplorationAutomationService $service;

    private Character $character;

    private Monster $monster;

    private CharacterAutomation $automation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(ExplorationAutomationService::class);

        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $this->monster = (new MonsterFactory)
            ->buildMonster()
            ->getMonster();

        $this->automation = $this->createCharacterAutomation([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_begin_automation_creates_exploration_automation(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $automation = CharacterAutomation::query()->latest('id')->first();

        $this->assertEquals($this->character->id, $automation->character_id);
        $this->assertEquals($this->monster->id, $automation->monster_id);
        $this->assertEquals(AutomationType::EXPLORING, $automation->type);
    }

    public function test_begin_automation_dispatches_update_character_status(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        Event::assertDispatched(UpdateCharacterStatus::class);
    }

    public function test_begin_automation_dispatches_automation_log_update(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        Event::assertDispatched(AutomationLogUpdate::class, function (AutomationLogUpdate $event) {
            return $event->message === 'The exploration will begin in 1 minute. Every 1 minute you will encounter 6 enemies based on your fight timeout modifier.';
        });
    }

    public function test_begin_automation_log_update_includes_exact_creature_count_and_no_old_copy(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        Event::assertDispatched(AutomationLogUpdate::class, function (AutomationLogUpdate $event) {
            return $event->message === 'The exploration will begin in 1 minute. Every 1 minute you will encounter 6 enemies based on your fight timeout modifier.'
                && ! str_contains($event->message, '2 minutes')
                && ! str_contains($event->message, '50 encounters')
                && ! str_contains($event->message, 'between 6 and 12');
        });
    }

    public function test_begin_automation_dispatches_automation_time_out(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function test_begin_automation_dispatches_exploration_job(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        Queue::assertPushed(Exploration::class);
    }

    public function test_begin_automation_dispatches_exploration_job_on_exploration_queue_with_long_running_connection(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        Queue::assertPushed(Exploration::class, function (Exploration $job): bool {
            return $job->queue === 'exploration' && $job->connection === 'long_running';
        });
    }

    public function test_stop_exploration_deletes_exploration_automation(): void
    {
        Event::fake();

        $this->service->stopExploration($this->character);

        $this->assertNull(
            CharacterAutomation::where('character_id', $this->character->id)
                ->where('type', AutomationType::EXPLORING)
                ->first()
        );
    }

    public function test_stop_exploration_returns422_when_no_automation_exists(): void
    {
        Event::fake();

        $this->automation->delete();

        $response = $this->service->stopExploration($this->character);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_stop_exploration_clears_character_survival_cache(): void
    {
        Event::fake();

        Cache::put('can-character-survive-'.$this->character->id, true);

        $this->service->stopExploration($this->character);

        $this->assertFalse(Cache::has('can-character-survive-'.$this->character->id));
    }

    public function test_stop_exploration_dispatches_automation_status(): void
    {
        Event::fake();

        $this->service->stopExploration($this->character);

        Event::assertDispatched(AutomationStatus::class);
    }

    public function test_set_time_delay_sets_default_delay(): void
    {
        $this->service->setTimeDelay();

        $this->assertEquals(1, $this->service->getTimeDelay());
    }

    public function test_begin_automation_sets_completed_at_from_auto_attack_length(): void
    {
        Queue::fake();
        Event::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');

        Carbon::setTestNow($now);

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 3,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $automation = CharacterAutomation::query()->latest('id')->first();

        $this->assertEquals($now->copy()->addHours(3)->toDateTimeString(), $automation->completed_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_begin_automation_persists_attack_type(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::CAST,
        ]);

        $automation = CharacterAutomation::query()->latest('id')->first();

        $this->assertEquals(AttackTypeValue::CAST, $automation->attack_type);
    }

    public function test_begin_automation_persists_move_down_monster_list_every(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 25,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $automation = CharacterAutomation::query()->latest('id')->first();

        $this->assertEquals(25, $automation->move_down_monster_list_every);
    }

    public function test_begin_automation_persists_previous_and_current_level(): void
    {
        Queue::fake();
        Event::fake();

        $this->character->update([
            'level' => 10,
        ]);

        $this->character = $this->character->refresh();

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $automation = CharacterAutomation::query()->latest('id')->first();

        $this->assertEquals(10, $automation->previous_level);
        $this->assertEquals(10, $automation->current_level);
    }

    public function test_begin_automation_persists_special_start_context(): void
    {
        Queue::fake();
        Event::fake();

        Location::factory()->create([
            'name' => 'Gold Mine',
            'game_map_id' => $this->character->map->game_map_id,
            'x' => $this->character->map->character_position_x,
            'y' => $this->character->map->character_position_y,
            'type' => LocationType::GOLD_MINES,
        ]);

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $automation = CharacterAutomation::query()->latest('id')->first();

        $this->assertTrue($automation->started_in_special_location);
    }

    public function test_begin_automation_persists_regular_start_context(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $automation = CharacterAutomation::query()->latest('id')->first();

        $this->assertFalse($automation->started_in_special_location);
    }

    public function test_stop_exploration_dispatches_automation_time_out(): void
    {
        Event::fake();

        $this->service->stopExploration($this->character);

        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function test_stop_exploration_dispatches_update_character_status(): void
    {
        Event::fake();

        $this->service->stopExploration($this->character);

        Event::assertDispatched(UpdateCharacterStatus::class);
    }

    public function test_stop_exploration_dispatches_automation_log_update(): void
    {
        Event::fake();

        $this->service->stopExploration($this->character);

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function test_stop_exploration_clears_character_sheet_cache(): void
    {
        Event::fake();

        Cache::put('character-sheet-'.$this->character->id, ['level' => 1]);

        $this->service->stopExploration($this->character);

        $this->assertFalse(Cache::has('character-sheet-'.$this->character->id));
    }

    public function test_stop_exploration_clears_character_defence_cache(): void
    {
        Event::fake();

        Cache::put('character-defence-'.$this->character->id, 100);

        $this->service->stopExploration($this->character);

        $this->assertFalse(Cache::has('character-defence-'.$this->character->id));
    }

    public function test_set_time_delay_is_always_one_minute(): void
    {
        $this->service->setTimeDelay($this->character);

        $this->assertEquals(1, $this->service->getTimeDelay());
    }

    public function test_begin_automation_dispatches_exploration_job_after_one_minute(): void
    {
        Queue::fake();
        Event::fake();
        $now = Carbon::parse('2026-01-01 12:00:00');
        Carbon::setTestNow($now);

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        Queue::assertPushed(Exploration::class, function (Exploration $job) use ($now): bool {
            return $job->delay->toDateTimeString() === $now->copy()->addMinute()->toDateTimeString();
        });

        Carbon::setTestNow();
    }

    public function test_begin_automation_creates_exploration_log(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $automation = CharacterAutomation::query()->latest('id')->first();
        $log = ExplorationLog::where('character_id', $this->character->id)->first();

        $this->assertNotNull($log);
        $this->assertNull($log->ended_at);
        $this->assertEquals($automation->id, $log->character_automation_id);
        $this->assertEquals($this->monster->id, $log->monster_id);
        $this->assertEquals(AttackTypeValue::ATTACK, $log->attack_type);
    }

    public function test_begin_automation_clears_old_exploration_warning_and_log(): void
    {
        Queue::fake();
        Event::fake();

        $oldLog = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'ended_at' => now(),
        ]);

        $oldWarning = $this->createExplorationWarning([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'exploration_log_id' => $oldLog->id,
            'type' => 'fight',
            'message' => 'Old warning.',
        ]);

        $this->service->beginAutomation($this->character, [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertNull($oldWarning->fresh());
        $this->assertNull(ExplorationLog::find($oldLog->id));
    }

    public function test_stop_exploration_finalizes_log_with_player_stop(): void
    {
        Event::fake();

        $log = $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $this->automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'ended_at' => null,
        ]);

        $this->service->stopExploration($this->character);

        $log->refresh();

        $this->assertTrue($log->stopped_by_player);
        $this->assertEquals('player_stopped', $log->stopped_reason);
        $this->assertNotNull($log->ended_at);
    }

    public function test_stop_exploration_does_not_create_exploration_warning(): void
    {
        Event::fake();

        $this->createExplorationLog([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'character_automation_id' => $this->automation->id,
            'monster_id' => $this->monster->id,
            'attack_type' => AttackTypeValue::ATTACK,
            'ended_at' => null,
        ]);

        $this->service->stopExploration($this->character);

        $this->assertEquals(0, ExplorationWarning::where('character_id', $this->character->id)->count());
    }
}
