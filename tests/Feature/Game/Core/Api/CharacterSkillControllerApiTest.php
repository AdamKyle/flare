<?php

namespace Tests\Feature\Game\Core\Api;

use App\Flare\Models\GameSkill;
use App\Flare\Models\ItemAffix;
use App\Game\Core\Services\CraftingSkillService;
use Database\Seeders\GameSkillsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\CharacterSetup;
use Tests\Traits\CreateItemAffix;

class CharacterSkillControllerApiTest extends TestCase {

    use RefreshDatabase,
        CreateItem,
        CreateUser,
        CreateItemAffix;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->seed(GameSkillsSeeder::class);

        $this->character = (new CharacterSetup)->setupCharacter($this->createUser())
                                               ->setSkill('Looting', [])
                                               ->setSkill('Weapon Crafting', [])
                                               ->setSkill('Enchanting', [])
                                               ->getCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanCraftItem() {
        $currentGold = $this->character->gold;

        $response = $this->actingAs($this->character->user, 'api')
            ->json('POST', '/api/craft/' . $this->character->id, [
                'item_to_craft' => $this->createItem([
                    'name' => 'sample',
                    'type' => 'weapon',
                    'cost' => 1,
                    'can_craft' => true,
                    'skill_level_required' => 1,
                    'crafting_type' => 'weapon',
                    'skill_level_trivial' => 10,
                ])->id,
                'type' => 'Weapon',
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertFalse($currentGold === $this->character->refresh()->gold);
    }

    public function testCanEnchantItem() {

        $this->createItemAffix();

        $this->character->update([
            'gold' => 10000,
        ]);

        GameSkill::where('name', 'Enchanting')->first()->update([
            'skill_bonus_per_level' => 100
        ]);

        $currentGold = $this->character->refresh()->gold;

        $item = $this->createItem([
            'name' => 'sample 2',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
        ]);

        $slot = $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
            'equipped' => false,
            'position' => null,
        ]);

        $craftingSkillService = Mockery::mock(CraftingSkillService::class)->makePartial();

        $this->app->instance(CraftingSkillService::class, $craftingSkillService);
        
        $craftingSkillService->shouldReceive('fetchCharacterRoll')->once()->andReturn(10000);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/enchant/' . $this->character->id, [
                            'slot_id'   => $slot->id,
                            'affix_ids' => [1],
                            'cost'      => 1000,
                         ])->response;

        $this->assertEquals(200, $response->status());
        $this->assertFalse($currentGold === $this->character->refresh()->gold);

        $this->assertNotNull($slot->refresh()->item->item_suffix_id);
    }

    public function testCanEnchantItemAddSuffix() {

        $this->createItemAffix();

        $this->character->update([
            'gold' => 10000,
        ]);

        GameSkill::where('name', 'Enchanting')->first()->update([
            'skill_bonus_per_level' => 100
        ]);

        $currentGold = $this->character->refresh()->gold;

        $item = $this->createItem([
            'name' => 'sample 2',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'name' => 'Demonic Prefix'
            ])->id,
        ]);

        $slot = $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
            'equipped' => false,
            'position' => null,
        ]);

        $craftingSkillService = Mockery::mock(CraftingSkillService::class)->makePartial();

        $this->app->instance(CraftingSkillService::class, $craftingSkillService);
        
        $craftingSkillService->shouldReceive('fetchCharacterRoll')->once()->andReturn(10000);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/enchant/' . $this->character->id, [
                            'slot_id'   => $slot->id,
                            'affix_ids' => [1],
                            'cost'      => 1000,
                         ])->response;


        $this->assertEquals(200, $response->status());
        $this->assertFalse($currentGold === $this->character->refresh()->gold);
        $this->assertNotNull($slot->refresh()->item->item_suffix_id);
        $this->assertNotNull($slot->refresh()->item->item_prefix_id);
    }

    public function testCanEnchantItemReplaceBothAffixes() {

        $this->createItemAffix();

        $this->character->update([
            'gold' => 10000,
        ]);

        GameSkill::where('name', 'Enchanting')->first()->update([
            'skill_bonus_per_level' => 100
        ]);

        $currentGold = $this->character->refresh()->gold;

        $item = $this->createItem([
            'name' => 'sample 2',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'name' => 'Demonic Prefix'
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
                'name' => 'Demonic Suffix'
            ])->id,
        ]);

        $slot = $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
            'equipped' => false,
            'position' => null,
        ]);

        $craftingSkillService = Mockery::mock(CraftingSkillService::class)->makePartial();

        $this->app->instance(CraftingSkillService::class, $craftingSkillService);
        
        $craftingSkillService->shouldReceive('fetchCharacterRoll')->andReturn(10000);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/enchant/' . $this->character->id, [
                            'slot_id'   => $slot->id,
                            'affix_ids' => [1, 2],
                            'cost'      => 1000,
                         ])->response;

        $this->assertEquals(200, $response->status());
        $this->assertFalse($currentGold === $this->character->refresh()->gold);
        $this->assertNotNull($slot->refresh()->item->item_suffix_id);
        $this->assertNotNull($slot->refresh()->item->item_prefix_id);
    }

    public function testFailToEnchantItem() {

        $this->createItemAffix();

        $this->character->update([
            'gold' => 10000,
        ]);

        GameSkill::where('name', 'Enchanting')->first()->update([
            'skill_bonus_per_level' => 100
        ]);

        $currentGold = $this->character->refresh()->gold;

        $item = $this->createItem([
            'name' => 'sample 2',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
        ]);

        $slot = $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
            'equipped' => false,
            'position' => null,
        ]);

        $craftingSkillService = Mockery::mock(CraftingSkillService::class)->makePartial();

        $this->app->instance(CraftingSkillService::class, $craftingSkillService);
        
        $craftingSkillService->shouldReceive('fetchCharacterRoll')->once()->andReturn(0);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/enchant/' . $this->character->id, [
                            'slot_id'   => $slot->id,
                            'affix_ids' => [1],
                            'cost'      => 1000
                         ])->response;

        $itemSlot = $this->character->refresh()->inventory->slots->where('item_id', $item->id)->first();

        $this->assertEquals(200, $response->status());
        $this->assertFalse($currentGold === $this->character->refresh()->gold);
        $this->assertNull($itemSlot);
    }

    public function testCanEnchantItemThatAlreadyHasAffix() {

        $this->character->update([
            'gold' => 10000,
        ]);

        GameSkill::where('name', 'Enchanting')->first()->update([
            'skill_bonus_per_level' => 100
        ]);

        $currentGold = $this->character->refresh()->gold;

        $item = $this->createItem([
            'name' => 'sample 2',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
            'item_suffix_id' => $this->createItemAffix()->id
        ]);

        $slot = $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
            'equipped' => false,
            'position' => null,
        ]);

        $craftingSkillService = Mockery::mock(CraftingSkillService::class)->makePartial();

        $this->app->instance(CraftingSkillService::class, $craftingSkillService);
        
        $craftingSkillService->shouldReceive('fetchCharacterRoll')->once()->andReturn(10000);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/enchant/' . $this->character->id, [
                            'slot_id'   => $slot->id,
                            'affix_ids' => [1],
                            'cost'      => 1000,
                            'extraTime' => 'double'
                         ])->response;

        $this->assertEquals(200, $response->status());
        $this->assertFalse($currentGold === $this->character->refresh()->gold);
        $this->assertNotNull($slot->refresh()->item->item_suffix_id);
    }

    public function testCanNotCraftItemThatDoesntExist() {
        $currentGold = $this->character->gold;

        $response = $this->actingAs($this->character->user, 'api')
            ->json('POST', '/api/craft/' . $this->character->id, [
                'item_to_craft' => 2,
                'type' => 'Weapon',
            ])
            ->response;

        $this->assertEquals(422, $response->status());
        $this->assertTrue($currentGold === $this->character->refresh()->gold);
    }

    public function testCanNotEnchantItemThatDoesntExistWithAfixThatDoesntExist() {
        $currentGold = $this->character->gold;

        $response = $this->actingAs($this->character->user, 'api')
                    ->json('POST', '/api/enchant/' . $this->character->id, [
                        'slot_id'   => 100,
                        'affix_ids' => [4],
                        'cost'      => 1000,
                    ])->response;

        $this->assertEquals(422, $response->status());
        $this->assertTrue($currentGold === $this->character->refresh()->gold);
    }

    public function testCanNotCraftItemWithSkillThatDoesntExist() {
        $currentGold = $this->character->gold;

        $response = $this->actingAs($this->character->user, 'api')
            ->json('POST', '/api/craft/' . $this->character->id, [
                'item_to_craft' => $this->createItem([
                    'name' => 'sample',
                    'type' => 'weapon',
                    'cost' => 1,
                    'can_craft' => true,
                    'skill_level_required' => 1,
                    'crafting_type' => 'weapon',
                    'skill_level_trivial' => 10,
                ])->id,
                'type' => 'Apples',
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertTrue($currentGold === $this->character->refresh()->gold);
    }

    public function testCannotCraftItemTooHard() {
        $this->createItem([
            'name' => 'sample 2',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
        ]);

        $response = $this->actingAs($this->character->user, 'api')
            ->json('POST', '/api/craft/' . $this->character->id, [
                'item_to_craft' => $this->createItem([
                    'name' => 'sample',
                    'type' => 'weapon',
                    'cost' => 1,
                    'can_craft' => true,
                    'skill_level_required' => 100,
                    'crafting_type' => 'weapon',
                    'skill_level_trivial' => 10,
                ])->id,
                'type' => 'Weapon',
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue($content->items[0]->name === 'sample 2');
        $this->assertEquals(1, count($content->items));
    }

    
    public function testCanEnchantItemTooHard() {
        $this->createItemAffix([
            'skill_level_required' => 10
        ]);

        $this->character->update([
            'gold' => 10000,
        ]);

        $currentGold = $this->character->refresh()->gold;

        $item = $this->createItem([
            'name' => 'sample 2',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
        ]);

        $slot = $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
            'equipped' => false,
            'position' => null,
        ]);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/enchant/' . $this->character->id, [
                            'slot_id'   => $slot->id,
                            'affix_ids' => [1],
                            'cost'      => 1000,
                         ])->response;

        $item = $this->character->refresh()->inventory->slots->where('item_id', $item->id)->first()->item;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertFalse($currentGold === $this->character->refresh()->gold);
        $this->assertNull($item->item_suffix_id);
    }

    public function testCanCraftItemTooEasy() {
        $this->createItem([
            'name' => 'sample 2',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 1,
            'crafting_type' => 'weapon',
            'skill_level_trivial' => 0,
        ]);

        $this->assertEquals(0, $this->character->skills->where('name', 'Weapon Crafting')->first()->xp);

        $response = $this->actingAs($this->character->user, 'api')
            ->json('POST', '/api/craft/' . $this->character->id, [
                'item_to_craft' => $this->createItem([
                    'name' => 'sample',
                    'type' => 'weapon',
                    'cost' => 1,
                    'can_craft' => true,
                    'skill_level_required' => 1,
                    'crafting_type' => 'weapon',
                    'skill_level_trivial' => 0,
                ])->id,
                'type' => 'Weapon',
            ])
            ->response;

        $content = json_decode($response->content());

        $skill = $this->character->skills()->join('game_skills', function($join) {
            $join->on('game_skills.id', 'skills.game_skill_id')
                 ->where('name', 'Weapon Crafting');
        })->first();

        $this->assertEquals(0, $skill->xp);

        $this->assertEquals(200, $response->status());
        $this->assertTrue($content->items[0]->name === 'sample 2');
        $this->assertEquals(2, count($content->items));
    }

    public function testCanEnchantItemTooEasy() {
        $this->createItemAffix([
            'skill_level_required' => 1
        ]);

        $this->character->skills()->where('game_skill_id', GameSkill::where('name', 'Enchanting')->first()->id)->first()->update([
            'level' => 400
        ]);

        $this->character->update([
            'gold' => 10000,
        ]);

        $currentGold = $this->character->refresh()->gold;

        $item = $this->createItem([
            'name' => 'sample 2',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
        ]);

        $slot = $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
            'equipped' => false,
            'position' => null,
        ]);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/enchant/' . $this->character->id, [
                            'slot_id'   => $slot->id,
                            'affix_ids' => [1],
                            'cost'      => 1000,
                            'extraTime' => 'double'
                         ])->response;


        $this->assertEquals(200, $response->status());
        $this->assertFalse($currentGold === $this->character->refresh()->gold);
        $this->assertNotNull($slot->refresh()->item->item_suffix_id);
    }

    public function testCannotCraftItemCostsTooMuch() {
        $currentGold = $this->character->gold;

        $response = $this->actingAs($this->character->user, 'api')
            ->json('POST', '/api/craft/' . $this->character->id, [
                'item_to_craft' => $this->createItem([
                    'name' => 'sample',
                    'type' => 'weapon',
                    'cost' => 10000,
                    'can_craft' => true,
                    'skill_level_required' => 100,
                    'crafting_type' => 'weapon',
                    'skill_level_trivial' => 10,
                ])->id,
                'type' => 'Weapon',
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue($currentGold === $this->character->refresh()->gold);
    }

    public function testCannotEnchantItemTooCostly() {
        $this->createItemAffix([
            'skill_level_required' => 1
        ]);

        $this->character->update([
            'gold' => 10,
        ]);

        $currentGold = $this->character->refresh()->gold;

        $item = $this->createItem([
            'name' => 'sample 2',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
        ]);

        $slot = $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id' => $item->id,
            'equipped' => false,
            'position' => null,
        ]);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/enchant/' . $this->character->id, [
                            'slot_id'   => $slot->id,
                            'affix_ids' => [1],
                            'cost'      => 1000,
                            'extraTime' => 'double'
                         ])->response;

        $item = $this->character->refresh()->inventory->slots->where('item_id', $item->id)->first()->item;

        $this->assertEquals(422, $response->status());
        $this->assertTrue($currentGold === $this->character->refresh()->gold);
        $this->assertNull($item->item_suffix_id);
    }

    public function testCannotPickUpCraftedItem() {
        $this->character->update([
            'inventory_max' => 0,
        ]);

        $craftingSkilLService = $this->getMockBuilder(CraftingSkillService::class)
                                     ->setMethods(array('fetchDCCheck', 'fetchCharacterRoll'))
                                     ->getMock();

        $this->app->instance(CraftingSkillService::class, $craftingSkilLService);

        $craftingSkilLService->expects($this->any())
                             ->method('fetchDCCheck')
                             ->willReturn(0);

        $craftingSkilLService->expects($this->any())
                             ->method('fetchCharacterRoll')
                             ->willReturn(100);

        $response = $this->actingAs($this->character->user, 'api')
            ->json('POST', '/api/craft/' . $this->character->id, [
                'item_to_craft' => $this->createItem([
                    'name' => 'sample',
                    'type' => 'weapon',
                    'cost' => 1,
                    'can_craft' => true,
                    'skill_level_required' => 1,
                    'crafting_type' => 'weapon',
                    'skill_level_trivial' => 10,
                ])->id,
                'type' => 'Weapon',
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue($this->character->inventory->slots->count() === 0);
    }

    public function testCanPickUpCraftedItem() {
        $suffix = ItemAffix::create([
            'name' => 'Sample-suffix',
            'skill_training_bonus' => 1.5,
            'skill_name' => 'Weapon Crafting',
            'type' => 'suffix',
            'cost' => 100,
        ]);

        $prefix = ItemAffix::create([
            'name' => 'Sample-prefix',
            'skill_training_bonus' => 1.5,
            'skill_name' => 'Weapon Crafting',
            'type' => 'prefix',
            'cost' => 100,
        ]);

        $item = $this->createItem([
            'name' => 'sample',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
            'skill_name' => 'Weapon Crafting',
            'skill_training_bonus' => 1.5,
        ]);

        $item->update([
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $item->id,
            'equipped'     => true,
            'position'     => 'left-hand',
        ]);

        $craftingSkilLService = $this->getMockBuilder(CraftingSkillService::class)
                                     ->setMethods(array('fetchDCCheck', 'fetchCharacterRoll'))
                                     ->getMock();

        $this->app->instance(CraftingSkillService::class, $craftingSkilLService);

        $craftingSkilLService->expects($this->once())
                             ->method('fetchDCCheck')
                             ->willReturn(0);

        $craftingSkilLService->expects($this->once())
                             ->method('fetchCharacterRoll')
                             ->willReturn(100);

        $itemId = $this->createItem([
            'name' => 'sample',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
            'skill_level_trivial' => 10,
        ])->id;

        $response = $this->actingAs($this->character->user, 'api')
            ->json('POST', '/api/craft/' . $this->character->id, [
                'item_to_craft' => $itemId,
                'type' => 'Weapon',
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue($this->character->refresh()->inventory->slots->count() === 2);
        $this->assertTrue($this->character->refresh()->inventory->slots->filter(function($slot) {
            return $slot->item->name === 'sample';
        })->isNotEmpty());
    }

    public function testFailedToCraft() {
        $craftingSkilLService = $this->getMockBuilder(CraftingSkillService::class)
                                     ->setMethods(array('fetchDCCheck', 'fetchCharacterRoll'))
                                     ->getMock();

        $this->app->instance(CraftingSkillService::class, $craftingSkilLService);

        $craftingSkilLService->expects($this->once())
                             ->method('fetchDCCheck')
                             ->willReturn(100);

        $craftingSkilLService->expects($this->once())
                             ->method('fetchCharacterRoll')
                             ->willReturn(0);

        $itemId = $this->createItem([
            'name' => 'sample',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 10,
            'crafting_type' => 'weapon',
            'skill_level_trivial' => 10,
        ])->id;

        $response = $this->actingAs($this->character->user, 'api')
            ->json('POST', '/api/craft/' . $this->character->id, [
                'item_to_craft' => $itemId,
                'type' => 'Weapon',
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue($this->character->refresh()->inventory->slots->count() === 0);
        $this->assertFalse($this->character->refresh()->inventory->slots->filter(function($slot) {
            return $slot->item->name === 'sample';
        })->isNotEmpty());
    }

    public function testFailedToCraftBecauseDead() {
        $this->character->update(['is_dead' => true]);

        $itemId = $this->createItem([
            'name' => 'sample',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
            'skill_level_trivial' => 10,
        ])->id;

        $response = $this->actingAs($this->character->user, 'api')
            ->json('POST', '/api/craft/' . $this->character->id, [
                'item_to_craft' => $itemId,
                'type' => 'Weapon',
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
    }

    public function testGetAListOfItems() {

        $this->createItem([
            'name' => 'sample',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
            'skill_level_trivial' => 10,
        ]);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('GET', '/api/crafting/' . $this->character->id, [
                             'crafting_type' => 'Weapon'
                         ])
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($content->items);
    }

    public function testShouldNotGetAListOfItemsBecauseLevelIsTooLow() {

        $this->createItem([
            'name' => 'sample',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 2,
            'crafting_type' => 'weapon',
        ]);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('GET', '/api/crafting/' . $this->character->id, [
                             'crafting_type' => 'Weapon'
                         ])
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEmpty($content->items);
    }

    public function testShouldNotGetAListOfItemsBecauseDead() {

        $this->createItem([
            'name' => 'sample',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 2,
            'crafting_type' => 'weapon',
        ]);

        $this->character->update([
            'is_dead' => true,
        ]);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('GET', '/api/crafting/' . $this->character->id, [
                             'crafting_type' => 'Weapon'
                         ])
                         ->response;

        $this->assertEquals(422, $response->status());
    }
}
