<?php

namespace Tests\Console;

use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\FactionLoyaltyNpcTask;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Kingdom;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\SetSlot;
use App\Flare\Models\SmeltingProgress;
use App\Flare\Models\SuggestionAndBugs;
use App\Flare\Models\UserLoginDuration;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Messages\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateMessage;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateSuggestionAndBugs;
use Tests\Traits\CreateUser;

class CleanDanglingCharacterDataTest extends TestCase
{
    use CreateCelestials,
        CreateGameMap,
        CreateItem,
        CreateKingdom,
        CreateMessage,
        CreateMonster,
        CreateSuggestionAndBugs,
        CreateUser,
        RefreshDatabase;

    private const ORPHAN_CHARACTER_ID = 99999;

    private const ORPHAN_USER_ID = 99999;

    private function withoutFkChecks(callable $callback): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $callback();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function test_dry_run_does_not_delete_orphaned_character_records(): void
    {
        $this->withoutFkChecks(function () {
            ExplorationLog::factory()->create(['character_id' => self::ORPHAN_CHARACTER_ID]);
            SmeltingProgress::factory()->create(['character_id' => self::ORPHAN_CHARACTER_ID]);
        });

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data'));

        $this->assertEquals(1, ExplorationLog::where('character_id', self::ORPHAN_CHARACTER_ID)->count());
        $this->assertEquals(1, SmeltingProgress::where('character_id', self::ORPHAN_CHARACTER_ID)->count());
    }

    public function test_dry_run_does_not_null_suggestion_character_ids(): void
    {
        $this->withoutFkChecks(function () {
            SuggestionAndBugs::factory()->create(['character_id' => self::ORPHAN_CHARACTER_ID]);
        });

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data'));

        $suggestion = SuggestionAndBugs::where('character_id', self::ORPHAN_CHARACTER_ID)->first();
        $this->assertNotNull($suggestion);
        $this->assertEquals(self::ORPHAN_CHARACTER_ID, $suggestion->character_id);
    }

    public function test_apply_deletes_orphaned_exploration_logs(): void
    {
        $this->withoutFkChecks(function () {
            ExplorationLog::factory()->create(['character_id' => self::ORPHAN_CHARACTER_ID]);
        });

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertEquals(0, ExplorationLog::where('character_id', self::ORPHAN_CHARACTER_ID)->count());
    }

    public function test_apply_deletes_orphaned_smelting_progress(): void
    {
        $this->withoutFkChecks(function () {
            SmeltingProgress::factory()->create(['character_id' => self::ORPHAN_CHARACTER_ID]);
        });

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertEquals(0, SmeltingProgress::where('character_id', self::ORPHAN_CHARACTER_ID)->count());
    }

    public function test_apply_deletes_orphaned_character_in_celestial_fights(): void
    {
        $monster = $this->createMonster(['is_celestial_entity' => true]);
        $celestialFight = $this->createCelestialFight([
            'monster_id' => $monster->id,
            'character_id' => null,
            'conjured_at' => now(),
            'x_position' => 16,
            'y_position' => 16,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'current_health' => 1000,
            'max_health' => 1000,
            'type' => CelestialConjureType::PUBLIC,
        ]);

        $this->withoutFkChecks(function () use ($celestialFight) {
            $this->createCharacterInCelestialFight([
                'character_id' => self::ORPHAN_CHARACTER_ID,
                'celestial_fight_id' => $celestialFight->id,
                'character_max_health' => 1000,
                'character_current_health' => 500,
            ]);
        });

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertEquals(0, CharacterInCelestialFight::where('character_id', self::ORPHAN_CHARACTER_ID)->count());
    }

    public function test_apply_deletes_orphaned_delve_explorations(): void
    {
        $this->withoutFkChecks(function () {
            DelveExploration::factory()->create(['character_id' => self::ORPHAN_CHARACTER_ID]);
        });

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertEquals(0, DelveExploration::where('character_id', self::ORPHAN_CHARACTER_ID)->count());
    }

    public function test_apply_nulls_suggestion_character_ids_for_missing_characters(): void
    {
        $this->withoutFkChecks(function () {
            SuggestionAndBugs::factory()->create(['character_id' => self::ORPHAN_CHARACTER_ID]);
        });

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertEquals(0, SuggestionAndBugs::where('character_id', self::ORPHAN_CHARACTER_ID)->count());
        $suggestion = SuggestionAndBugs::whereNull('character_id')->first();
        $this->assertNotNull($suggestion);
    }

