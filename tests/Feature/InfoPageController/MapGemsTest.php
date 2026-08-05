<?php

namespace Tests\Feature\InfoPageController;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMapGemParamter;

class MapGemsTest extends TestCase
{
    use CreateGameMapGemParamter, RefreshDatabase;

    public function test_map_gems_page_lists_gems_with_their_map_name(): void
    {
        $gemParamter = $this->createGameMapGemParamter(['name' => 'Ember Shard']);

        $this->visit('/information/map-gems')
            ->see('Ember Shard')
            ->see($gemParamter->gameMap->name);
    }

    public function test_map_gems_search_filters_by_name(): void
    {
        $this->createGameMapGemParamter(['name' => 'Ember Shard']);
        $this->createGameMapGemParamter(['name' => 'Frost Crystal']);

        $response = $this->call('GET', '/information/map-gems', ['search' => 'Ember']);

        $response->assertOk();
        $response->assertSee('Ember Shard');
        $response->assertDontSee('Frost Crystal');
    }

    public function test_map_gems_page_shows_empty_state_when_no_gems(): void
    {
        $this->visit('/information/map-gems')
            ->see('No map gems found.');
    }
}
