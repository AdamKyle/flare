<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ItemsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateItemAffix;

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
        $this->visitRoute('game.items.item', ['item' => 1])->see('Rusty Dagger');
    }

    public function testCanColorForOneAffix() {
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

        $this->item->save();

        $this->visitRoute('game.items.item', ['item' => 1])->see('Rusty Dagger')->see('one-enchant');
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

        $this->visitRoute('game.items.item', ['item' => 1])->see('Rusty Dagger')->see('two-enchant');
    }

    public function testColorForQuestItem() {
        $this->item->type = 'quest';
        $this->item->save();

        $this->visitRoute('game.items.item', ['item' => 1])->see('Rusty Dagger')->see('quest-item');
    }

    public function testCannotSeeItemDetailsFourOhFour() {
        $response = $this->get(route('game.items.item', [
            'item' => 100
        ]))->response;

        $this->assertEquals($response->status(), 404);
    }
}
