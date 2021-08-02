<?php

namespace Tests\Feature\Game\Core\Api;

use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantItemService;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItemAffix;

class CharacterSkillControllerApiTest extends TestCase {

    use RefreshDatabase,
        CreateItem,
        CreateUser,
        CreateItemAffix,
        CreateGameSkill;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->assignSkill($this->createGameSkill([
                                                    'name' => 'Weapon Crafting'
                                                 ]));
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanCraftItem() {
        $character = $this->character->getCharacter();
        $user       = $this->character->getUser();

        $currentGold = $character->getgold;

        $response = $this->actingAs($user)
            ->json('POST', '/api/craft/' . $character->id, [
                'item_to_craft' => $this->createItem([
                    'name' => 'sample',
                    'type' => 'weapon',
                    'cost' => 1,
                    'can_craft' => true,
                    'skill_level_required' => 1,
                    'crafting_type' => 'weapon',
                    'skill_level_trivial' => 10,
                ])->id,
                'type' => 'weapon',
            ])
            ->response;

        $this->assertEquals(200, $response->status());
        $this->assertFalse($currentGold === $this->character->getCharacter()->gold);
    }

    public function testShouldBeAbleToGetAffixesList() {
        $this->createItemAffix([
            'int_required' => 1,
            'skill_level_required' => 0,
        ]);

        $skill = $this->createGameSkill([
            'name' => 'Enchanting'
        ]);

        $character = $this->character->updateCharacter([
            'gold' => 10000,
        ])->assignSkill($skill)
          ->trainSkill($skill->name)
          ->inventoryManagement()
          ->giveItem($this->createItem())
          ->getCharacterFactory()
          ->getCharacter();

        $response = $this->actingAs($character->user)->json('GET', '/api/enchanting/' . $character->id)->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());

