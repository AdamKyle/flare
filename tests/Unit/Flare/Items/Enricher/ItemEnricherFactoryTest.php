<?php

namespace Tests\Unit\Flare\Items\Enricher;

use App\Flare\Items\Enricher\ItemEnricherFactory;
use App\Flare\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class ItemEnricherFactoryTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateItemAffix,
        CreateGameMap,
        CreateLocation,
        CreateMonster,
        CreateNpc,
        CreateQuest;

    private ?ItemEnricherFactory $factory = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->factory = app()->make(ItemEnricherFactory::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->factory = null;
    }

    public function testReturnsEnrichedEquippableItem(): void
    {
        $item = $this->createItem([
            'type' => 'weapon',
            'usable' => false,
            'affix_damage_reduction' => 0.15,
        ]);

        $result = $this->factory->buildItem($item, 'str');

        $this->assertInstanceOf(Item::class, $result);
        $this->assertNotNull($result->affix_damage_reduction);
        $this->assertEquals(0.15, $result->affix_damage_reduction);
    }

    public function testEquippableBranchActuallyEnrichesItem(): void
    {
        $item = $this->createItem([
            'type' => 'sword',
            'usable' => false,
            'base_damage' => 100,
            'base_healing' => 50,
            'base_ac' => 25,
            'base_damage_mod' => 0.10,
            'base_healing_mod' => 0.20,
            'base_ac_mod' => 0.30,
        ]);

        $result = $this->factory->buildItem($item, 'str');

        $this->assertEquals(110, $result->total_damage);
        $this->assertEquals(60, $result->total_healing);
        $this->assertEquals(33, $result->total_defence);

        $this->assertEquals(0.10, $result->base_damage_mod);
        $this->assertEquals(0.20, $result->base_healing_mod);
        $this->assertEquals(0.30, $result->base_ac_mod);
    }

    public function testReturnsUnmodifiedUsableItem(): void
    {
        $item = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
        ]);

        $result = $this->factory->buildItem($item);

        $this->assertSame($item->id, $result->id);
    }

    public function testReturnsUnmodifiedQuestItem(): void
    {
        $item = $this->createItem([
            'type' => 'quest',
            'usable' => false,
        ]);

        $result = $this->factory->buildItem($item);

        $this->assertSame($item->id, $result->id);
    }

    public function testReturnsTransformedEquippableItemData(): void
    {
        // Create an equippable item and place it in an actual inventory slot
        $item = $this->createItem([
            'type' => 'sword',
            'usable' => false,
        ]);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $slot = $character->inventory->slots()
            ->where('item_id', $item->id)
            ->first()
            ->fresh()
            ->load('item');

        // Pass both the item and the slot so the equippable transformer receives a Slot
        $result = $this->factory->buildItemData($item, $slot);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('slot_id', $result);
        $this->assertArrayHasKey('item_id', $result);
        $this->assertEquals($item->id, $result['item_id']);
    }

    public function testReturnsTransformedUsableItemData(): void
    {
        $item = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
        ]);

        $result = $this->factory->buildItemData($item);

        $this->assertIsArray($result);
        $this->assertEquals($item->id, $result['id']);
    }

    public function testReturnsTransformedQuestItemData(): void
    {
        $item = $this->createItem([
            'type' => 'quest',
            'usable' => false,
        ]);

        $result = $this->factory->buildItemData($item);

        $this->assertIsArray($result);
        // QuestItemTransformer uses 'item_id' as the identifier
        $this->assertEquals($item->id, $result['item_id']);
    }

    public function testReturnsEmptyArrayForUnknownType(): void
    {
        $item = $this->createItem([
            'type' => 'some-cursed-type',
            'usable' => false,
        ]);

        $result = $this->factory->buildItemData($item);

        $this->assertEquals([], $result);
    }

    public function testQuestItemIncludesDropLocation(): void
    {
        $map = $this->createGameMap(['name' => 'Surface']);
        $location = $this->createLocation(['game_map_id' => $map->id]);

        $item = $this->createItem([
            'type' => 'quest',
            'usable' => false,
            'drop_location_id' => $location->id,
        ]);

        $data = $this->factory->buildItemData($item);

        $this->assertNotNull($data['drop_location']);
        $this->assertEquals($location->id, $data['drop_location']['id']);
        $this->assertEquals('Surface', $data['drop_location']['map']);
    }

    public function testQuestItemIncludesRequiredMonster(): void
    {
        $map = $this->createGameMap(['name' => 'Surface']);
        $item = $this->createItem(['type' => 'quest', 'usable' => false]);

        $monster = $this->createMonster([
            'game_map_id' => $map->id,
            'quest_item_id' => $item->id,
        ]);

        $data = $this->factory->buildItemData($item);

        $this->assertNotNull($data['required_monster']);
        $this->assertEquals($monster->id, $data['required_monster']['id']);
        $this->assertEquals('Surface', $data['required_monster']['map']);
    }

    public function testQuestItemIncludesRequiredQuest(): void
    {
        $map = $this->createGameMap(['name' => 'Surface']);
        $npc = $this->createNpc(['game_map_id' => $map->id]);

        $item = $this->createItem(['type' => 'quest', 'usable' => false]);
        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'reward_item' => $item->id,
        ]);

        $data = $this->factory->buildItemData($item);

        $this->assertNotNull($data['reward_quests']);
        $this->assertEquals($quest->id, $data['reward_quests'][0]['id']);
        $this->assertEquals($npc->real_name, $data['reward_quests'][0]['npc']);
    }

    public function testQuestItemIncludesRewardLocations(): void
    {
        $map = $this->createGameMap(['name' => 'Surface']);
        $item = $this->createItem(['type' => 'quest', 'usable' => false]);
        $rewardLocation = $this->createLocation(['game_map_id' => $map->id, 'quest_reward_item_id' => $item->id]);

        $data = $this->factory->buildItemData($item);

        $this->assertCount(1, $data['reward_locations']);
        $this->assertEquals($rewardLocation->id, $data['reward_locations'][0]['id']);
    }

    public function testQuestItemIncludesAllRequiredQuests(): void
    {
        $map = $this->createGameMap(['name' => 'Surface']);
        $npc = $this->createNpc(['game_map_id' => $map->id]);
        $item = $this->createItem(['type' => 'quest', 'usable' => false]);

        $quest1 = $this->createQuest(['npc_id' => $npc->id, 'item_id' => $item->id]);
        $quest2 = $this->createQuest(['npc_id' => $npc->id, 'secondary_required_item' => $item->id]);

        $data = $this->factory->buildItemData($item);

        $this->assertCount(2, $data['required_quests']);
        $this->assertEqualsCanonicalizing(
            [$quest1->id, $quest2->id],
            array_column($data['required_quests'], 'id')
        );
    }

    public function testQuestItemIsRewardedByQuest(): void
    {
        $map = $this->createGameMap(['name' => 'Surface']);
        $npc = $this->createNpc(['game_map_id' => $map->id]);
        $item = $this->createItem(['type' => 'quest', 'usable' => false]);

        $quest = $this->createQuest(['npc_id' => $npc->id, 'reward_item' => $item->id]);

        $data = $this->factory->buildItemData($item);

        $this->assertCount(1, $data['reward_quests']);
        $this->assertEquals($quest->id, $data['reward_quests'][0]['id']);
    }

    public function testQuestItemIsRequiredByLocation(): void
    {
        $map = $this->createGameMap(['name' => 'Surface']);

        $item = $this->createItem(['type' => 'quest', 'usable' => false]);

        $location = $this->createLocation([
            'game_map_id' => $map->id,
            'required_quest_item_id' => $item->id,
        ]);

        $data = $this->factory->buildItemData($item);

        $this->assertCount(1, $data['required_locations']);
        $this->assertEquals($location->id, $data['required_locations'][0]['id']);
    }
}
