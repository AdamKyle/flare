<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Models\ArtifactProperty;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ArtifactPropertyTest extends TestCase
{
    use RefreshDatabase, CreateItem;


    public function testArtifactPropertyCanGetParentItem()
    {
        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6'
        ]);

        $item->artifactProperty()->create([
            'item_id' => $item->id,
            'name' => 'test',
            'base_damage_mod' => 'str',
            'description' => 'test',
        ]);

        $this->assertEquals($item->id, ArtifactProperty::first()->item->id);
    }
}
