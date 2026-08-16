<?php

namespace Tests\Unit\Game\Character\CharacterSheet\Transformers;

use App\Game\Automation\Values\AutomationType;
use App\Game\Character\CharacterSheet\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Maps\Values\LocationBasedCraftingOptions;
use App\Game\Maps\Values\MapName;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateDelveExploration;
use Tests\Traits\CreateFactionLoyaltyAutomationWarning;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateRole;

class CharacterSheetBaseInfoTransformerTest extends TestCase
{
    use CreateCharacterAutomation, CreateDelveExploration, CreateFactionLoyaltyAutomationWarning, CreateGameMap, CreateItem, CreateRole, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterSheetBaseInfoTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
        $this->transformer = resolve(CharacterSheetBaseInfoTransformer::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        $this->character = null;
        $this->transformer = null;

        parent::tearDown();
    }

    public function test_location_based_crafting_options_are_mapped_onto_character_sheet(): void
    {
        $gameMap = $this->createGameMap(['name' => MapName::PURGATORY->value, 'default' => false]);

        $character = $this->character->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $data = $this->transformer->transform($character);

        $locationBasedCraftingOptions = LocationBasedCraftingOptions::fromCharacter($character);

        $this->assertSame($locationBasedCraftingOptions->toCharacterSheetArray(), [
            'can_use_work_bench' => $data['can_use_work_bench'],
            'can_access_queen' => $data['can_access_queen'],
            'can_access_labyrinth_oracle' => $data['can_access_labyrinth_oracle'],
            'can_access_seer_camp' => $data['can_access_seer_camp'],
        ]);
    }

    public function test_null_crafting_timestamp_produces_zero_remaining_timeout(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $character->update(['can_craft_again_at' => null]);

        $data = $this->transformer->transform($character->refresh());

        $this->assertSame(0, $data['can_craft_again_at']);
    }

    public function test_expired_crafting_timestamp_produces_zero_remaining_timeout(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $character->update(['can_craft_again_at' => now()->subMinutes(5)]);

        $data = $this->transformer->transform($character->refresh());

        $this->assertSame(0, $data['can_craft_again_at']);
    }

    public function test_future_crafting_timestamp_produces_the_factual_remaining_duration(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $character = $this->character->givePlayerLocation()->getCharacter();

        $character->update(['can_craft_again_at' => now()->addSeconds(120)]);

        $data = $this->transformer->transform($character->refresh());

        $this->assertSame(120, $data['can_craft_again_at']);
    }

