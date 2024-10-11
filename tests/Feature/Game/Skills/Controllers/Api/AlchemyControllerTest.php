<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Values\MaxCurrenciesValue;
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

class AlchemyControllerTest extends TestCase
{
    use CreateFactionLoyalty, CreateGameSkill, CreateItem, CreateNpc, RefreshDatabase;

    private ?Character $character = null;

    public function setUp(): void
    {
        parent::setUp();

        $craftingSkill = $this->createGameSkill([
            'name' => 'Alchemy',
            'type' => SkillTypeValue::CRAFTING,
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

    public function testGetAlchemyItems()
    {
        $item = $this->createItem([
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/alchemy/' . $this->character->id, [
                'crafting_type' => $item->crafting_type,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($jsonData['items'][0]['id'], $item->id);
        $this->assertEquals(0, $jsonData['skill_xp']['current_xp']);
    }

    public function testCannotTransmute()
    {
        $item = $this->createItem([
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
        ]);

        $this->character->update([
            'can_craft' => false
        ]);

        $character = $this->character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/transmute/' . $character->id, [
                'item_to_craft' => $item->id,
                'type' => $item->crafting_type,
                'craft_for_npc' => false,
                'craft_for_event' => false,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('You must wait to craft again.', $jsonData['message']);
        $this->assertEquals(422, $response->status());
    }

    public function testTransumteItem()
    {
        $item = $this->createItem([
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 25,
            'gold_dust_cost' => 100,
            'shards_cost' => 50
        ]);

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $this->character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $character = $this->character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/transmute/' . $character->id, [
                'item_to_craft' => $item->id,
                'type' => $item->crafting_type,
                'craft_for_npc' => false,
                'craft_for_event' => false,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $character = $this->character->refresh();

        $this->assertEquals(200, $response->status());
        $this->assertEquals($jsonData['items'][0]['id'], $item->id);
        $this->assertGreaterThan(0, $jsonData['skill_xp']['current_xp']);
        $this->assertNotEmpty($character->inventory->slots);
        $this->assertLessThan(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertLessThan(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
    }
}
