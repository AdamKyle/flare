<?php

namespace Tests\Unit\Game\Skills\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Values\SkillTypeValue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateSkill;

class DisenchantServiceTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateItemAffix, CreateSkill, CreateGameSkill;

    private $character;

    private $item;

    public function setUp(): void {
        parent::setUp();

        $this->item      = $this->createItem([
            'item_suffix_id' => $this->createItemAffix()->id,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill($this->createGameSkill([
            'type' => SkillTypeValue::DISENCHANTING
        ]))->inventoryManagement()->giveItem($this->item);
    }

    public function testFailToDisenchantItem() {
        $disenchantService = \Mockery::mock(DisenchantService::class)->makePartial();

        $this->app->instance(DisenchantService::class, $disenchantService);

        $disenchantService->shouldReceive('getDCCheck')->once()->andReturn(100);
        $disenchantService->shouldReceive('characterRoll')->once()->andReturn(0);

        $disenchant = resolve(DisenchantService::class);

        $character = $this->character->getCharacter();

        $slot = $character->inventory->slots->filter(function($slot) {
            return $slot->item_id === $this->item->id;
        })->first();

        $disenchant->disenchantWithSkill($character, $slot);

        $this->assertTrue($disenchant->getGoldDust() > 0);
    }
}
