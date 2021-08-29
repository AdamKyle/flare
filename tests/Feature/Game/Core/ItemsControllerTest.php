<?php

namespace Tests\Feature\Game\Core;

use App\Flare\Models\Item;
use App\Flare\Values\ItemUsabilityType;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Setup\Character\CharacterFactory;

class ItemsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateItemAffix,
        CreateCharacterBoon;

    private $item;

    public function setUp(): void {
        parent::setUp();

        $this->item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6',
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->item = null;
    }

    public function testCanSeeItemDetails() {
        $this->visitRoute('game.items.item', ['item' => Item::first()->id])->see('Rusty Dagger');
    }

    public function testCanColorForOneAffix() {
        $this->item->item_suffix_id = $this->createItemAffix([
            'name'                     => 'sample',
            'base_damage_mod'          => 0.10,
            'type'                     => 'suffix',
            'description'              => 'test',
            'base_healing_mod'         => 0.10,
            'str_mod'                  => 0.10,
            'dur_mod'                  => 0.10,
            'dex_mod'                  => 0.10,
            'chr_mod'                  => 0.10,
            'int_mod'                  => 0.10,
        ])->id;

        $this->item->save();

        $this->visitRoute('game.items.item', ['item' => $this->item])->see('Rusty Dagger')->see('one-enchant');
    }

    public function testCanColorForTwoAffix() {
        $this->item->item_suffix_id = $this->createItemAffix([
            'name'                 => 'sample',
            'base_damage_mod'      => 0.10,
            'type'                 => 'suffix',
            'description'          => 'test',
            'base_healing_mod'     => 0.10,
            'str_mod'              => 0.10,
            'dur_mod'              => 0.10,
            'dex_mod'              => 0.10,
            'chr_mod'              => 0.10,
            'int_mod'              => 0.10,
            'skill_name'           => 0.10,
            'skill_training_bonus' => 0.10,
        ])->id;

        $this->item->item_prefix_id = $this->createItemAffix([
            'name'                 => 'sample',
            'base_damage_mod'      => 0.10,
            'type'                 => 'suffix',
            'description'          => 'test',
            'base_healing_mod'     => 0.10,
            'str_mod'              => 0.10,
            'dur_mod'              => 0.10,
            'dex_mod'              => 0.10,
            'chr_mod'              => 0.10,
            'int_mod'              => 0.10,
            'skill_name'           => 0.10,
            'skill_training_bonus' => 0.10,
        ])->id;

        $this->item->save();

        $this->visitRoute('game.items.item', ['item' => Item::first()->id])->see('Rusty Dagger')->see('two-enchant');
    }

    public function testColorForQuestItem() {
        $this->item->type = 'quest';
        $this->item->save();

        $this->visitRoute('game.items.item', ['item' => Item::first()->id])->see('Rusty Dagger')->see('quest-item');
    }

    public function testCannotSeeItemDetailsFourOhFour() {
        $response = $this->get(route('game.items.item', [
            'item' => rand(768,9804),
        ]))->response;

        $this->assertEquals($response->status(), 404);
    }

    public function testUseItem() {
        $this->item->update([
            'usable' => true,
            'stat_increase' => true,
            'increase_stat_by' => 0.08,
            'lasts_for' => 10
        ]);

        $item = $this->item->refresh();

        $character = (new CharacterFactory)->createBaseCharacter()->inventoryManagement()->giveItem($item)->getCharacter();

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'stat_bonus'   => 0.08,
            'started'      => now(),
            'complete'     => now()->subHour(10),
            'type'         => ItemUsabilityType::STAT_INCREASE
        ]);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)->post(route('game.item.use', [
            'character' => $character->id,
            'item'      => $item->id,
        ]))->response;

        $response->assertSessionHas('success', 'Applied: ' . $item->name . ' for: ' . $item->lasts_for . ' Minutes.');
    }

    public function testCannotUseItemMaxBoons() {
        $this->item->update([
            'usable' => true,
            'stat_increase' => true,
            'increase_stat_by' => 0.08,
            'lasts_for' => 10
        ]);

        $item = $this->item->refresh();

        $character = (new CharacterFactory)->createBaseCharacter()->inventoryManagement()->giveItem($item)->getCharacter();

        for ($i = 1; $i <= 10; $i++) {
            $this->createCharacterBoon([
                'character_id' => $character->id,
                'stat_bonus' => 0.08,
                'started' => now(),
                'complete' => now()->addHour(10),
                'type' => ItemUsabilityType::STAT_INCREASE
            ]);
        }

        $character = $character->refresh();

        $response = $this->actingAs($character->user)->post(route('game.item.use', [
            'character' => $character->id,
            'item'      => $item->id,
        ]))->response;

        $response->assertSessionHas('error', 'You can only have a max of ten boons applied. Check active boons to see which ones you have. You can always cancel one by clicking on the row.');
    }

    public function testUseItemAffectsSkills() {
        $this->item->update([
            'usable' => true,
            'affects_skill_type' => SkillTypeValue::ALCHEMY,
            'increase_skill_bonus_by' => 0.18,
            'lasts_for' => 10
        ]);

        $item = $this->item->refresh();

        $character = (new CharacterFactory)->createBaseCharacter()->inventoryManagement()->giveItem($item)->getCharacter();

        $character = $character->refresh();

        $response = $this->actingAs($character->user)->post(route('game.item.use', [
            'character' => $character->id,
            'item'      => $item->id,
        ]))->response;

        $response->assertSessionHas('success', 'Applied: ' . $item->name . ' for: ' . $item->lasts_for . ' Minutes.');
    }

    public function testUseItemThatDoesntExist() {
        $this->item->update([
            'usable' => true,
            'stat_increase' => true,
            'increase_stat_by' => 0.08,
            'lasts_for' => 10
        ]);

        $item = $this->item->refresh();

        $character = (new CharacterFactory)->createBaseCharacter()->inventoryManagement()->getCharacter();

        $response = $this->actingAs($character->user)->post(route('game.item.use', [
            'character' => $character->id,
            'item'      => $item->id,
        ]))->response;

        $response->assertSessionHas('error', 'You don\'t have this item.');
    }
}
