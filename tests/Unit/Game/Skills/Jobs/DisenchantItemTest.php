<?php

namespace Tests\Unit\Game\Skills\Jobs;

use App\Game\Skills\Jobs\DisenchantItem;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Setup\Character\CharacterFactory;

class DisenchantItemTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateItemAffix, CreateGameSkill;

    public function testDisenchantItems()
    {
        $character = (new CharacterFactory())->createBaseCharacter()
                                ->givePlayerLocation()
                                ->assignSkill(
                                    $this->createGameSkill([
                                        'type' => SkillTypeValue::ENCHANTING,
                                    ])
                                )
                                ->assignSkill(
                                    $this->createGameSkill([
                                        'type' => SkillTypeValue::DISENCHANTING,
                                    ])
                                )
                                ->inventoryManagement()
                                ->giveItem($this->createItem([
                                    'item_prefix_id' => $this->createItemAffix([
                                        'type' => 'prefix'
                                    ])->id
                                ]))
                                ->giveItem($this->createItem([
                                    'item_prefix_id' => $this->createItemAffix([
                                        'type' => 'prefix'
                                    ])->id
                                ]))
                                ->getCharacterFactory()->getCharacter(false);

        $slots = $character->inventory->slots->filter(function($slot) {
            return !is_null($slot->item->item_prefix_id);
        });

        DisenchantItem::dispatch($character, $slots[0]->id);
        DisenchantItem::dispatch($character, $slots[1]->id, true);

        $slots = $character->refresh()->inventory->slots->filter(function($slot) {
            return !is_null($slot->item->item_prefix_id);
        });

        $this->assertEmpty($slots);
    }

    
}
