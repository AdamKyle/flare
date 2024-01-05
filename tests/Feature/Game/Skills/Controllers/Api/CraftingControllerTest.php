<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;


use App\Flare\Models\Character;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;

class CraftingControllerTest extends TestCase {

    use RefreshDatabase, CreateNpc, CreateItem, CreateFactionLoyalty, CreateGameSkill;

    private ?Character $character = null;

    public function setUp(): void {
        parent::setUp();

        $craftingSkill = $this->createGameSkill([
            'name' => 'Weapon Crafting',
            'type' => SkillTypeValue::CRAFTING,
        ]);

        $this->character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->assignFactionSystem()
            ->assignSkill(
                $craftingSkill, 10
            )
            ->getCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testFetchItemsToCraftWithTheAbilityToShowCraftForNpc() {
        $npc = $this->createNpc([
            'game_map_id' => $this->character->map->game_map_id
        ]);

        $item = $this->createItem([
            'crafting_type' => 'weapon',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id'   => $this->character->factions->first()->id,
            'character_id' => $this->character->id,
            'is_pledged'   => true,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id'          => $factionLoyalty->id,
            'npc_id'                      => $npc->id,
            'current_level'               => 0,
            'max_level'                   => 25,
            'next_level_fame'             => 100,
            'currently_helping'           => true,
            'kingdom_item_defence_bonus'  => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id'         => $factionLoyalty->id,
            'faction_loyalty_npc_id'     => $factionNpc->id,
            'fame_tasks'                 => [[
                'type'            => $item->crafting_type,
                'item_name'       => $item->affix_name,
                'item_id'         => $item->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 0,
            ]],
        ]);

        $character = $this->character->refresh();

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/crafting/' . $this->character->id, [
                'crafting_type' => $item->crafting_type,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['items'][0]['id'], $item->id);
        $this->assertEquals(0, $jsonData['xp']['current_xp']);
        $this->assertTrue($jsonData['show_craft_for_npc']);
    }
}
