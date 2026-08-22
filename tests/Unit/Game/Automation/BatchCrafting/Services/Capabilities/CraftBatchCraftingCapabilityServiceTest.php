<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Capabilities;

use App\Game\Automation\BatchCrafting\Services\Capabilities\CraftBatchCraftingCapabilityService;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateScheduledEvent;

class CraftBatchCraftingCapabilityServiceTest extends TestCase
{
    use CreateEvent, CreateGameMap, CreateGameSkill, CreateGlobalEventGoal, CreateItem, CreateScheduledEvent, RefreshDatabase;

    private ?CraftBatchCraftingCapabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(CraftBatchCraftingCapabilityService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_can_craft_for_experience_is_false_when_all_four_crafting_skills_are_maxed(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        foreach (['Weapon Crafting', 'Armour Crafting', 'Ring Crafting', 'Spell Crafting'] as $name) {
            $skill = $this->createGameSkill(['name' => $name, 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);

            $factory = $factory->assignSkill($skill, 5, false);
        }

        $result = $this->service->canCraftForExperience($factory->getCharacter());

        $this->assertFalse($result);
    }

    public function test_can_craft_for_experience_is_true_when_at_least_one_skill_can_still_gain_levels_and_a_meaningful_item_exists(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $maxedNames = ['Weapon Crafting', 'Armour Crafting', 'Ring Crafting'];

        foreach ($maxedNames as $name) {
            $skill = $this->createGameSkill(['name' => $name, 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);

            $factory = $factory->assignSkill($skill, 5, false);
        }

        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $factory = $factory->assignSkill($spellCrafting, 10, false);

        $this->createItem(['name' => 'Meaningful Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'can_craft' => true, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $result = $this->service->canCraftForExperience($factory->getCharacter());

        $this->assertTrue($result);
    }

    public function test_can_craft_for_experience_is_false_when_no_skill_is_maxed_but_no_meaningful_item_exists(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        foreach (['Weapon Crafting', 'Armour Crafting', 'Ring Crafting', 'Spell Crafting'] as $name) {
            $skill = $this->createGameSkill(['name' => $name, 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

            $factory = $factory->assignSkill($skill, 10, false);
        }

        $result = $this->service->canCraftForExperience($factory->getCharacter());

        $this->assertFalse($result);
    }

    public function test_can_craft_for_event_is_false_without_an_eligible_goal(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->canCraftForEvent($character);

        $this->assertFalse($result);
    }

    public function test_can_craft_for_event_is_true_with_a_real_eligible_goal(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $result = $this->service->canCraftForEvent($character);

        $this->assertTrue($result);
    }

    public function test_build_returns_the_complete_capability_facts_payload(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        foreach (['Weapon Crafting', 'Armour Crafting', 'Ring Crafting'] as $name) {
            $skill = $this->createGameSkill(['name' => $name, 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);

            $factory = $factory->assignSkill($skill, 5, false);
        }

        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $factory = $factory->assignSkill($spellCrafting, 10, false);

        $this->createItem(['name' => 'Meaningful Damage Spell', 'type' => 'spell-damage', 'crafting_type' => 'spell', 'can_craft' => true, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $character = $factory->getCharacter();

        $result = $this->service->build($character);

        $spellFacts = collect($result['crafting_skills'])->firstWhere('crafting_type', 'spell');

        $this->assertTrue($result['can_craft_for_experience']);
        $this->assertFalse($result['can_craft_for_event']);
        $this->assertNull($result['event_goal']);
        $this->assertCount(4, $result['crafting_skills']);
        $this->assertSame(10, $spellFacts['level']);
        $this->assertFalse($spellFacts['is_maxed']);
    }

    public function test_crafting_skill_facts_returns_facts_for_every_existing_crafting_skill(): void
    {
        $factory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $skill = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = $factory->assignSkill($skill, 10, false)->getCharacter();

        $facts = $this->service->craftingSkillFacts($character);

        $weaponFacts = collect($facts)->firstWhere('crafting_type', 'weapon');

        $this->assertSame(10, $weaponFacts['level']);
        $this->assertSame(400, $weaponFacts['max_level']);
        $this->assertFalse($weaponFacts['is_maxed']);
    }

    public function test_event_goal_facts_is_null_without_an_eligible_goal(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->eventGoalFacts($character);

        $this->assertNull($result);
    }

    public function test_event_goal_facts_by_id_returns_facts_for_a_known_goal(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $goal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_crafts' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->eventGoalFactsById($character, $goal->id);

        $this->assertSame($goal->id, $result['goal_id']);
    }

    public function test_event_goal_facts_by_id_returns_null_for_an_unknown_goal(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $result = $this->service->eventGoalFactsById($character, 999999);

        $this->assertNull($result);
    }
}
