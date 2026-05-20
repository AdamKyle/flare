<?php

namespace Feature\Game\Automation;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Game\Battle\Services\CelestialFightService;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\Maps\Services\PctService;
use App\Game\Skills\Values\SkillTypeValue;
use App\Game\Automation\Middleware\IsCharacterExploring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Monster\MonsterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

/**
 * This class is testing multiple enforcement restrictions when various automations are running
 * such as:
 *
 * MonsterFightService — manual fighting blocked
 * CelestialFightService — celestial fighting blocked
 * PctService — PCT blocked
 * Delve start endpoint/controller
 * Faction Loyalty start endpoint/controller
 * Exploration start endpoint/controller
 * Crafting endpoint/controller
 *
 * This is a feature / integration enforcement test across multiple services and controllers.
 */
class AutomationRestrictionEnforcementTest extends TestCase
{
    use CreateGameSkill, CreateItem, RefreshDatabase;

    public function testManualFightingIsBlockedWhileExplorationIsRunning(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $character->map->game_map_id,
            ])
            ->getMonster();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = resolve(MonsterFightService::class)->setupMonster($character, [
            'attack_type' => AttackTypeValue::ATTACK,
            'selected_monster_id' => $monster->id,
        ]);

        $this->assertEquals(422, $response['status']);
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::EXPLORING)->first());
    }

    public function testManualFightingIsBlockedWhileDelveIsRunning(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $character->map->game_map_id,
            ])
            ->getMonster();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = resolve(MonsterFightService::class)->setupMonster($character, [
            'attack_type' => AttackTypeValue::ATTACK,
            'selected_monster_id' => $monster->id,
        ]);

        $this->assertEquals(422, $response['status']);
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::DELVE)->first());
    }

    public function testManualFightingIsBlockedWhileFactionLoyaltyIsRunning(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $character->map->game_map_id,
            ])
            ->getMonster();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = resolve(MonsterFightService::class)->setupMonster($character, [
            'attack_type' => AttackTypeValue::ATTACK,
            'selected_monster_id' => $monster->id,
        ]);

        $this->assertEquals(422, $response['status']);
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::FACTION_LOYALTY)->first());
    }

    public function testCelestialFightingIsBlockedWhileExplorationIsRunning(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $character->map->game_map_id,
                'is_celestial_entity' => true,
            ])
            ->getMonster();
        $celestialFight = CelestialFight::factory()->create([
            'monster_id' => $monster->id,
            'character_id' => $character->id,
            'conjured_at' => now(),
            'x_position' => $character->map->character_position_x,
            'y_position' => $character->map->character_position_y,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PUBLIC,
        ]);
        $characterInCelestialFight = CharacterInCelestialFight::factory()->create([
            'celestial_fight_id' => $celestialFight->id,
            'character_id' => $character->id,
            'character_max_health' => 100,
            'character_current_health' => 100,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = resolve(CelestialFightService::class)->fight($character, $celestialFight, $characterInCelestialFight, AttackTypeValue::ATTACK);

        $this->assertEquals(422, $response['status']);
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::EXPLORING)->first());
    }

    public function testCelestialFightingIsBlockedWhileDelveIsRunning(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $character->map->game_map_id,
                'is_celestial_entity' => true,
            ])
            ->getMonster();
        $celestialFight = CelestialFight::factory()->create([
            'monster_id' => $monster->id,
            'character_id' => $character->id,
            'conjured_at' => now(),
            'x_position' => $character->map->character_position_x,
            'y_position' => $character->map->character_position_y,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PUBLIC,
        ]);
        $characterInCelestialFight = CharacterInCelestialFight::factory()->create([
            'celestial_fight_id' => $celestialFight->id,
            'character_id' => $character->id,
            'character_max_health' => 100,
            'character_current_health' => 100,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = resolve(CelestialFightService::class)->fight($character, $celestialFight, $characterInCelestialFight, AttackTypeValue::ATTACK);

        $this->assertEquals(422, $response['status']);
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::DELVE)->first());
    }

    public function testCelestialFightingIsBlockedWhileFactionLoyaltyIsRunning(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $character->map->game_map_id,
                'is_celestial_entity' => true,
            ])
            ->getMonster();
        $celestialFight = CelestialFight::factory()->create([
            'monster_id' => $monster->id,
            'character_id' => $character->id,
            'conjured_at' => now(),
            'x_position' => $character->map->character_position_x,
            'y_position' => $character->map->character_position_y,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PUBLIC,
        ]);
        $characterInCelestialFight = CharacterInCelestialFight::factory()->create([
            'celestial_fight_id' => $celestialFight->id,
            'character_id' => $character->id,
            'character_max_health' => 100,
            'character_current_health' => 100,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = resolve(CelestialFightService::class)->fight($character, $celestialFight, $characterInCelestialFight, AttackTypeValue::ATTACK);

        $this->assertEquals(422, $response['status']);
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::FACTION_LOYALTY)->first());
    }

    public function testPctIsBlockedWhileExplorationIsRunning(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertFalse(resolve(PctService::class)->usePCT($character, true));
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::EXPLORING)->first());
    }

    public function testPctIsBlockedWhileDelveIsRunning(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertFalse(resolve(PctService::class)->usePCT($character, true));
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::DELVE)->first());
    }

    public function testPctIsBlockedWhileFactionLoyaltyIsRunning(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertFalse(resolve(PctService::class)->usePCT($character, true));
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::FACTION_LOYALTY)->first());
    }

    public function testStartingDelveIsBlockedWhileExplorationIsRunning(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/delve/' . $character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
                'pack_size' => 5,
            ]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::EXPLORING)->first());
    }

    public function testStartingFactionLoyaltyIsBlockedWhileExplorationIsRunning(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/faction-loyalty-automation/' . $character->id . '/start', [
                '_token' => csrf_token(),
                'attack_type' => AttackTypeValue::ATTACK,
            ]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::EXPLORING)->first());
    }

    public function testStartingExplorationIsBlockedWhileDelveIsRunning(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $character->map->game_map_id,
            ])
            ->getMonster();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/automation/' . $character->id . '/start', [
                '_token' => csrf_token(),
                'auto_attack_length' => 1,
                'move_down_the_list_every' => 10,
                'selected_monster_id' => $monster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::DELVE)->first());
    }

    public function testStartingExplorationIsBlockedWhileFactionLoyaltyIsRunning(): void
    {
        Queue::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $character->map->game_map_id,
            ])
            ->getMonster();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/automation/' . $character->id . '/start', [
                '_token' => csrf_token(),
                'auto_attack_length' => 1,
                'move_down_the_list_every' => 10,
                'selected_monster_id' => $monster->id,
                'attack_type' => AttackTypeValue::ATTACK,
            ]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::FACTION_LOYALTY)->first());
    }

    public function testCraftingIsBlockedWhileFactionLoyaltyIsRunning(): void
    {
        $craftingSkill = $this->createGameSkill([
            'name' => 'Weapon Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
            'max_level' => 400,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($craftingSkill, 10)
            ->getCharacter();
        $item = $this->createItem([
            'type' => ItemType::DAGGER->value,
            'crafting_type' => ItemType::DAGGER->value,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/craft/' . $character->id, [
                'item_to_craft' => $item->id,
                'type' => $item->crafting_type,
                'craft_for_npc' => false,
                'craft_for_event' => false,
            ], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You cannot do that while Faction Loyalty automation is running. Cancel it first.', $jsonData['message']);
    }

    public function testDirectManualFightExecutionIsBlockedWhileExplorationIsRunning(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = resolve(MonsterFightService::class)->fightMonster($character, AttackTypeValue::ATTACK);

        $this->assertEquals(422, $response['status']);
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::EXPLORING)->first());
    }

    public function testDirectManualFightExecutionIsBlockedWhileDelveIsRunning(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = resolve(MonsterFightService::class)->fightMonster($character, AttackTypeValue::ATTACK);

        $this->assertEquals(422, $response['status']);
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::DELVE)->first());
    }

    public function testDirectManualFightExecutionIsBlockedWhileFactionLoyaltyIsRunning(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = resolve(MonsterFightService::class)->fightMonster($character, AttackTypeValue::ATTACK);

        $this->assertEquals(422, $response['status']);
        $this->assertNotNull(CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::FACTION_LOYALTY)->first());
    }

    public function testCelestialConjuringIsBlockedWhileExplorationIsRunning(): void
    {
        $this->withoutMiddleware(IsCharacterExploring::class);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $character->map->game_map_id,
                'is_celestial_entity' => true,
            ])
            ->getMonster();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/conjure/' . $character->id, [
                'monster_id' => $monster->id,
                'type' => 'public',
            ], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You cannot do that while Exploration automation is running. Cancel it first.', $jsonData['message']);
        $this->assertNull(CelestialFight::where('character_id', $character->id)->first());
    }

    public function testCelestialConjuringIsBlockedWhileDelveIsRunning(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $character->map->game_map_id,
                'is_celestial_entity' => true,
            ])
            ->getMonster();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/conjure/' . $character->id, [
                'monster_id' => $monster->id,
                'type' => 'public',
            ], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You cannot do that while Delve automation is running. Cancel it first.', $jsonData['message']);
        $this->assertNull(CelestialFight::where('character_id', $character->id)->first());
    }

    public function testCelestialConjuringIsBlockedWhileFactionLoyaltyIsRunning(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = (new MonsterFactory)
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $character->map->game_map_id,
                'is_celestial_entity' => true,
            ])
            ->getMonster();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/conjure/' . $character->id, [
                'monster_id' => $monster->id,
                'type' => 'public',
            ], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You cannot do that while Faction Loyalty automation is running. Cancel it first.', $jsonData['message']);
        $this->assertNull(CelestialFight::where('character_id', $character->id)->first());
    }
}