    public function test_apply_deletes_orphaned_user_login_durations(): void
    {
        $this->withoutFkChecks(function () {
            UserLoginDuration::factory()->create([
                'user_id' => self::ORPHAN_USER_ID,
                'logged_in_at' => now(),
                'last_heart_beat' => now(),
                'last_activity' => now(),
            ]);
        });

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertEquals(0, UserLoginDuration::where('user_id', self::ORPHAN_USER_ID)->count());
    }

    public function test_apply_does_not_delete_records_belonging_to_existing_characters(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        ExplorationLog::factory()->create(['character_id' => $character->id]);

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertEquals(1, ExplorationLog::where('character_id', $character->id)->count());
    }

    public function test_messages_are_never_deleted(): void
    {
        $user = $this->createUser();
        $message = $this->createMessage($user);

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertNotNull(Message::find($message->id));
    }

    public function test_suggestion_records_are_never_deleted(): void
    {
        $suggestion = SuggestionAndBugs::factory()->create(['character_id' => null]);

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertNotNull(SuggestionAndBugs::find($suggestion->id));
    }

    public function test_kingdoms_are_never_deleted(): void
    {
        $gameMap = $this->createGameMap();
        $kingdom = $this->createKingdom([
            'character_id' => null,
            'game_map_id' => $gameMap->id,
        ]);

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertNotNull(Kingdom::find($kingdom->id));
    }

    public function test_items_are_never_deleted(): void
    {
        $item = $this->createItem();

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertNotNull(Item::find($item->id));
    }

    public function test_dry_run_reports_but_does_not_delete_orphan_market_board_row(): void
    {
        $item = $this->createItem();

        $this->withoutFkChecks(function () use ($item) {
            MarketBoard::create([
                'character_id' => self::ORPHAN_CHARACTER_ID,
                'item_id' => $item->id,
                'listed_price' => 100,
            ]);
        });

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data'));

        $this->assertEquals(1, MarketBoard::where('character_id', self::ORPHAN_CHARACTER_ID)->count());
    }

    public function test_apply_deletes_orphan_market_board_row(): void
    {
        $item = $this->createItem();

        $this->withoutFkChecks(function () use ($item) {
            MarketBoard::create([
                'character_id' => self::ORPHAN_CHARACTER_ID,
                'item_id' => $item->id,
                'listed_price' => 100,
            ]);
        });

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertEquals(0, MarketBoard::where('character_id', self::ORPHAN_CHARACTER_ID)->count());
    }

    public function test_dry_run_does_not_delete_login_duration_for_user_without_character(): void
    {
        $user = $this->createUser();
        UserLoginDuration::factory()->create([
            'user_id' => $user->id,
            'logged_in_at' => now(),
            'last_heart_beat' => now(),
            'last_activity' => now(),
        ]);

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data'));

        $this->assertEquals(1, UserLoginDuration::where('user_id', $user->id)->count());
    }

    public function test_apply_deletes_login_duration_for_user_without_character(): void
    {
        $user = $this->createUser();
        UserLoginDuration::factory()->create([
            'user_id' => $user->id,
            'logged_in_at' => now(),
            'last_heart_beat' => now(),
            'last_activity' => now(),
        ]);

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertEquals(0, UserLoginDuration::where('user_id', $user->id)->count());
    }

