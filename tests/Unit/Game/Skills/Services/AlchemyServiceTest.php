<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class AlchemyServiceTest extends TestCase
{
    use RefreshDatabase, CreateGameSkill, CreateItem;

    private $character;

    private $item;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill($this->createGameSkill([
            'type' => SkillTypeValue::ALCHEMY
        ]))->updateCharacter([
            'gold_dust' => 10000,
            'shards'    => 100,
        ])->givePlayerLocation();

        $this->item = $this->createItem([
            'can_craft' => true,
            'crafting_type' => 'alchemy',
            'gold_dust_cost' => 1000,
            'shards_cost' => 10,
            'type' => 'alchemy',
            'skill_level_required' => 0,
        ]);
    }

    public function testAlchemyFailsItemDoesNotExist() {
        $alchemyService = resolve(AlchemyService::class);
        $character = $this->character->getCharacter();

        $response = $alchemyService->transmute($character, 100);

        $this->assertEquals(422, $response['status']);
    }

    public function testGoldDustIsTooMuch() {
        $this->item->update(['gold_dust_cost' => 10000000]);

        $item = $this->item->refresh();

        $alchemyService = resolve(AlchemyService::class);
        $character = $this->character->getCharacter();

        $response = $alchemyService->transmute($character, $item->id);

        $this->assertEquals(422, $response['status']);
    }

    public function testShardsCostIsTooMuch() {
        $this->item->update(['shards_cost' => 10000000]);

        $item = $this->item->refresh();

        $alchemyService = resolve(AlchemyService::class);
        $character = $this->character->getCharacter();

        $response = $alchemyService->transmute($character, $item->id);

        $this->assertEquals(422, $response['status']);
    }

    public function testFailToTransmuteSkillLevelRequiredToHigh() {
        $this->item->update(['skill_level_required' => 10000000]);

        $item = $this->item->refresh();

        $alchemyService = resolve(AlchemyService::class);
        $character = $this->character->getCharacter();

        $response = $alchemyService->transmute($character, $item->id);

        $this->assertCount(0, $response['items']);
    }

    public function testCreateAlchemyItem() {
        $this->item->update(['skill_level_trivial' => 100]);

        $item = $this->item->refresh();

        $alchemy = \Mockery::mock(AlchemyService::class)->makePartial();

        $this->app->instance(AlchemyService::class, $alchemy);

        $alchemy->shouldReceive('getDCCheck')->once()->andReturn(0);
        $alchemy->shouldReceive('characterRoll')->once()->andReturn(100);

        $alchemyService = resolve(AlchemyService::class);
        $character = $this->character->getCharacter();

        $response = $alchemyService->transmute($character, $item->id);

        $this->assertCount(1, $response['items']);

        $slot = $character->refresh()->inventory->slots(function($slot) {
            return $slot->item_id === $this->item->id;
        })->first();

        $this->assertNotNull($slot);
    }

    public function testCreateAlchemyItemButCantPickItUp() {
        $this->item->update(['skill_level_trivial' => 100]);

        $item = $this->item->refresh();

        $alchemy = \Mockery::mock(AlchemyService::class)->makePartial();

        $this->app->instance(AlchemyService::class, $alchemy);

        $alchemy->shouldReceive('getDCCheck')->once()->andReturn(0);
        $alchemy->shouldReceive('characterRoll')->once()->andReturn(100);

        $alchemyService = resolve(AlchemyService::class);
        $character = $this->character->updateCharacter([
            'inventory_max' => 0
        ])->getCharacter();

        $response = $alchemyService->transmute($character, $item->id);

        $this->assertCount(1, $response['items']);

        $slot = $character->refresh()->inventory->slots(function($slot) {
            return $slot->item_id === $this->item->id;
        })->first();

        $this->assertNull($slot);
    }
}