    public function test_active_exploration_automation_is_reported_with_its_name_and_timer(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $character = $this->character->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING->value,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(90),
        ]);

        $data = $this->transformer->transform($character->refresh());

        $this->assertSame('Exploration', $data['active_automation']['name']);
        $this->assertSame(90, $data['automation_completed_at']);
        $this->assertFalse($data['is_delve_running']);
    }

    public function test_active_delve_automation_is_reported_and_marks_delve_as_running(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE->value,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $data = $this->transformer->transform($character->refresh());

        $this->assertSame('Delve', $data['active_automation']['name']);
        $this->assertTrue($data['is_delve_running']);
    }

    public function test_active_faction_loyalty_automation_is_reported_and_marks_it_as_running(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY->value,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $data = $this->transformer->transform($character->refresh());

        $this->assertSame('Faction Loyalty', $data['active_automation']['name']);
        $this->assertTrue($data['is_faction_loyalty_automation_running']);
    }

    public function test_no_active_automation_reports_zero_time_left_and_a_null_active_automation(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $data = $this->transformer->transform($character);

        $this->assertNull($data['active_automation']);
        $this->assertSame(0, $data['automation_completed_at']);
    }

    public function test_is_delve_visible_when_a_completed_and_undismissed_delve_exploration_exists(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $this->createDelveExploration([
            'character_id' => $character->id,
            'completed_at' => now(),
            'panel_dismissed_at' => null,
        ]);

        $data = $this->transformer->transform($character->refresh());

        $this->assertTrue($data['is_delve_visible']);
    }

    public function test_is_delve_visible_is_false_when_no_delve_exploration_exists(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $data = $this->transformer->transform($character);

        $this->assertFalse($data['is_delve_visible']);
    }

    public function test_can_set_delve_pack_is_true_when_the_character_holds_the_delve_pack_choice_item(): void
    {
        $item = $this->createItem(['effect' => ItemEffectType::DELVE_PACK_CHOICE->value]);

        $character = $this->character->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $data = $this->transformer->transform($character);

        $this->assertTrue($data['can_set_delve_pack']);
    }

    public function test_can_set_delve_pack_is_false_when_the_character_does_not_hold_the_item(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $data = $this->transformer->transform($character);

        $this->assertFalse($data['can_set_delve_pack']);
    }

    public function test_faction_loyalty_warning_notices_are_included_when_present(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $this->createFactionLoyaltyAutomationWarning([
            'character_id' => $character->id,
            'type' => 'bounty',
            'message' => 'Sample warning message.',
        ]);

        $data = $this->transformer->transform($character);

        $this->assertTrue($data['has_faction_loyalty_warning']);
        $this->assertCount(1, $data['faction_loyalty_warning_notices']);
        $this->assertSame('Sample warning message.', $data['faction_loyalty_warning_notices'][0]['message']);
    }

    public function test_no_faction_loyalty_warning_notices_when_none_exist(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $data = $this->transformer->transform($character);

        $this->assertFalse($data['has_faction_loyalty_warning']);
        $this->assertSame([], $data['faction_loyalty_warning_notices']);
    }

    public function test_pledged_faction_current_fame_tasks_exclude_bounty_tasks(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $character = (new FactionLoyaltyFactory)->setUp($character)->getCharacter();

        $data = $this->transformer->transform($character);

        $this->assertTrue($data['can_see_pledge_tab']);
        $this->assertNotNull($data['pledged_to_faction_id']);
        $this->assertNotEmpty($data['current_fame_tasks']);

        foreach ($data['current_fame_tasks'] as $task) {
            $this->assertNotSame('bounty', $task['type']);
        }
    }

    public function test_no_pledged_faction_reports_no_pledge_tab_and_no_fame_tasks(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $data = $this->transformer->transform($character);

        $this->assertFalse($data['can_see_pledge_tab']);
        $this->assertNull($data['pledged_to_faction_id']);
        $this->assertSame([], $data['current_fame_tasks']);
    }

    public function test_pledged_faction_with_no_actively_helped_npc_reports_no_fame_tasks(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();

        $factionLoyaltyFactory->getPledgedFactionLoyalty()->factionLoyaltyNpcs()->update(['currently_helping' => false]);

        $data = $this->transformer->transform($character->refresh());

        $this->assertTrue($data['can_see_pledge_tab']);
        $this->assertSame([], $data['current_fame_tasks']);
    }

    public function test_ignore_reductions_removes_map_based_stat_reduction(): void
    {
        $purgatoryMap = $this->createGameMap([
            'name' => MapName::PURGATORY->value,
            'character_attack_reduction' => 0.5,
        ]);

        $character = $this->character->givePlayerLocation(16, 16, $purgatoryMap)->getCharacter();

        $withReduction = $this->transformer->transform($character);

        $this->transformer->setIgnoreReductions(true);

        $withoutReduction = $this->transformer->transform($character);

        $this->assertGreaterThan($withReduction['str_modded'], $withoutReduction['str_modded']);
    }

    public function test_active_automation_of_an_unrecognized_type_is_reported_as_not_running(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $this->createCharacterAutomation([
            'character_id' => $character->id,
            'type' => 999,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $data = $this->transformer->transform($character->refresh());

        $this->assertNull($data['active_automation']);
        $this->assertSame(0, $data['automation_completed_at']);
    }

    public function test_is_admin_is_true_for_a_character_owned_by_an_admin_user(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $role = $this->createAdminRole();
        $character->user->assignRole($role->name);

        $data = $this->transformer->transform($character->refresh());

        $this->assertTrue($data['is_admin']);
    }

    public function test_is_admin_is_false_for_a_character_owned_by_a_non_admin_user(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $data = $this->transformer->transform($character);

        $this->assertFalse($data['is_admin']);
    }

    public function test_inventory_count_is_a_flat_object_without_a_data_envelope(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();

        $data = $this->transformer->transform($character);

        $this->assertArrayNotHasKey('data', $data['inventory_count']);
        $this->assertArrayHasKey('inventory_count', $data['inventory_count']);
        $this->assertArrayHasKey('inventory_max', $data['inventory_count']);
    }
}
