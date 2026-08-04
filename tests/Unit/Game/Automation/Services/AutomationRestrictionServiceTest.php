<?php

namespace Tests\Unit\Game\Automation\Services;

use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Automation\Values\AutomationType;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateLocation;

class AutomationRestrictionServiceTest extends TestCase
{
    use CreateBatchCrafting, CreateCharacterAutomation, CreateLocation, RefreshDatabase;

    private AutomationRestrictionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(AutomationRestrictionService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->service);
    }

    public function test_no_active_automation_allows_manual_fighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::MANUAL_FIGHTING));
    }

    public function test_no_active_automation_allows_celestial_fighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::CELESTIAL_FIGHTING));
    }

    public function test_no_active_automation_allows_pct(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::PCT));
    }

    public function test_no_active_automation_allows_directional_movement(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::DIRECTIONAL_MOVEMENT));
    }

    public function test_no_active_automation_allows_teleport(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::TELEPORT));
    }

    public function test_no_active_automation_allows_set_sail(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::SET_SAIL));
    }

    public function test_no_active_automation_allows_traverse(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::TRAVERSE));
    }

    public function test_no_active_automation_allows_enter_location(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION));
    }

    public function test_no_active_automation_allows_start_delve(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_DELVE));
    }

    public function test_no_active_automation_allows_start_exploration(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_EXPLORATION));
    }

    public function test_no_active_automation_allows_start_faction_loyalty(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_FACTION_LOYALTY));
    }

    public function test_no_active_automation_allows_start_crafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_CRAFTING));
    }

    public function test_no_active_automation_allows_start_item_crafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_ITEM_CRAFTING));
    }

    public function test_faction_loyalty_blocks_start_delve(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_DELVE));
    }

    public function test_faction_loyalty_blocks_start_exploration(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_EXPLORATION));
    }

    public function test_faction_loyalty_blocks_manual_fighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::MANUAL_FIGHTING));
    }

    public function test_faction_loyalty_allows_start_crafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_CRAFTING));
    }

    public function test_faction_loyalty_blocks_start_item_crafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_ITEM_CRAFTING));
    }

    public function test_faction_loyalty_blocks_pct(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::PCT));
    }

    public function test_faction_loyalty_blocks_celestial_fighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::CELESTIAL_FIGHTING));
    }

    public function test_faction_loyalty_allows_directional_movement(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::DIRECTIONAL_MOVEMENT));
    }

    public function test_delve_blocks_start_exploration(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_EXPLORATION));
    }

    public function test_delve_blocks_manual_fighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::MANUAL_FIGHTING));
    }

    public function test_delve_blocks_start_faction_loyalty(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_FACTION_LOYALTY));
    }

    public function test_delve_blocks_pct(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::PCT));
    }

    public function test_delve_blocks_directional_movement(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::DIRECTIONAL_MOVEMENT));
    }

    public function test_delve_blocks_enter_location(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION));
    }

    public function test_delve_blocks_teleport(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::TELEPORT));
    }

    public function test_delve_blocks_set_sail(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::SET_SAIL));
    }

    public function test_delve_blocks_traverse(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::TRAVERSE));
    }

    public function test_delve_blocks_celestial_conjuring(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::CELESTIAL_CONJURING));
    }

    public function test_delve_blocks_celestial_fighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::CELESTIAL_FIGHTING));
    }

    public function test_delve_allows_start_crafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_CRAFTING));
    }

    public function test_delve_allows_start_item_crafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_ITEM_CRAFTING));
    }

    public function test_exploration_blocks_start_delve(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_DELVE));
    }

    public function test_exploration_blocks_start_faction_loyalty(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_FACTION_LOYALTY));
    }

    public function test_exploration_blocks_manual_fighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::MANUAL_FIGHTING));
    }

    public function test_exploration_blocks_pct(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::PCT));
    }

    public function test_exploration_blocks_celestial_fighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::CELESTIAL_FIGHTING));
    }

    public function test_exploration_blocks_teleport(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::TELEPORT));
    }

    public function test_exploration_blocks_set_sail(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::SET_SAIL));
    }

    public function test_exploration_blocks_traverse(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::TRAVERSE));
    }

    public function test_exploration_allows_start_crafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_CRAFTING));
    }

    public function test_exploration_allows_start_item_crafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_ITEM_CRAFTING));
    }

    public function test_exploration_started_in_special_location_blocks_directional_movement(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => true,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::DIRECTIONAL_MOVEMENT));
    }

    public function test_exploration_started_in_special_location_blocks_entering_location(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => null,

        ]);

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => true,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION, $location));
    }

    public function test_exploration_started_in_regular_context_allows_directional_movement(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::DIRECTIONAL_MOVEMENT));
    }

    public function test_exploration_started_in_regular_context_allows_entering_regular_location(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $regularLocation = $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => null,

            'is_port' => false,
        ]);

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION, $regularLocation));
    }

    public function test_exploration_started_in_regular_context_allows_entering_port(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $port = $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => 48,
            'y' => 16,
            'type' => null,

            'is_port' => true,
        ]);

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION, $port));
    }

    public function test_exploration_started_in_regular_context_blocks_gold_mine(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::GOLD_MINES->value,
        ]);

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION, $location));
    }

    public function test_exploration_started_in_regular_context_blocks_purgatory_dungeon(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::PURGATORY_DUNGEONS->value,
        ]);

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION, $location));
    }

    public function test_exploration_started_in_regular_context_blocks_purgatory_smith_house(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::PURGATORY_SMITH_HOUSE->value,
        ]);

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::ENTER_LOCATION, $location));
    }

    public function test_blocked_context_returns_automation_message_and_automation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $automation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $context = $this->service->blockedContext($character, AutomationRestrictionService::MANUAL_FIGHTING);

        $this->assertEquals($automation->id, $context['automation']->id);
        $this->assertEquals('Exploration', $context['automation_name']);
        $this->assertEquals('You cannot do that while Exploration automation is running. Cancel it first.', $context['message']);
    }

    public function test_active_automation_selects_newest_active_automation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
            'started_in_special_location' => false,
        ]);

        $newestAutomation = $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(3),
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $activeAutomation = $this->service->activeAutomation($character);

        $this->assertEquals($newestAutomation->id, $activeAutomation->id);
    }

    public function test_batch_crafting_blocks_start_faction_loyalty(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_FACTION_LOYALTY));
    }

    public function test_batch_crafting_blocks_start_item_crafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_ITEM_CRAFTING));
    }

    public function test_batch_crafting_allows_start_crafting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_CRAFTING));
    }

    public function test_batch_crafting_allows_manual_fighting(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::MANUAL_FIGHTING));
    }

    public function test_completed_batch_crafting_does_not_block_start_faction_loyalty(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'completed_at' => now(),
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_FACTION_LOYALTY));
    }

    public function test_dismissed_batch_crafting_does_not_block_start_faction_loyalty(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'completed_at' => now(),
            'panel_dismissed_at' => now(),
        ]);

        $this->assertFalse($this->service->isBlocked($character, AutomationRestrictionService::START_FACTION_LOYALTY));
    }
}
