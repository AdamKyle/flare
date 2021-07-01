<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Game\Skills\Services\EnchantItemService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateSkill;

class EnchantItemServiceTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateItemAffix, CreateGameSkill, CreateSkill;

    private $item;

    private $itemAffix;

    private $skill;

    public function setUp(): void {
        parent::setUp();

        $this->item      = $this->createItem();
        $this->itemAffix = $this->createItemAffix();
        $this->skill     = $this->createSkill([
            'game_skill_id' => $this->createGameSkill([
                'type' => SkillTypeValue::ENCHANTING
            ])->id,
        ]);
    }

    public function testEnchantItem() {
        $enchantService = \Mockery::mock(EnchantItemService::class)->makePartial();

        $this->app->instance(EnchantItemService::class, $enchantService);

        $enchantService->shouldReceive('getDCCheck')->once()->andReturn(0);
        $enchantService->shouldReceive('characterRoll')->once()->andReturn(100);

        $enchant = resolve(EnchantItemService::class);

        $enchant->setDCIncrease(3)->attachAffix($this->item, $this->itemAffix, $this->skill);

        $this->assertNotNull($enchant->getItem());
    }

    public function testEnchantItemThenDestroyItem() {
        $enchantService = \Mockery::mock(EnchantItemService::class)->makePartial();

        $this->app->instance(EnchantItemService::class, $enchantService);

        $enchantService->shouldReceive('getDCCheck')->once()->andReturn(0);
        $enchantService->shouldReceive('characterRoll')->once()->andReturn(100);

        $enchant = resolve(EnchantItemService::class);

        $character = (new CharacterFactory)->createBaseCharacter()->inventoryManagement()->giveItem($this->item)->getCharacter();

        $enchant->setDCIncrease(3)->attachAffix($this->item, $this->itemAffix, $this->skill);

        $this->assertNotNull($enchant->getItem());

        $slot = $character->inventory->slots->filter(function($slot) {
            return $slot->item_id === $this->item->id;
        })->first();

        $enchant->deleteSlot($slot);

        $this->assertNull($enchant->getItem());
    }
}