        $this->assertNotEmpty($content);

    }

    public function testCanEnchantItem() {

        $this->createItemAffix();

        $skill = $this->createGameSkill([
            'name' => 'Enchanting'
        ]);

        $character = $this->character->updateCharacter([
            'gold' => 10000,
        ])->assignSkill($skill, 400)
          ->trainSkill($skill->name)
          ->inventoryManagement()
          ->giveItem($this->createItem([
            'name' => 'sample 2',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
          ]))
          ->getCharacterFactory()
          ->getCharacter();

        $currentGold = $character->gold;

        $user        = $this->character->getUser();
        $slot        = $character->inventory->slots->first();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/enchant/' . $character->id, [
                            'slot_id'   => $slot->id,
                            'affix_ids' => [ItemAffix::first()->id],
                            'cost'      => 1000,
                         ])->response;

        $this->assertEquals(200, $response->status());
        $this->assertFalse($currentGold === $this->character->getcharacter()->gold);

        $this->assertNotNull($slot->refresh()->item->item_suffix_id);
    }

    public function testCanEnchantItemAddSuffix() {

        $this->createItemAffix();

        $skill = $this->createGameSkill([
            'name' => 'Enchanting'
        ]);

        $character = $this->character->updateCharacter([
            'gold' => 10000,
        ])->assignSkill($skill, 400)
          ->trainSkill($skill->name)
          ->inventoryManagement()
          ->giveItem($this->createItem([
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
          ]))
          ->getCharacterFactory()
          ->getCharacter();

        $currentGold = $character->gold;

        $user        = $this->character->getUser();
        $slot        = $character->inventory->slots->first();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/enchant/' . $character->id, [
                            'slot_id'   => $slot->id,
                            'affix_ids' => [ItemAffix::first()->id],
                            'cost'      => 1000,
                         ])->response;


        $this->assertEquals(200, $response->status());
        $this->assertFalse($currentGold === $this->character->getCharacter()->gold);
        $this->assertNotNull($slot->refresh()->item->item_suffix_id);
        $this->assertNotNull($slot->refresh()->item->item_prefix_id);
    }

    public function testCanEnchantItemReplaceBothAffixes() {
        $this->createItemAffix();

        $skill = $this->createGameSkill([
            'name' => 'Enchanting'
        ]);

        $character = $this->character->updateCharacter([
            'gold' => 10000,
        ])->assignSkill($skill, 400)
          ->inventoryManagement()
          ->giveItem($this->createItem([
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
          ]))
          ->getCharacterFactory()
          ->getCharacter();

        $currentGold = $character->gold;

        $user        = $this->character->getUser();
        $slot        = $character->inventory->slots->first();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/enchant/' . $character->id, [
                            'slot_id'   => $slot->id,
                            'affix_ids' => [
                                ItemAffix::where('name', 'Demonic Prefix')->first()->id,
                                ItemAffix::where('name', 'Demonic Suffix')->first()->id,
                            ],
                            'cost'      => 1000,
                         ])->response;

        $this->assertEquals(200, $response->status());
        $this->assertFalse($currentGold === $this->character->getCharacter()->gold);
        $this->assertNotNull($slot->refresh()->item->item_suffix_id);
        $this->assertNotNull($slot->refresh()->item->item_prefix_id);
    }

    public function testFailToEnchantItem() {

        $this->createItemAffix();

        $skill = $this->createGameSkill([
            'name' => 'Enchanting'
        ]);

        $character = $this->character->updateCharacter(['gold' => 10000])
                                     ->assignSkill($skill)
                                     ->inventoryManagement()
                                     ->giveItem($this->createItem([
                                        'name' => 'sample 2',
                                        'type' => 'weapon',
                                        'cost' => 1,
                                        'can_craft' => true,
                                        'skill_level_required' => 1,
                                        'crafting_type' => 'weapon',
                                    ]))
                                     ->getCharacterFactory()
                                     ->getCharacter();
        $user      = $this->character->getUser();

        $currentGold = $character->gold;
        $slot        = $character->inventory->slots->first();

        $craftingSkillService = Mockery::mock(EnchantItemService::class)->makePartial();

        $this->app->instance(EnchantItemService::class, $craftingSkillService);

        $craftingSkillService->shouldReceive('characterRoll')->once()->andReturn(0);

        $response = $this->actingAs($user)
                         ->json('POST', '/api/enchant/' . $character->id, [
                            'slot_id'   => $slot->id,
                            'affix_ids' => [ItemAffix::first()->id],
                            'cost'      => 1000
                         ])->response;

        $character = $this->character->getCharacter();

        $this->assertEquals(200, $response->status());
        $this->assertFalse($currentGold === $character->gold);
        $this->assertTrue($character->inventory->slots->isEmpty());
    }

    public function testCanEnchantItemThatAlreadyHasAffix() {
        $skill = $this->createGameSkill([
            'name' => 'Enchanting'
        ]);

        $character = $this->character->updateCharacter(['gold' => 10000])
                                     ->assignSkill($skill, 400)
                                     ->inventoryManagement()
                                     ->giveItem($this->createItem([
                                        'name' => 'sample 2',
                                        'type' => 'weapon',
                                        'cost' => 1,
                                        'can_craft' => true,
                                        'skill_level_required' => 1,
                                        'crafting_type' => 'weapon',
                                        'item_suffix_id' => $this->createItemAffix()->id
                                    ]))
                                     ->getCharacterFactory()
                                     ->getCharacter();
        $user      = $this->character->getUser();


        $currentGold = $character->gold;

        $response = $this->actingAs($user)
                         ->json('POST', '/api/enchant/' . $character->id, [
                            'slot_id'   => $character->inventory->slots->first()->id,
                            'affix_ids' => [ItemAffix::first()->id],
                            'cost'      => 1000,
                            'extraTime' => 'double'
                         ])->response;

        $this->assertEquals(200, $response->status());
        $this->assertFalse($currentGold === $this->character->getCharacter()->gold);
        $this->assertNotNull($this->character->getCharacter()->inventory->slots->first()->item->item_suffix_id);
    }

    public function testCanNotCraftItemThatDoesntExist() {
        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $currentGold = $character->gold;

        $response = $this->actingAs($user)
            ->json('POST', '/api/craft/' . $character->id, [
                'item_to_craft' => rand(500,1590),
                'type' => 'Weapon',
            ])
            ->response;

        $this->assertEquals(422, $response->status());
        $this->assertTrue($currentGold === $character->refresh()->gold);
    }

    public function testCanNotEnchantItemThatDoesntExistWithAffixThatDoesntExist() {
        $skill = $this->createGameSkill([
            'name' => 'Enchanting'
        ]);

        $character = $this->character->assignSkill($skill)->getCharacter();
        $user      = $this->character->getUser();

        $currentGold = $character->gold;

        $response = $this->actingAs($user)
                    ->json('POST', '/api/enchant/' . $character->id, [
                        'slot_id'   => 100,
                        'affix_ids' => [2500],
                        'cost'      => 1000,
                    ])->response;

        $this->assertEquals(422, $response->status());
        $this->assertTrue($currentGold === $character->refresh()->gold);
    }

    public function testCanNotCraftItemWithSkillThatDoesntExist() {
        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $currentGold = $character->gold;

        $response = $this->actingAs($user)
            ->json('POST', '/api/craft/' . $character->id, [
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

        $this->assertEquals(422, $response->status());
        $this->assertTrue($currentGold === $character->refresh()->gold);
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

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $currentGold = $character->gold;

        $response = $this->actingAs($user)
            ->json('POST', '/api/craft/' . $character->id, [
                'item_to_craft' => $this->createItem([
                    'name' => 'sample',
                    'type' => 'weapon',
                    'cost' => 1,
                    'can_craft' => true,
                    'skill_level_required' => 100,
                    'crafting_type' => 'weapon',
                    'skill_level_trivial' => 10,
                ])->id,
                'type' => 'weapon',
            ])
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue($content->items[0]->name === 'sample 2');
        $this->assertEquals(1, count($content->items));
        $this->assertTrue($currentGold === $character->refresh()->gold);
    }


    public function testCantEnchantItemTooHard() {
        $this->createItemAffix([
            'skill_level_required' => 10
        ]);

        $skill = $this->createGameSkill([
            'name' => 'Enchanting'
        ]);

        $character = $this->character->updateCharacter(['gold' => 10000])
                                     ->assignSkill($skill)
                                     ->updateSkill('Enchanting', [
                                         'level' => 0
                                     ])
                                     ->inventoryManagement()
                                     ->giveItem($this->createItem([
                                        'name' => 'sample 2',
                                        'type' => 'weapon',
                                        'cost' => 1,
                                        'can_craft' => true,
                                        'skill_level_required' => 1,
                                        'crafting_type' => 'weapon',
                                    ]))
                                     ->getCharacterFactory()
                                     ->getCharacter();

        $user      = $this->character->getUser();

        $currentGold = $character->refresh()->gold;

        $response = $this->actingAs($user)
                         ->json('POST', '/api/enchant/' . $character->id, [
                            'slot_id'   => $character->inventory->slots->first()->id,
                            'affix_ids' => [ItemAffix::first()->id],
                            'cost'      => 1000,
                         ])->response;

        $item = $this->character->getCharacter()->inventory->slots->first()->item;

        $this->assertEquals(200, $response->status());
        $this->assertTrue($currentGold !== $character->refresh()->gold);
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

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $this->assertEquals(0, $character->skills->where('name', 'Weapon Crafting')->first()->xp);

        $response = $this->actingAs($user)
            ->json('POST', '/api/craft/' . $character->id, [
                'item_to_craft' => $this->createItem([
                    'name' => 'sample',
                    'type' => 'weapon',
                    'cost' => 1,
                    'can_craft' => true,
                    'skill_level_required' => 1,
                    'crafting_type' => 'weapon',
                    'skill_level_trivial' => 0,
                ])->id,
                'type' => 'weapon',
            ])
            ->response;

        $content = json_decode($response->content());

        $skill = $this->character->getCharacter()->skills->where('name', 'Weapon Crafting')->first();

        // Should still be zero
        $this->assertEquals(0, $skill->xp);

        $this->assertEquals(200, $response->status());
        $this->assertTrue($content->items[0]->name === 'sample 2');
        $this->assertEquals(2, count($content->items));
    }

    public function testCanEnchantItemTooEasy() {

        $this->createItemAffix([
            'skill_level_required' => 1
        ]);

        $skill = $this->createGameSkill([
            'name' => 'Enchanting'
        ]);

        $character = $this->character->updateCharacter(['gold' => 10000])
                                     ->assignSkill($skill)
                                     ->updateSkill('Enchanting', [
                                         'level' => 400
                                     ])
                                     ->inventoryManagement()
                                     ->giveItem($this->createItem([
                                        'name' => 'sample 2',
                                        'type' => 'weapon',
                                        'cost' => 1,
                                        'can_craft' => true,
                                        'skill_level_required' => 1,
                                        'crafting_type' => 'weapon',
                                    ]))
                                     ->getCharacterFactory()
                                     ->getCharacter();
        $user      = $this->character->getUser();

        $currentGold = $character->gold;
        $currentXp   = $character->skills->where('name', 'Enchanting')->first()->xp;

        $response = $this->actingAs($user)
                         ->json('POST', '/api/enchant/' . $character->id, [
                            'slot_id'   => $character->inventory->slots->first()->id,
                            'affix_ids' => [ItemAffix::first()->id],
                            'cost'      => 1000,
                            'extraTime' => 'double'
                         ])->response;

        $character = $this->character->getCharacter();

        $this->assertEquals(200, $response->status());
        $this->assertFalse($currentGold === $character->gold);
        $this->assertTrue($currentXp === $character->refresh()->skills->where('name', 'Enchanting')->first()->xp);
        $this->assertNotNull($character->inventory->slots->first()->item->item_suffix_id);
    }

    public function testCannotCraftItemCostsTooMuch() {
        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $currentGold = $character->gold;

        $response = $this->actingAs($user)
            ->json('POST', '/api/craft/' . $character->id, [
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

        $this->assertEquals(422, $response->status());
        $this->assertTrue($currentGold === $character->refresh()->gold);
    }

    public function testCannotEnchantItemTooCostly() {
        $this->createItemAffix([
            'skill_level_required' => 1
        ]);

        $skill = $this->createGameSkill([
            'name' => 'Enchanting'
        ]);

        $character = $this->character->updateCharacter(['gold' => 0])
                                     ->assignSkill($skill)
                                     ->updateSkill('Enchanting', [
                                         'level' => 1
                                     ])
                                     ->inventoryManagement()
                                     ->giveItem($this->createItem([
                                        'name' => 'sample 2',
                                        'type' => 'weapon',
                                        'cost' => 1,
                                        'can_craft' => true,
                                        'skill_level_required' => 1,
                                        'crafting_type' => 'weapon',
                                    ]))
                                     ->getCharacterFactory()
                                     ->getCharacter();
        $user      = $this->character->getUser();

        $currentGold = $character->gold;

        $response = $this->actingAs($user)
                         ->json('POST', '/api/enchant/' . $character->id, [
                            'slot_id'   => $character->inventory->slots->first()->id,
                            'affix_ids' => [ItemAffix::first()->id],
                            'cost'      => 1000,
                            'extraTime' => 'double'
                         ])->response;

        $character = $this->character->getCharacter();

        $item      = $character->inventory->slots->first()->item;

        $this->assertEquals(422, $response->status());
        $this->assertTrue($currentGold === $character->gold);
        $this->assertNull($item->item_suffix_id);
    }

    public function testCannotPickUpCraftedItem() {
        $character = $this->character->updateCharacter(['gold' => 1000, 'inventory_max' => 0])
                                     ->updateSkill('Weapon Crafting', ['level' => 400])
                                     ->getCharacter();

        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
            ->json('POST', '/api/craft/' . $character->id, [
                'item_to_craft' => $this->createItem([
                    'name' => 'sample',
                    'type' => 'weapon',
                    'cost' => 1,
                    'can_craft' => true,
                    'skill_level_required' => 1,
                    'crafting_type' => 'weapon',
                    'skill_level_trivial' => 10,
                ])->id,
                'type' => 'weapon',
            ])
            ->response;

        $this->assertEquals(200, $response->status());
        $this->assertTrue($character->refresh()->inventory->slots->count() === 0);
    }

    public function testCanPickUpCraftedItem() {
        $character = $this->character->updateCharacter(['gold' => 1000])
                                     ->updateSkill('Weapon Crafting', ['level' => 400])
                                     ->inventoryManagement()
                                     ->giveItem($this->createItem([
                                        'name' => 'sample',
                                        'type' => 'weapon',
                                        'cost' => 1,
                                        'can_craft' => true,
                                        'skill_level_required' => 1,
                                        'crafting_type' => 'weapon',
                                        'skill_name' => 'Weapon Crafting',
                                        'skill_training_bonus' => 1.5,
                                        'item_prefix_id' => $this->createItemAffix([
                                            'name' => 'Sample-prefix',
                                            'skill_training_bonus' => 1.5,
                                            'skill_name' => 'Weapon Crafting',
                                            'type' => 'prefix',
                                            'cost' => 100,
                                        ])->id,
                                        'item_suffix_id' => $this->createItemAffix([
                                            'name' => 'Sample-suffix',
                                            'skill_training_bonus' => 1.5,
                                            'skill_name' => 'Weapon Crafting',
                                            'type' => 'suffix',
                                            'cost' => 100,
                                        ])->id,
                                    ]))
                                    ->equipLeftHand('sample')
                                    ->getCharacterFactory()
                                    ->getCharacter();

        $user      = $this->character->getUser();

        $itemId = $this->createItem([
            'name' => 'sample',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
            'skill_level_trivial' => 10,
        ])->id;

        $response = $this->actingAs($user)
            ->json('POST', '/api/craft/' . $character->id, [
                'item_to_craft' => $itemId,
                'type' => 'weapon',
            ])
            ->response;

        $character = $this->character->getCharacter();

        $this->assertEquals(200, $response->status());
        $this->assertTrue($character->inventory->slots->count() === 2);
        $this->assertTrue($character->inventory->slots->filter(function($slot) {
            return $slot->item->name === 'sample';
        })->isNotEmpty());
    }

    public function testFailedToCraft() {
        $craftingSkilLService = $this->getMockBuilder(CraftingService::class)
                                     ->setMethods(array('getDCCheck', 'characterRoll'))
                                     ->getMock();

        $this->app->instance(CraftingService::class, $craftingSkilLService);

        $craftingSkilLService->expects($this->once())
                             ->method('getDCCheck')
                             ->willReturn(100);

        $craftingSkilLService->expects($this->once())
                             ->method('characterRoll')
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

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
            ->json('POST', '/api/craft/' . $character->id, [
                'item_to_craft' => $itemId,
                'type' => 'weapon',
            ])
            ->response;

        $character = $this->character->getCharacter();

        $this->assertEquals(200, $response->status());
        $this->assertTrue($character->inventory->slots->count() === 0);
        $this->assertFalse($character->inventory->slots->filter(function($slot) {
            return $slot->item->name === 'sample';
        })->isNotEmpty());
    }

    public function testFailedToCraftBecauseDead() {

        $character = $this->character->updateCharacter(['is_dead' => true])->getCharacter();
        $user      = $this->character->getUser();

        $itemId = $this->createItem([
            'name' => 'sample',
            'type' => 'weapon',
            'cost' => 1,
            'can_craft' => true,
            'skill_level_required' => 1,
            'crafting_type' => 'weapon',
            'skill_level_trivial' => 10,
        ])->id;

        $response = $this->actingAs($user)
            ->json('POST', '/api/craft/' . $character->id, [
                'item_to_craft' => $itemId,
                'type' => 'Weapon',
            ])
            ->response;

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

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('GET', '/api/crafting/' . $character->id, [
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

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('GET', '/api/crafting/' . $character->id, [
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

        $character = $this->character->updateCharacter(['is_dead' => true])->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('GET', '/api/crafting/' . $character->id, [
                             'crafting_type' => 'Weapon'
                         ])
                         ->response;

        $this->assertEquals(422, $response->status());
    }
}
