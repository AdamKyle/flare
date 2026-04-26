<?php

namespace Tests\Unit\Game\Automation\Services;

use Carbon\Carbon;
use App\Game\Skills\Values\SkillTypeValue;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Monster;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationStatus;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Jobs\Exploration;
use App\Game\Automation\Services\ExplorationAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Monster\MonsterFactory;
use Tests\TestCase;

class ExplorationAutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExplorationAutomationService $service;

    private Character $character;

    private Monster $monster;

    public function setUp(): void
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
    }

    public function testBeginAutomationCreatesExplorationAutomation(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->params());

        $automation = CharacterAutomation::first();

        $this->assertEquals($this->character->id, $automation->character_id);
        $this->assertEquals($this->monster->id, $automation->monster_id);
        $this->assertEquals(AutomationType::EXPLORING, $automation->type);
    }

    public function testBeginAutomationDispatchesUpdateCharacterStatus(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->params());

        Event::assertDispatched(UpdateCharacterStatus::class);
    }

    public function testBeginAutomationDispatchesAutomationLogUpdate(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->params());

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function testBeginAutomationDispatchesAutomationTimeOut(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->params());

        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testBeginAutomationDispatchesExplorationJob(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, $this->params());

        Queue::assertPushed(Exploration::class);
    }

    public function testStopExplorationDeletesExplorationAutomation(): void
    {
        Event::fake();

        CharacterAutomation::create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->service->stopExploration($this->character);


        $this->assertNull(
            CharacterAutomation::where('character_id', $this->character->id)
                ->where('type', AutomationType::EXPLORING)
                ->first()
        );
    }

    public function testStopExplorationReturns422WhenNoAutomationExists(): void
    {
        Event::fake();

        $response = $this->service->stopExploration($this->character);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function testStopExplorationClearsCharacterSurvivalCache(): void
    {
        Event::fake();

        CharacterAutomation::create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        Cache::put('can-character-survive-' . $this->character->id, true);

        $this->service->stopExploration($this->character);

        $this->assertFalse(Cache::has('can-character-survive-' . $this->character->id));
    }

    public function testStopExplorationDispatchesAutomationStatus(): void
    {
        Event::fake();

        CharacterAutomation::create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->service->stopExploration($this->character);

        Event::assertDispatched(AutomationStatus::class);
    }

    public function testSetTimeDelaySetsDefaultDelay(): void
    {
        $this->service->setTimeDelay($this->character);

        $this->assertEquals(5, $this->service->getTimeDelay());
    }

    public function testBeginAutomationSetsCompletedAtFromAutoAttackLength(): void
    {
        Queue::fake();
        Event::fake();

        $now = Carbon::parse('2026-01-01 12:00:00');

        Carbon::setTestNow($now);

        $this->service->beginAutomation($this->character, [
            ...$this->params(),
            'auto_attack_length' => 3,
        ]);

        $automation = CharacterAutomation::first();

        $this->assertEquals($now->copy()->addHours(3)->toDateTimeString(), $automation->completed_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function testBeginAutomationPersistsAttackType(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, [
            ...$this->params(),
            'attack_type' => AttackTypeValue::CAST,
        ]);

        $automation = CharacterAutomation::first();

        $this->assertEquals(AttackTypeValue::CAST, $automation->attack_type);
    }

    public function testBeginAutomationPersistsMoveDownMonsterListEvery(): void
    {
        Queue::fake();
        Event::fake();

        $this->service->beginAutomation($this->character, [
            ...$this->params(),
            'move_down_the_list_every' => 25,
        ]);

        $automation = CharacterAutomation::first();

        $this->assertEquals(25, $automation->move_down_monster_list_every);
    }

    public function testBeginAutomationPersistsPreviousAndCurrentLevel(): void
    {
        Queue::fake();
        Event::fake();

        $this->character->update([
            'level' => 10,
        ]);

        $this->character = $this->character->refresh();

        $this->service->beginAutomation($this->character, $this->params());

        $automation = CharacterAutomation::first();

        $this->assertEquals(10, $automation->previous_level);
        $this->assertEquals(10, $automation->current_level);
    }

    public function testStopExplorationDispatchesAutomationTimeOut(): void
    {
        Event::fake();

        $this->createAutomation();

        $this->service->stopExploration($this->character);

        Event::assertDispatched(AutomationTimeOut::class);
    }

    public function testStopExplorationDispatchesUpdateCharacterStatus(): void
    {
        Event::fake();

        $this->createAutomation();

        $this->service->stopExploration($this->character);

        Event::assertDispatched(UpdateCharacterStatus::class);
    }

    public function testStopExplorationDispatchesAutomationLogUpdate(): void
    {
        Event::fake();

        $this->createAutomation();

        $this->service->stopExploration($this->character);

        Event::assertDispatched(AutomationLogUpdate::class);
    }

    public function testStopExplorationClearsCharacterSheetCache(): void
    {
        Event::fake();

        $this->createAutomation();

        Cache::put('character-sheet-' . $this->character->id, ['level' => 1]);

        $this->service->stopExploration($this->character);

        $this->assertFalse(Cache::has('character-sheet-' . $this->character->id));
    }

    public function testStopExplorationClearsCharacterDefenceCache(): void
    {
        Event::fake();

        $this->createAutomation();

        Cache::put('character-defence-' . $this->character->id, 100);

        $this->service->stopExploration($this->character);

        $this->assertFalse(Cache::has('character-defence-' . $this->character->id));
    }

    public function testSetTimeDelayUsesFightTimeOutModifier(): void
    {
        $skill = $this->character->skills
            ->where('baseSkill.type', SkillTypeValue::EFFECTS_BATTLE_TIMER->value)
            ->first();

        $skill->baseSkill->update([
            'fight_time_out_mod_bonus_per_level' => 0.2,
        ]);

        $this->character = $this->character->refresh();

        $this->service->setTimeDelay($this->character);

        $this->assertEquals(3, $this->service->getTimeDelay());
    }

    private function params(): array
    {
        return [
            'selected_monster_id' => $this->monster->id,
            'auto_attack_length' => 1,
            'move_down_the_list_every' => 10,
            'attack_type' => AttackTypeValue::ATTACK,
        ];
    }

    private function createAutomation(): CharacterAutomation
    {
        return CharacterAutomation::create([
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'move_down_monster_list_every' => 10,
            'previous_level' => $this->character->level,
            'current_level' => $this->character->level,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
    }
}