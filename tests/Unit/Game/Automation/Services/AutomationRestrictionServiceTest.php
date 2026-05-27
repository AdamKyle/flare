<?php

namespace Tests\Unit\Game\Automation\Services;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Location;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\LocationEffectValue;
use App\Flare\Values\LocationType;
use App\Game\Automation\Services\AutomationRestrictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class AutomationRestrictionServiceTest extends TestCase
{
    use RefreshDatabase;

    private AutomationRestrictionService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(AutomationRestrictionService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->service);
    }

    public function testNoActiveAutomationAllowsManualFighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::MANUAL_FIGHTING));
    }

    public function testNoActiveAutomationAllowsCelestialFighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::CELESTIAL_FIGHTING));
    }

    public function testNoActiveAutomationAllowsPct(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::PCT));
    }

    public function testNoActiveAutomationAllowsDirectionalMovement(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::DIRECTIONAL_MOVEMENT));
    }

    public function testNoActiveAutomationAllowsTeleport(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::TELEPORT));
    }

    public function testNoActiveAutomationAllowsSetSail(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::SET_SAIL));
    }

    public function testNoActiveAutomationAllowsTraverse(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::TRAVERSE));
    }

    public function testNoActiveAutomationAllowsEnterLocation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION));
    }

    public function testNoActiveAutomationAllowsStartDelve(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_DELVE));
    }

    public function testNoActiveAutomationAllowsStartExploration(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_EXPLORATION));
    }

    public function testNoActiveAutomationAllowsStartFactionLoyalty(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_FACTION_LOYALTY));
    }

    public function testNoActiveAutomationAllowsStartCrafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_CRAFTING));
    }

    public function testNoActiveAutomationAllowsStartItemCrafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_ITEM_CRAFTING));
    }

    public function testFactionLoyaltyBlocksStartDelve(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_DELVE));
    }

    public function testFactionLoyaltyBlocksStartExploration(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_EXPLORATION));
    }

    public function testFactionLoyaltyBlocksManualFighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::MANUAL_FIGHTING));
    }

    public function testFactionLoyaltyAllowsStartCrafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_CRAFTING));
    }

    public function testFactionLoyaltyBlocksStartItemCrafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_ITEM_CRAFTING));
    }

    public function testFactionLoyaltyBlocksPct(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::PCT));
    }

    public function testFactionLoyaltyBlocksCelestialFighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::CELESTIAL_FIGHTING));
    }

    public function testFactionLoyaltyAllowsDirectionalMovement(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::DIRECTIONAL_MOVEMENT));
    }

    public function testDelveBlocksStartExploration(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_EXPLORATION));
    }

    public function testDelveBlocksManualFighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::MANUAL_FIGHTING));
    }

    public function testDelveBlocksStartFactionLoyalty(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_FACTION_LOYALTY));
    }

    public function testDelveBlocksPct(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::PCT));
    }

    public function testDelveBlocksDirectionalMovement(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::DIRECTIONAL_MOVEMENT));
    }

    public function testDelveBlocksEnterLocation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION));
    }

    public function testDelveBlocksTeleport(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::TELEPORT));
    }

    public function testDelveBlocksSetSail(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::SET_SAIL));
    }

    public function testDelveBlocksTraverse(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::TRAVERSE));
    }

    public function testDelveBlocksCelestialConjuring(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::CELESTIAL_CONJURING));
    }

    public function testDelveBlocksCelestialFighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::CELESTIAL_FIGHTING));
    }

    public function testDelveAllowsStartCrafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_CRAFTING));
    }

    public function testDelveAllowsStartItemCrafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_ITEM_CRAFTING));
    }

    public function testExplorationBlocksStartDelve(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_DELVE));
    }

    public function testExplorationBlocksStartFactionLoyalty(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_FACTION_LOYALTY));
    }

    public function testExplorationBlocksManualFighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::MANUAL_FIGHTING));
    }

    public function testExplorationBlocksPct(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::PCT));
    }

    public function testExplorationBlocksCelestialFighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::CELESTIAL_FIGHTING));
    }

    public function testExplorationBlocksTeleport(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::TELEPORT));
    }

    public function testExplorationBlocksSetSail(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::SET_SAIL));
    }

    public function testExplorationBlocksTraverse(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::TRAVERSE));
    }

    public function testExplorationAllowsStartCrafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_CRAFTING));
    }

    public function testExplorationAllowsStartItemCrafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_ITEM_CRAFTING));
    }

    public function testExplorationStartedInSpecialLocationBlocksDirectionalMovement(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => true,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::DIRECTIONAL_MOVEMENT));
    }

    public function testExplorationStartedInSpecialLocationBlocksEnteringLocation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => null,
            'enemy_strength_type' => null,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => true,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION, $location));
    }

    public function testExplorationStartedInRegularContextAllowsDirectionalMovement(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::DIRECTIONAL_MOVEMENT));
    }

    public function testExplorationStartedInRegularContextAllowsEnteringRegularLocation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $regularLocation = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => null,
            'enemy_strength_type' => null,
            'is_port' => false,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION, $regularLocation));
    }

    public function testExplorationStartedInRegularContextAllowsEnteringPort(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $port = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 48,
            'y' => 16,
            'type' => null,
            'enemy_strength_type' => null,
            'is_port' => true,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION, $port));
    }

    public function testExplorationStartedInRegularContextBlocksGoldMine(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::GOLD_MINES,
            'enemy_strength_type' => null,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION, $location));
    }

    public function testExplorationStartedInRegularContextBlocksPurgatoryDungeon(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::PURGATORY_DUNGEONS,
            'enemy_strength_type' => null,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION, $location));
    }

    public function testExplorationStartedInRegularContextBlocksPurgatorySmithHouse(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::PURGATORY_SMITH_HOUSE,
            'enemy_strength_type' => null,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION, $location));
    }

    public function testExplorationStartedInRegularContextBlocksEnemyStrengthModifyingLocation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => null,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION, $location));
    }

    public function testBlockedContextReturnsAutomationMessageAndAutomation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $context = $this->service->blockedContext($character, AutomationRestrictionService::MANUAL_FIGHTING);

        $this->assertEquals($automation->id, $context['automation']->id);
        $this->assertEquals('Exploration', $context['automation_name']);
        $this->assertEquals('You cannot do that while Exploration automation is running. Cancel it first.', $context['message']);
    }

    public function testActiveAutomationSelectsNewestActiveAutomation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $newestAutomation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $activeAutomation = $this->service->activeAutomation($character);

        $this->assertEquals($newestAutomation->id, $activeAutomation->id);
    }
}