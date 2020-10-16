<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Locations\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Locations\Partials\QuestItem;
use App\Flare\Models\GameMap;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;

class QuestItemTest extends TestCase
{
    use RefreshDatabase, CreateItem, CreateLocation;

    public function testLocationQuestItemComponentIsLoaded() {
        $location = $this->createLocation([
            'name'                 => 'Apples',
            'game_map_id'          => GameMap::create([
                'name' => 'Apples',
                'path' => 'test',
                'default' => true
            ])->id,
            'quest_reward_item_id' => $this->createItem([
                'name'          => 'Sample',
                'type'          => 'quest',
                'base_damage'   => null,
                'cost'          => null,
                'crafting_type' => null,
            ])->id,
            'description'          => 'test',
            'is_port'              => false,
            'x'                    => 1,
            'y'                    => 1,
        ]);

        Livewire::test(QuestItem::class)->call('update', $location->getAttributes())->assertSee('Select item as quest reward:');
    }

    public function testSetQuestItemOnLocation() {
        $location = $this->createLocation([
            'name'                 => 'Apples',
            'game_map_id'          => GameMap::create([
                'name' => 'Apples',
                'path' => 'test',
                'default' => true
            ])->id,
            'quest_reward_item_id' => null,
            'description'          => 'test',
            'is_port'              => false,
            'x'                    => 1,
            'y'                    => 1,
        ]);

        $item = $this->createItem([
            'name'          => 'Sample',
            'type'          => 'quest',
            'base_damage'   => null,
            'cost'          => null,
            'crafting_type' => null,
        ]);
        
        $message = 'Created location: ' . $location->refresh()->name;

        Livewire::test(QuestItem::class, [
            'location' => $location
        ])
            ->assertSee('Select item as quest reward:')
            ->set('location.quest_reward_item_id', $item->id)
            ->call('validateInput', 'finish', 3);

        $this->assertNotNull($location->refresh()->quest_reward_item_id);
    }
}