    public function test_apply_removes_orphan_child_rows_tied_to_missing_character_parents(): void
    {
        $item = $this->createItem();

        $this->withoutFkChecks(function () use ($item) {
            InventorySlot::create([
                'inventory_id' => self::ORPHAN_CHARACTER_ID,
                'item_id' => $item->id,
            ]);

            SetSlot::create([
                'inventory_set_id' => self::ORPHAN_CHARACTER_ID,
                'item_id' => $item->id,
                'equipped' => false,
            ]);

            DB::table('gem_bag_slots')->insert([
                'gem_bag_id' => self::ORPHAN_CHARACTER_ID,
                'gem_id' => self::ORPHAN_CHARACTER_ID,
                'amount' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('character_class_ranks_weapon_masteries')->insert([
                'character_class_rank_id' => self::ORPHAN_CHARACTER_ID,
                'weapon_type' => 'hammer',
                'current_xp' => 0,
                'required_xp' => 100,
                'level' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('faction_loyalty_npcs')->insert([
                'faction_loyalty_id' => self::ORPHAN_CHARACTER_ID,
                'npc_id' => self::ORPHAN_CHARACTER_ID,
                'current_level' => 1,
                'max_level' => 25,
                'next_level_fame' => 1000,
                'currently_helping' => false,
                'kingdom_item_defence_bonus' => 0.025,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('faction_loyalty_npc_tasks')->insert([
                'faction_loyalty_npc_id' => self::ORPHAN_CHARACTER_ID,
                'faction_loyalty_id' => self::ORPHAN_CHARACTER_ID,
                'fame_tasks' => '[]',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('global_event_crafting_inventory_slots')->insert([
                'global_event_crafting_inventory_id' => self::ORPHAN_CHARACTER_ID,
                'item_id' => $item->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertEquals(0, InventorySlot::where('inventory_id', self::ORPHAN_CHARACTER_ID)->count());
        $this->assertEquals(0, SetSlot::where('inventory_set_id', self::ORPHAN_CHARACTER_ID)->count());
        $this->assertEquals(0, DB::table('gem_bag_slots')->where('gem_bag_id', self::ORPHAN_CHARACTER_ID)->count());
        $this->assertEquals(0, DB::table('character_class_ranks_weapon_masteries')->where('character_class_rank_id', self::ORPHAN_CHARACTER_ID)->count());
        $this->assertEquals(0, FactionLoyaltyNpc::where('faction_loyalty_id', self::ORPHAN_CHARACTER_ID)->count());
        $this->assertEquals(0, FactionLoyaltyNpcTask::where('faction_loyalty_npc_id', self::ORPHAN_CHARACTER_ID)->count());
        $this->assertEquals(0, GlobalEventCraftingInventorySlot::where('global_event_crafting_inventory_id', self::ORPHAN_CHARACTER_ID)->count());
    }

    public function test_apply_deletes_faction_loyalty_npc_tasks_with_missing_faction_loyalty(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factionId = DB::table('factions')->insertGetId([
            'character_id' => $character->id,
            'game_map_id' => $character->map->game_map_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $factionLoyalty = FactionLoyalty::factory()->create([
            'character_id' => $character->id,
            'faction_id' => $factionId,
        ]);
        $npcId = DB::table('npcs')->insertGetId([
            'game_map_id' => $character->map->game_map_id,
            'name' => 'Missing Faction Loyalty Test',
            'real_name' => 'Missing Faction Loyalty Test',
            'type' => 0,
            'x_position' => 1,
            'y_position' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $factionLoyaltyNpc = FactionLoyaltyNpc::factory()->create([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npcId,
        ]);

        $this->withoutFkChecks(function () use ($factionLoyaltyNpc) {
            FactionLoyaltyNpcTask::factory()->create([
                'faction_loyalty_id' => self::ORPHAN_CHARACTER_ID,
                'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            ]);
        });

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertEquals(0, FactionLoyaltyNpcTask::where('faction_loyalty_id', self::ORPHAN_CHARACTER_ID)->count());
    }

    public function test_apply_deletes_faction_loyalty_npc_tasks_with_missing_faction_loyalty_npc(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $factionId = DB::table('factions')->insertGetId([
            'character_id' => $character->id,
            'game_map_id' => $character->map->game_map_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $factionLoyalty = FactionLoyalty::factory()->create([
            'character_id' => $character->id,
            'faction_id' => $factionId,
        ]);

        $this->withoutFkChecks(function () use ($factionLoyalty) {
            FactionLoyaltyNpcTask::factory()->create([
                'faction_loyalty_id' => $factionLoyalty->id,
                'faction_loyalty_npc_id' => self::ORPHAN_CHARACTER_ID,
            ]);
        });

        $this->assertEquals(0, $this->artisan('cleanup:dangling-character-data', ['--apply' => true]));

        $this->assertEquals(0, FactionLoyaltyNpcTask::where('faction_loyalty_npc_id', self::ORPHAN_CHARACTER_ID)->count());
    }
}
