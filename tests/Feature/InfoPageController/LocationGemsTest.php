<?php

namespace Tests\Feature\InfoPageController;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamter;

class LocationGemsTest extends TestCase
{
    use CreateGameLocationGemParamter, RefreshDatabase;

    public function test_location_gems_page_lists_gems_with_their_location_and_map_name(): void
    {
        $gemParamter = $this->createGameLocationGemParamter(['name' => 'Ember Shard']);

        $this->visit('/information/location-gems')
            ->see('Ember Shard')
            ->see($gemParamter->location->name)
            ->see($gemParamter->location->map->name);
    }

    public function test_location_gems_search_filters_by_name(): void
    {
        $this->createGameLocationGemParamter(['name' => 'Ember Shard']);
        $this->createGameLocationGemParamter(['name' => 'Frost Crystal']);

        $response = $this->call('GET', '/information/location-gems', ['search' => 'Ember']);

        $response->assertOk();
        $response->assertSee('Ember Shard');
        $response->assertDontSee('Frost Crystal');
    }

    public function test_location_gems_page_shows_empty_state_when_no_gems(): void
    {
        $this->visit('/information/location-gems')
            ->see('No location gems found.');
    }
}
