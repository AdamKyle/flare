<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Handlers;

use App\Flare\Models\Faction;
use App\Flare\Models\GameMap;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\Core\Values\FactionLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;
use Tests\Traits\CreateSkill;
use Tests\Traits\CreateUser;

class FactionHandlerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateItemAffix, CreateLocation, CreateMonster, CreateNpc, CreateQuest, CreateGuideQuest, CreateSkill, CreateUser, RefreshDatabase;

    private ?CharacterFactory $character = null;

    private ?Monster $monster = null;

    private ?FactionHandler $factionHandler = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem();

        $this->monster = $this->createMonster();

        $this->factionHandler = resolve(FactionHandler::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->monster = null;
        $this->factionHandler = null;
    }

    /**
     * @return int
     */
    private function getMaxFactionLevel(): int
    {
        foreach (range(0, 500) as $candidate) {
            if (FactionLevel::isMaxLevel($candidate)) {
                return $candidate;
            }
        }

        $this->fail('Unable to determine max faction level.');

        return 0;
    }

    /**
     * @param array $options
     * @return void
     */
    private function createExploringAutomation(array $options): void
    {
        $this->assertNotNull($this->character);

        $this->character->assignAutomation($options);
    }


    public function test_award_faction_points_from_batch_returns_when_amount_is_zero(): void
    {
        $character = $this->character->getCharacter();

        $this->factionHandler->awardFactionPointsFromBatch($character, 0);

        $this->assertTrue(true);
    }

    public function test_award_faction_points_from_batch_returns_when_in_purgatory(): void
    {
        $purgatoryMap = $this->createGameMap([
            'name' => 'Purgatory',
        ]);

        $this->character->assignFactionSystem();

        $character = $this->character->getCharacter();

        Map::where('character_id', $character->id)->update([
            'game_map_id' => $purgatoryMap->id,
        ]);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $purgatoryMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 1;
        $faction->current_points = 0;
        $faction->points_needed = 100;
        $faction->maxed = false;
        $faction->save();

        $this->factionHandler->awardFactionPointsFromBatch($character->refresh(), 1000);

        $faction = Faction::where('character_id', $character->id)->where('game_map_id', $purgatoryMap->id)->first();

        $this->assertNotNull($faction);
        $this->assertSame(0, $faction->current_points);
        $this->assertSame(1, $faction->current_level);
        $this->assertFalse((bool) $faction->maxed);
    }

    public function test_award_faction_points_from_batch_does_nothing_when_no_faction_exists(): void
    {
        $character = $this->character->getCharacter();

        $character->factions()->delete();

        $this->factionHandler->awardFactionPointsFromBatch($character, 500);

        $this->assertNull(Faction::where('character_id', $character->id)->first());
    }

    public function test_award_faction_points_from_batch_does_nothing_when_faction_is_maxed(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 3;
        $faction->current_points = 0;
        $faction->points_needed = 100;
        $faction->maxed = true;
        $faction->save();

        $this->factionHandler->awardFactionPointsFromBatch($character, 500);

        $faction = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($faction);
        $this->assertSame(0, $faction->current_points);
        $this->assertSame(3, $faction->current_level);
        $this->assertTrue((bool) $faction->maxed);
    }

    public function test_award_faction_points_from_batch_applies_points_without_leveling_up(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 1;
        $faction->current_points = 10;
        $faction->points_needed = 200;
        $faction->maxed = false;
        $faction->save();

        $this->factionHandler->awardFactionPointsFromBatch($character, 50);

        $faction = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($faction);
        $this->assertSame(60, $faction->current_points);
        $this->assertSame(1, $faction->current_level);
        $this->assertFalse((bool) $faction->maxed);
    }

    public function test_award_faction_points_from_batch_levels_up_and_carries_over_remaining_points(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $this->createItemAffix([
            'type' => 'prefix',
            'cost' => 1,
            'affix_type' => 7,
        ]);

        $this->createItemAffix([
            'type' => 'suffix',
            'cost' => 1,
            'affix_type' => 7,
        ]);

        Item::factory()->create([
            'cost' => RandomAffixDetails::LEGENDARY,
            'type' => 'weapon',
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 1;
        $faction->current_points = 90;
        $faction->points_needed = 100;
        $faction->maxed = false;
        $faction->save();

        $this->factionHandler->awardFactionPointsFromBatch($character, 25);

        $faction = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($faction);
        $this->assertSame(2, $faction->current_level);
        $this->assertSame(15, $faction->current_points);
        $this->assertFalse((bool) $faction->maxed);
    }

    public function test_award_faction_points_from_batch_levels_up_when_points_already_maxed_for_level(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $this->createItemAffix([
            'type' => 'prefix',
            'cost' => 1,
            'affix_type' => 7,
        ]);

        $this->createItemAffix([
            'type' => 'suffix',
            'cost' => 1,
            'affix_type' => 7,
        ]);

        Item::factory()->create([
            'cost' => RandomAffixDetails::LEGENDARY,
            'type' => 'weapon',
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 1;
        $faction->current_points = 100;
        $faction->points_needed = 100;
        $faction->maxed = false;
        $faction->save();

        $this->factionHandler->awardFactionPointsFromBatch($character, 1);

        $faction = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($faction);
        $this->assertSame(2, $faction->current_level);
    }

    public function test_award_faction_points_from_batch_marks_faction_maxed_when_at_max_level(): void
    {
        $character = $this->character->getCharacter();

        $maxLevel = $this->getMaxFactionLevel();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = $maxLevel;
        $faction->current_points = 0;
        $faction->points_needed = 1;
        $faction->maxed = false;
        $faction->save();

        $this->factionHandler->awardFactionPointsFromBatch($character, 500);

        $faction = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($faction);
        $this->assertTrue((bool) $faction->maxed);
    }

    public function test_award_faction_points_from_batch_refreshes_and_returns_when_faction_becomes_maxed_via_level_up_to_max(): void
    {
        $character = $this->character->getCharacter();

        $maxLevel = $this->getMaxFactionLevel();

        $gameMap = GameMap::find($character->map->game_map_id);

        $this->createItemAffix([
            'type' => 'prefix',
            'cost' => 1,
            'affix_type' => 7,
        ]);

        $this->createItemAffix([
            'type' => 'suffix',
            'cost' => 1,
            'affix_type' => 7,
        ]);

        Item::factory()->create([
            'cost' => RandomAffixDetails::LEGENDARY,
            'type' => 'weapon',
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = $maxLevel - 1;
        $faction->current_points = 999999;
        $faction->points_needed = 1;
        $faction->maxed = false;
        $faction->save();

        $this->factionHandler->awardFactionPointsFromBatch($character, 1000);

        $faction = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($faction);
        $this->assertTrue((bool) $faction->maxed);
    }

    public function test_get_faction_points_per_kill_returns_zero_when_no_faction_exists(): void
    {
        $character = $this->character->getCharacter();

        $character->factions()->delete();

        $points = $this->factionHandler->getFactionPointsPerKill($character);

        $this->assertSame(0, $points);
    }

    public function test_get_faction_points_per_kill_returns_zero_when_faction_is_maxed(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 5;
        $faction->current_points = 0;
        $faction->points_needed = 100;
        $faction->maxed = true;
        $faction->save();

        $points = $this->factionHandler->getFactionPointsPerKill($character);

        $this->assertSame(0, $points);
    }

    public function test_get_faction_points_per_kill_adds_quest_item_and_guide_bonus_and_skips_when_required_level_matches(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 2;
        $faction->current_points = 0;
        $faction->points_needed = 100;
        $faction->maxed = false;
        $faction->save();

        $basePoints = FactionLevel::gatPointsPerLevel($faction->current_level);

        $character->user->update([
            'guide_enabled' => true,
        ]);

        $questItem = Item::factory()->create([
            'effect' => ItemEffectsValue::FACTION_POINTS,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($questItem)->getCharacter();

        GuideQuest::factory()->create([
            'unlock_at_level' => 1,
            'only_during_event' => null,
            'parent_id' => null,
            'faction_points_per_kill' => 12,
            'required_faction_id' => $faction->game_map_id,
            'required_faction_level' => 1,
        ]);

        $points = $this->factionHandler->getFactionPointsPerKill($character);

        $this->assertSame($basePoints + 50 + 12, $points);

        GuideQuest::factory()->create([
            'unlock_at_level' => 1,
            'only_during_event' => null,
            'parent_id' => null,
            'faction_points_per_kill' => 999,
            'required_faction_id' => $faction->game_map_id,
            'required_faction_level' => 2,
        ]);

        $pointsAfter = $this->factionHandler->getFactionPointsPerKill($character->refresh());

        $this->assertSame($basePoints + 50 + 12, $pointsAfter);
    }

    public function test_player_has_quest_item_returns_false_when_item_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $this->assertFalse($this->factionHandler->playerHasQuestItem($character));
    }

    public function test_player_has_quest_item_returns_true_when_item_exists_in_inventory(): void
    {
        $character = $this->character->getCharacter();

        $questItem = Item::factory()->create([
            'effect' => ItemEffectsValue::FACTION_POINTS,
        ]);

        InventorySlot::factory()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $questItem->id,
        ]);

        $this->assertTrue($this->factionHandler->playerHasQuestItem($character));
    }

    public function test_handle_faction_returns_when_character_has_current_automations(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 1;
        $faction->current_points = 0;
        $faction->points_needed = 100;
        $faction->maxed = false;
        $faction->save();

        $this->createExploringAutomation([
            'character_id' => $character->id,
            'monster_id' => $this->monster->id,
        ]);

        $this->factionHandler->handleFaction($character->refresh(), $this->monster);

        $faction = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($faction);
        $this->assertSame(0, $faction->current_points);
    }

    public function test_handle_faction_returns_when_no_faction_exists(): void
    {
        $character = $this->character->getCharacter();

        $character->factions()->delete();

        $this->factionHandler->handleFaction($character, $this->monster);

        $this->assertNull(Faction::where('character_id', $character->id)->first());
    }

    public function test_handle_faction_returns_when_faction_is_maxed(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 10;
        $faction->current_points = 0;
        $faction->points_needed = 100;
        $faction->maxed = true;
        $faction->save();

        $this->factionHandler->handleFaction($character, $this->monster);

        $faction = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($faction);
        $this->assertSame(0, $faction->current_points);
    }

    public function test_handle_faction_applies_points_and_applies_guide_quest_bonus_without_leveling_up(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 1;
        $faction->current_points = 0;
        $faction->points_needed = 1000;
        $faction->maxed = false;
        $faction->save();

        $character->user->update([
            'guide_enabled' => true,
        ]);

        GuideQuest::factory()->create([
            'unlock_at_level' => 1,
            'only_during_event' => null,
            'parent_id' => null,
            'faction_points_per_kill' => 17,
            'required_faction_id' => $faction->game_map_id,
            'required_faction_level' => 2,
        ]);

        $this->factionHandler->handleFaction($character->refresh(), $this->monster);

        $factionAfter = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($factionAfter);

        $basePoints = FactionLevel::gatPointsPerLevel($factionAfter->current_level);

        $this->assertSame($basePoints + 17, $factionAfter->current_points);
        $this->assertSame(1, $factionAfter->current_level);
    }

    public function test_handle_faction_levels_up_and_creates_inventory_slot_when_not_full_and_suffix_roll_succeeds(): void
    {
        srand(123);

        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $this->createItemAffix([
            'type' => 'prefix',
            'cost' => 1,
            'affix_type' => 7,
        ]);

        $this->createItemAffix([
            'type' => 'suffix',
            'cost' => 1,
            'affix_type' => 7,
        ]);

        $baseItem = Item::factory()->create([
            'cost' => RandomAffixDetails::LEGENDARY,
            'type' => 'weapon',
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $character->update([
            'gold' => 0,
        ]);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 1;
        $faction->current_points = 999;
        $faction->points_needed = 1000;
        $faction->maxed = false;
        $faction->save();

        $this->factionHandler->handleFaction($character->refresh(), $this->monster);

        $factionAfter = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($factionAfter);
        $this->assertSame(2, $factionAfter->current_level);
        $this->assertSame(0, $factionAfter->current_points);

        $characterAfter = $character->refresh();

        $this->assertGreaterThan(0, $characterAfter->gold);

        $this->assertNotNull($characterAfter->inventory);
        $this->assertTrue($characterAfter->inventory->slots()->whereNotNull('item_id')->exists());

        $slot = $characterAfter->inventory->slots()->latest('id')->first();

        $this->assertNotNull($slot);
        $this->assertNotSame($baseItem->id, $slot->item_id);

        $rewardItem = Item::find($slot->item_id);

        $this->assertNotNull($rewardItem);
        $this->assertNotNull($rewardItem->item_prefix_id);
        $this->assertNotNull($rewardItem->item_suffix_id);
    }

    public function test_handle_faction_marks_faction_maxed_when_max_level(): void
    {
        $character = $this->character->getCharacter();

        $maxLevel = $this->getMaxFactionLevel();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = $maxLevel;
        $faction->current_points = 0;
        $faction->points_needed = 1;
        $faction->maxed = false;
        $faction->save();

        $this->factionHandler->handleFaction($character->refresh(), $this->monster);

        $factionAfter = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($factionAfter);
        $this->assertTrue((bool) $factionAfter->maxed);
    }

    public function test_handle_custom_faction_amount_multiplies_when_player_has_quest_item_handles_inventory_full_and_gold_cap(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $questItem = Item::factory()->create([
            'effect' => ItemEffectsValue::FACTION_POINTS,
        ]);

        $this->createItemAffix([
            'type' => 'prefix',
            'cost' => 1,
            'affix_type' => 7,
        ]);

        $this->createItemAffix([
            'type' => 'suffix',
            'cost' => 1,
            'affix_type' => 7,
        ]);

        Item::factory()->create([
            'cost' => RandomAffixDetails::LEGENDARY,
            'type' => 'weapon',
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ]);

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD - 1,
        ]);

        $character->inventory->slots()->delete();

        for ($slotIndex = 1; $slotIndex <= 74; $slotIndex++) {
            $item = Item::factory()->create([
                'type' => 'weapon',
                'cost' => 1,
            ]);

            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id' => $item->id,
            ]);
        }

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $questItem->id,
        ]);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 1;
        $faction->current_points = 0;
        $faction->points_needed = 10;
        $faction->maxed = false;
        $faction->save();

        $this->factionHandler->handleCustomFactionAmount($character->refresh(), 2);

        $factionAfter = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($factionAfter);
        $this->assertSame(2, $factionAfter->current_level);

        $characterAfter = $character->refresh();

        $this->assertSame(MaxCurrenciesValue::MAX_GOLD, $characterAfter->gold);
        $this->assertSame(75, $characterAfter->inventory->slots()->count());
    }

    public function test_handle_custom_faction_amount_returns_when_no_faction_exists(): void
    {
        $character = $this->character->getCharacter();

        $character->factions()->delete();

        $this->factionHandler->handleCustomFactionAmount($character, 50);

        $this->assertNull(Faction::where('character_id', $character->id)->first());
    }

    public function test_handle_custom_faction_amount_returns_when_faction_is_maxed(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 10;
        $faction->current_points = 0;
        $faction->points_needed = 100;
        $faction->maxed = true;
        $faction->save();

        $this->factionHandler->handleCustomFactionAmount($character, 50);

        $faction = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($faction);
        $this->assertSame(0, $faction->current_points);
        $this->assertSame(10, $faction->current_level);
        $this->assertTrue((bool) $faction->maxed);
    }

    public function test_handle_custom_faction_amount_does_not_multiply_when_faction_level_is_zero(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $questItem = Item::factory()->create([
            'effect' => ItemEffectsValue::FACTION_POINTS,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($questItem)->getCharacter();

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 0;
        $faction->current_points = 0;
        $faction->points_needed = 100;
        $faction->maxed = false;
        $faction->save();

        $this->factionHandler->handleCustomFactionAmount($character->refresh(), 2);

        $factionAfter = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($factionAfter);
        $this->assertSame(2, $factionAfter->current_points);
    }

    public function test_handle_custom_faction_amount_applies_points_without_leveling_up_when_not_enough(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 1;
        $faction->current_points = 0;
        $faction->points_needed = 100;
        $faction->maxed = false;
        $faction->save();

        $this->factionHandler->handleCustomFactionAmount($character->refresh(), 10);

        $factionAfter = Faction::where('character_id', $character->id)->where('game_map_id', $gameMap->id)->first();

        $this->assertNotNull($factionAfter);
        $this->assertSame(10, $factionAfter->current_points);
        $this->assertSame(1, $factionAfter->current_level);
    }
    public function test_get_faction_points_per_kill_skips_quests_with_null_bonus_or_null_required_level_and_applies_next_valid_bonus(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 2;
        $faction->current_points = 0;
        $faction->points_needed = 100;
        $faction->maxed = false;
        $faction->save();

        $basePoints = FactionLevel::gatPointsPerLevel($faction->current_level);

        $character->user->update([
            'guide_enabled' => true,
        ]);

        $this->createGuideQuest([
            'unlock_at_level' => null,
            'only_during_event' => null,
            'parent_id' => null,
            'faction_points_per_kill' => null,
            'required_faction_id' => $faction->game_map_id,
            'required_faction_level' => 1,
        ]);

        $this->createGuideQuest([
            'unlock_at_level' => 1,
            'only_during_event' => null,
            'parent_id' => null,
            'faction_points_per_kill' => 13,
            'required_faction_id' => $faction->game_map_id,
            'required_faction_level' => 1,
        ]);

        $points = $this->factionHandler->getFactionPointsPerKill($character->refresh());

        $this->assertSame($basePoints + 13, $points);
    }

    public function test_get_faction_points_per_kill_skips_quests_for_other_factions_and_applies_next_matching_bonus(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 2;
        $faction->current_points = 0;
        $faction->points_needed = 100;
        $faction->maxed = false;
        $faction->save();

        $basePoints = FactionLevel::gatPointsPerLevel($faction->current_level);

        $character->user->update([
            'guide_enabled' => true,
        ]);

        $otherGameMap = $this->createGameMap([
            'name' => 'Other Map',
        ]);

        $this->createGuideQuest([
            'unlock_at_level' => null,
            'only_during_event' => null,
            'parent_id' => null,
            'faction_points_per_kill' => 99,
            'required_faction_id' => $otherGameMap->id,
            'required_faction_level' => 1,
        ]);

        $this->createGuideQuest([
            'unlock_at_level' => 1,
            'only_during_event' => null,
            'parent_id' => null,
            'faction_points_per_kill' => 12,
            'required_faction_id' => $faction->game_map_id,
            'required_faction_level' => 1,
        ]);

        $points = $this->factionHandler->getFactionPointsPerKill($character->refresh());

        $this->assertSame($basePoints + 12, $points);
    }

    public function test_get_faction_points_per_kill_applies_only_first_matching_quest_bonus_and_breaks(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 2;
        $faction->current_points = 0;
        $faction->points_needed = 100;
        $faction->maxed = false;
        $faction->save();

        $basePoints = FactionLevel::gatPointsPerLevel($faction->current_level);

        $character->user->update([
            'guide_enabled' => true,
        ]);

        $this->createGuideQuest([
            'unlock_at_level' => null,
            'only_during_event' => null,
            'parent_id' => null,
            'faction_points_per_kill' => 7,
            'required_faction_id' => $faction->game_map_id,
            'required_faction_level' => 1,
        ]);

        $this->createGuideQuest([
            'unlock_at_level' => 1,
            'only_during_event' => null,
            'parent_id' => null,
            'faction_points_per_kill' => 22,
            'required_faction_id' => $faction->game_map_id,
            'required_faction_level' => 1,
        ]);

        $points = $this->factionHandler->getFactionPointsPerKill($character->refresh());

        $this->assertSame($basePoints + 7, $points);
    }

    public function test_get_faction_points_per_kill_returns_points_per_kill_when_guide_is_disabled(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 2;
        $faction->current_points = 0;
        $faction->points_needed = 100;
        $faction->maxed = false;
        $faction->save();

        $basePoints = FactionLevel::gatPointsPerLevel($faction->current_level);

        $character->user->update([
            'guide_enabled' => false,
        ]);

        $questItem = Item::factory()->create([
            'effect' => ItemEffectsValue::FACTION_POINTS,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($questItem)->getCharacter();

        $this->createGuideQuest([
            'unlock_at_level' => 1,
            'only_during_event' => null,
            'parent_id' => null,
            'faction_points_per_kill' => 999,
            'required_faction_id' => $faction->game_map_id,
            'required_faction_level' => 1,
        ]);

        $points = $this->factionHandler->getFactionPointsPerKill($character->refresh());

        $this->assertSame($basePoints + 50, $points);
    }

    public function test_get_faction_points_per_kill_skips_quest_when_required_level_matches_current_level(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 2;
        $faction->current_points = 0;
        $faction->points_needed = 100;
        $faction->maxed = false;
        $faction->save();

        $basePoints = FactionLevel::gatPointsPerLevel($faction->current_level);

        $character->user->update([
            'guide_enabled' => true,
        ]);

        $this->createGuideQuest([
            'unlock_at_level' => null,
            'only_during_event' => null,
            'parent_id' => null,
            'faction_points_per_kill' => 11,
            'required_faction_id' => $faction->game_map_id,
            'required_faction_level' => 2,
        ]);

        $this->createGuideQuest([
            'unlock_at_level' => 1,
            'only_during_event' => null,
            'parent_id' => null,
            'faction_points_per_kill' => 9,
            'required_faction_id' => $faction->game_map_id,
            'required_faction_level' => 1,
        ]);

        $points = $this->factionHandler->getFactionPointsPerKill($character->refresh());

        $this->assertSame($basePoints + 9, $points);
    }

    public function test_get_faction_points_per_kill_adds_50_when_player_has_quest_item(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $basePoints = FactionLevel::gatPointsPerLevel($faction->current_level);

        $item = $this->createItem([
            'effect' => ItemEffectsValue::FACTION_POINTS,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $points = $this->factionHandler->getFactionPointsPerKill($character);

        $this->assertSame($basePoints + 50, $points);
    }

    public function test_award_faction_points_from_batch_calls_handle_faction_maxed_out_and_returns(): void
    {
        $character = $this->character->getCharacter();

        $maxLevel = $this->getMaxFactionLevel();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = $maxLevel;
        $faction->current_points = 99;
        $faction->points_needed = 100;
        $faction->maxed = false;
        $faction->save();

        $this->factionHandler->awardFactionPointsFromBatch($character->refresh(), 100000);

        $factionAfter = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($factionAfter);
        $this->assertTrue((bool) $factionAfter->maxed);
        $this->assertSame($maxLevel, $factionAfter->current_level);
        $this->assertSame(100, $factionAfter->current_points);
    }

    public function test_award_faction_points_caps_new_points_to_points_needed(): void
    {

        $this->createItem();
        $this->createItemAffix();

        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 1;
        $faction->current_points = 95;
        $faction->points_needed = 100;
        $faction->maxed = false;
        $faction->save();

        $this->factionHandler->awardFactionPointsFromBatch($character->refresh(), 10);

        $factionAfter = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($factionAfter);
        $this->assertSame(2, $factionAfter->current_level);
        $this->assertSame(5, $factionAfter->current_points);
        $this->assertFalse((bool) $factionAfter->maxed);
    }

    public function test_handle_custom_faction_amount_saves_faction_and_returns_when_maxed(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $faction->maxed = true;
        $faction->save();

        $this->factionHandler->handleCustomFactionAmount($character, 1000);

        $faction = $faction->refresh();

        $this->assertTrue($faction->maxed);
    }

    public function test_handle_faction_adds_50_faction_points_when_player_has_quest_item(): void
    {
        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 1;
        $faction->current_points = 0;
        $faction->points_needed = 1000;
        $faction->maxed = false;
        $faction->save();

        $character->user->update([
            'guide_enabled' => false,
        ]);

        $questItem = Item::factory()->create([
            'effect' => ItemEffectsValue::FACTION_POINTS,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($questItem)->getCharacter();

        $this->factionHandler->handleFaction($character->refresh(), $this->monster);

        $factionAfter = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($factionAfter);

        $basePoints = FactionLevel::gatPointsPerLevel($factionAfter->current_level);

        $this->assertSame($basePoints + 50, $factionAfter->current_points);
    }

    public function test_handle_faction_with_guide_enabled_and_no_guide_quests_applies_base_points_and_returns_early(): void
    {
        GuideQuest::query()->delete();

        $character = $this->character->getCharacter();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = 1;
        $faction->current_points = 0;
        $faction->points_needed = 1000;
        $faction->maxed = false;
        $faction->save();

        $character->user->update([
            'guide_enabled' => true,
        ]);

        $this->factionHandler->handleFaction($character->refresh(), $this->monster);

        $factionAfter = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($factionAfter);

        $basePoints = FactionLevel::gatPointsPerLevel($factionAfter->current_level);

        $this->assertSame($basePoints, $factionAfter->current_points);
    }

    public function test_award_faction_points_from_batch_marks_maxed_when_already_at_points_needed_and_at_max_level(): void
    {
        $character = $this->character->getCharacter();

        $maxLevel = $this->getMaxFactionLevel();

        $gameMap = GameMap::find($character->map->game_map_id);

        $faction = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($faction);

        $faction->current_level = $maxLevel;
        $faction->points_needed = 100;
        $faction->current_points = 100;
        $faction->maxed = false;
        $faction->save();

        $this->factionHandler->awardFactionPointsFromBatch($character->refresh(), 1);

        $factionAfter = Faction::where('character_id', $character->id)
            ->where('game_map_id', $gameMap->id)
            ->first();

        $this->assertNotNull($factionAfter);
        $this->assertTrue((bool) $factionAfter->maxed);
        $this->assertSame($maxLevel, $factionAfter->current_level);
        $this->assertSame(100, $factionAfter->current_points);
    }


}
