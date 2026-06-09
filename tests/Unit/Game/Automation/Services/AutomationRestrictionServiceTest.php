<?php

namespace Tests\Unit\Game\Automation\Services;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Location;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\LocationType;
use App\Game\Automation\Services\AutomationRestrictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class AutomationRestrictionServiceTest extends TestCase
{
    use RefreshDatabase;

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

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
        ]);

        $this->assertTrue($this->service->isBlocked($character, AutomationRestrictionService::START_DELVE));
    }

    public function test_faction_loyalty_blocks_start_exploration(): void
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

    public function test_faction_loyalty_blocks_manual_fighting(): void
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

    public function test_faction_loyalty_allows_start_crafting(): void
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

    public function test_faction_loyalty_blocks_start_item_crafting(): void
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

    public function test_faction_loyalty_blocks_pct(): void
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

    public function test_faction_loyalty_blocks_celestial_fighting(): void
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

    public function test_faction_loyalty_allows_directional_movement(): void
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

    public function test_delve_blocks_start_exploration(): void
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

    public function test_delve_blocks_manual_fighting(): void
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

    public function test_delve_blocks_start_faction_loyalty(): void
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

    public function test_delve_blocks_pct(): void
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

    public function test_delve_blocks_directional_movement(): void
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

    public function test_delve_blocks_enter_location(): void
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

    public function test_delve_blocks_teleport(): void
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

    public function test_delve_blocks_set_sail(): void
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

    public function test_delve_blocks_traverse(): void
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

    public function test_delve_blocks_celestial_conjuring(): void
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

    public function test_delve_blocks_celestial_fighting(): void
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

    public function test_delve_allows_start_crafting(): void
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

    public function test_delve_allows_start_item_crafting(): void
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

    public function test_exploration_blocks_start_delve(): void
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

    public function test_exploration_blocks_start_faction_loyalty(): void
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

    public function test_exploration_blocks_manual_fighting(): void
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

    public function test_exploration_blocks_pct(): void
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

    public function test_exploration_blocks_celestial_fighting(): void
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

    public function test_exploration_blocks_teleport(): void
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

    public function test_exploration_blocks_set_sail(): void
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

    public function test_exploration_blocks_traverse(): void
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

    public function test_exploration_allows_start_crafting(): void
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

    public function test_exploration_allows_start_item_crafting(): void
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

    public function test_exploration_started_in_special_location_blocks_directional_movement(): void
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

    public function test_exploration_started_in_special_location_blocks_entering_location(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => null,

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

    public function test_exploration_started_in_regular_context_allows_directional_movement(): void
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

    public function test_exploration_started_in_regular_context_allows_entering_regular_location(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $regularLocation = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => null,

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

    public function test_exploration_started_in_regular_context_allows_entering_port(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $port = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 48,
            'y' => 16,
            'type' => null,

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

    public function test_exploration_started_in_regular_context_blocks_gold_mine(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::GOLD_MINES,

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

    public function test_exploration_started_in_regular_context_blocks_purgatory_dungeon(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::PURGATORY_DUNGEONS,

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

    public function test_exploration_started_in_regular_context_blocks_purgatory_smith_house(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $location = Location::factory()->create([
            'game_map_id' => $character->map->game_map_id,
            'x' => 32,
            'y' => 16,
            'type' => LocationType::PURGATORY_SMITH_HOUSE,

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

    public function test_blocked_context_returns_automation_message_and_automation(): void
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

    public function test_active_automation_selects_newest_active_automation(): void
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
