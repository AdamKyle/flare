<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Character\CharacterInventory\Values\ArmourType;
use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;

class CraftingControllerTest extends TestCase
{
    use CreateFactionLoyalty, CreateGameSkill, CreateItem, CreateNpc, RefreshDatabase;

    private ?Character $character = null;

    public function setUp(): void
    {
        parent::setUp();

        $craftingSkill = $this->createGameSkill([
            'name' => 'Weapon Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
            'max_level' => 400,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->assignSkill(
                $craftingSkill,
                10
            )
            ->getCharacter();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testFetchItemsToCraftWithTheAbilityToShowCraftForNpc()
    {
        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $item = $this->createItem([
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
            'is_pledged' => true,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => true,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [[
                'type' => $item->crafting_type,
                'item_name' => $item->affix_name,
                'item_id' => $item->id,
                'required_amount' => rand(10, 50),
                'current_amount' => 0,
            ]],
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/crafting/' . $this->character->id, [
                'crafting_type' => $item->crafting_type,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['items'][0]['id'], $item->id);
        $this->assertEquals(0, $jsonData['xp']['current_xp']);
        $this->assertTrue($jsonData['show_craft_for_npc']);
    }

    public function testFetchItemsToCraftWhileNotShowingCraftForNPCDueToNotHelpingThatNPC()
    {
        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id,
        ]);

        $item = $this->createItem([
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
            'is_pledged' => true,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [[
                'type' => $item->crafting_type,
                'item_name' => $item->affix_name,
                'item_id' => $item->id,
                'required_amount' => rand(10, 50),
                'current_amount' => 0,
            ]],
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/crafting/' . $this->character->id, [
                'crafting_type' => $item->crafting_type,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['items'][0]['id'], $item->id);
        $this->assertEquals(0, $jsonData['xp']['current_xp']);
        $this->assertFalse($jsonData['show_craft_for_npc']);
    }

    public function testGetCraftingItems()
    {
        $item = $this->createItem([
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/crafting/' . $this->character->id, [
                'crafting_type' => $item->crafting_type,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['items'][0]['id'], $item->id);
        $this->assertEquals(0, $jsonData['xp']['current_xp']);
        $this->assertFalse($jsonData['show_craft_for_npc']);
    }

    public function testCannotCraft()
    {
        $item = $this->createItem([
            'crafting_type' => ItemType::DAGGER->value,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $this->character->update([
            'can_craft' => false
        ]);

        $character = $this->character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/craft/' . $character->id, [
                'item_to_craft' => $item->id,
                'type' => $item->crafting_type,
                'craft_for_npc' => false,
                'craft_for_event' => false,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('You must wait to craft again.', $jsonData['message']);
        $this->assertEquals(422, $response->status());
    }

    public function testBuccaneerForMyClassWeaponCraftingReturnsGuns(): void
    {
        $craftingSkill = GameSkill::where('name', 'Weapon Crafting')->first();

        $buccaneerCharacter = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ])
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->assignSkill($craftingSkill, 10)
            ->getCharacter();

        $gun = $this->createItem([
            'type' => ItemType::GUN->value,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $response = $this->actingAs($buccaneerCharacter->user)
            ->call('GET', '/api/craft-for-class/' . $buccaneerCharacter->id);

        $response->assertOk();

        $jsonData = json_decode($response->getContent(), true);
        $itemIds = array_column($jsonData['items'], 'id');
        $this->assertContains($gun->id, $itemIds);
    }

    public function testBuccaneerForMyClassWeaponCraftingDoesNotReturnShields(): void
    {
        $craftingSkill = GameSkill::where('name', 'Weapon Crafting')->first();

        $buccaneerCharacter = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ])
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->assignSkill($craftingSkill, 10)
            ->getCharacter();

        $shield = $this->createItem([
            'type' => ArmourType::SHIELD->value,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $response = $this->actingAs($buccaneerCharacter->user)
            ->call('GET', '/api/craft-for-class/' . $buccaneerCharacter->id);

        $response->assertOk();

        $jsonData = json_decode($response->getContent(), true);
        $itemIds = array_column($jsonData['items'], 'id');
        $this->assertNotContains($shield->id, $itemIds);
    }

    public function testBuccaneerForMyClassWeaponCraftingReturnsSuccessfulResponse(): void
    {
        $craftingSkill = GameSkill::where('name', 'Weapon Crafting')->first();

        $buccaneerCharacter = (new CharacterFactory)
            ->createBaseCharacter([], [
                'name' => 'Buccaneer',
                'damage_stat' => 'str',
                'to_hit_stat' => 'dex',
            ])
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->assignSkill($craftingSkill, 10)
            ->getCharacter();

        $response = $this->actingAs($buccaneerCharacter->user)
            ->call('GET', '/api/craft-for-class/' . $buccaneerCharacter->id);

        $response->assertOk();

        $jsonData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('items', $jsonData);
    }

    public function testFighterForMyClassWeaponCraftingReturnsSwords(): void
    {
        $sword = $this->createItem([
            'type' => ItemType::SWORD->value,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/craft-for-class/' . $this->character->id);

        $response->assertOk();

        $jsonData = json_decode($response->getContent(), true);
        $itemIds = array_column($jsonData['items'], 'id');
        $this->assertContains($sword->id, $itemIds);
    }

    public function testCraftItem()
    {
        $item = $this->createItem([
            'type' => ItemType::DAGGER->value,
            'crafting_type' => ItemType::DAGGER->value,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $this->character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD
        ]);

        $character = $this->character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/craft/' . $character->id, [
                'item_to_craft' => $item->id,
                'type' => $item->crafting_type,
                'craft_for_npc' => false,
                'craft_for_event' => false,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $character = $this->character->refresh();

        $this->assertEquals(200, $response->status());
        $this->assertEquals($jsonData['items'][0]['id'], $item->id);
        $this->assertGreaterThan(0, $jsonData['xp']['current_xp']);
        $this->assertFalse($jsonData['show_craft_for_npc']);
        $this->assertNotEmpty($character->inventory->slots);
        $this->assertLessThan(MaxCurrenciesValue::MAX_GOLD, $character->gold);
    }
}
