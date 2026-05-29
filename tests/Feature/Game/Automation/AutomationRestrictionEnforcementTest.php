<?php

namespace Tests\Feature\Game\Automation;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\MapNameValue;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Battle\Services\CelestialFightService;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\Maps\Services\MovementService;
use App\Game\Maps\Services\PctService;
use App\Game\Maps\Services\SetSailService;
use App\Game\Maps\Services\TeleportService;
use App\Game\Maps\Services\WalkingService;
use App\Game\Skills\Values\SkillTypeValue;
use App\Game\Automation\Middleware\IsCharacterExploring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Monster\MonsterFactory;
use Tests\TestCase;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;

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
    use CreateFactionLoyalty, CreateGameSkill, CreateItem, CreateNpc, RefreshDatabase;

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

    public function testDirectMovementIsBlockedWhileDelveIsRunning(): void
    {
        Event::fake();

        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $walkingService = resolve(WalkingService::class);
        $walkingService->setCoordinatesToTravelTo(32, 16);

        $response = $walkingService->movePlayerToNewLocation($character);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals(16, $character->refresh()->map->character_position_x);
    }

    public function testEnterLocationIsBlockedWhileDelveIsRunning(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $blockedContext = resolve(AutomationRestrictionService::class)->blockedContext(
            $character,
            AutomationRestrictionService::ENTER_LOCATION,
            $location
        );

        $this->assertEquals('You cannot do that while Delve automation is running. Cancel it first.', $blockedContext['message']);
    }

    public function testTeleportIsBlockedWhileDelveIsRunning(): void
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

        $response = resolve(TeleportService::class)->teleport($character);

        $this->assertEquals(422, $response['status']);
    }

    public function testSetSailIsBlockedWhileDelveIsRunning(): void
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

        $response = resolve(SetSailService::class)->setSail($character);

        $this->assertEquals(422, $response['status']);
    }

    public function testTraverseIsBlockedWhileDelveIsRunning(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::HELL,
            'path' => 'hell.png',
            'default' => false,
            'can_traverse' => true,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = resolve(MovementService::class)->updateCharacterPlane($gameMap->id, $character);

        $this->assertEquals(422, $response['status']);
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

    public function testNpcFactionCraftingIsBlockedWhileExplorationIsRunning(): void
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
        $character->update([
            'gold' => 1000000,
        ]);
        $item = $this->createItem([
            'type' => ItemType::DAGGER->value,
            'crafting_type' => ItemType::DAGGER->value,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/craft/' . $character->id, [
                'item_to_craft' => $item->id,
                'type' => $item->crafting_type,
                'craft_for_npc' => true,
                'craft_for_event' => false,
            ], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You are currently doing Exploration. This action cannot be completed right now. Please cancel Exploration first.', $jsonData['message']);
    }

    public function testNpcFactionCraftingIsBlockedWhileDelveIsRunning(): void
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
        $character->update([
            'gold' => 1000000,
        ]);
        $item = $this->createItem([
            'type' => ItemType::DAGGER->value,
            'crafting_type' => ItemType::DAGGER->value,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/craft/' . $character->id, [
                'item_to_craft' => $item->id,
                'type' => $item->crafting_type,
                'craft_for_npc' => true,
                'craft_for_event' => false,
            ], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You are currently doing Delve. This action cannot be completed right now. Please cancel Delve first.', $jsonData['message']);
    }

    public function testRegularCraftingIsAllowedWhileExplorationIsRunning(): void
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
        $character->update([
            'gold' => 1000000,
        ]);
        $item = $this->createItem([
            'type' => ItemType::DAGGER->value,
            'crafting_type' => ItemType::DAGGER->value,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
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

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRegularCraftingIsAllowedWhileDelveIsRunning(): void
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
        $character->update([
            'gold' => 1000000,
        ]);
        $item = $this->createItem([
            'type' => ItemType::DAGGER->value,
            'crafting_type' => ItemType::DAGGER->value,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
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

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testFactionLoyaltyBountyIsBlockedWhileExplorationIsRunning(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $npc = $this->createNpc([
            'game_map_id' => $character->map->game_map_id,
        ]);
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

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/faction-loyalty-bounty/' . $character->id, [
                'monster_id' => $monster->id,
                'npc_id' => $npc->id,
                'attack_type' => 'attack',
            ], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You are currently doing Exploration. This action cannot be completed right now. Please cancel Exploration first.', $jsonData['message']);
    }

    public function testFactionLoyaltyBountyIsBlockedWhileDelveIsRunning(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $npc = $this->createNpc([
            'game_map_id' => $character->map->game_map_id,
        ]);
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
            ->call('POST', '/api/faction-loyalty-bounty/' . $character->id, [
                'monster_id' => $monster->id,
                'npc_id' => $npc->id,
                'attack_type' => 'attack',
            ], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);
        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('You are currently doing Delve. This action cannot be completed right now. Please cancel Delve first.', $jsonData['message']);
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
