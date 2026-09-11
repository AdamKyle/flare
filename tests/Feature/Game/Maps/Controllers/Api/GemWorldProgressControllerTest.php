<?php

namespace Tests\Feature\Game\Maps\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\GemProgression\GemWorldRewardTestFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterGameMapGemProgression;
use Tests\Traits\CreateCharacterGameMapGemScroll;
use Tests\Traits\CreateItem;

class GemWorldProgressControllerTest extends TestCase
{
    use CreateCharacterGameMapGemProgression, CreateCharacterGameMapGemScroll, CreateItem, RefreshDatabase;

    private GemWorldRewardTestFactory $gemWorldRewardTestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gemWorldRewardTestFactory = new GemWorldRewardTestFactory;
    }

    public function test_returns_null_profile_when_character_is_not_in_a_generated_gem_world(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/map/gem-world/'.$character->id.'/progress');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($jsonData['profile']);
    }

    public function test_returns_the_current_profile_and_progression_levels(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $this->createCharacterGameMapGemProgression([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 150,
            'xp' => 200,
        ]);

        $response = $this->actingAs($graph->character->user)
            ->call('GET', '/api/map/gem-world/'.$graph->character->id.'/progress');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('map_gem', $jsonData['profile']['type']);
        $this->assertSame($graph->mapProfile->id, $jsonData['profile']['id']);
        $this->assertSame(1, $jsonData['global']['level']);
        $this->assertSame(150, $jsonData['personal']['level']);
        $this->assertSame(200, $jsonData['personal']['xp']);
        $this->assertTrue($jsonData['scroll_drop']['eligible']);
        $this->assertEqualsWithDelta(0.02, $jsonData['scroll_drop']['chance'], 0.0000001);
    }

    public function test_current_status_does_not_eagerly_return_active_scroll_rows(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $scrollItem = $this->createGemXpScrollItem(0.15, 120, ['name' => 'Scroll of Testing']);

        $this->createCharacterGameMapGemScroll([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'item_id' => $scrollItem->id,
            'started_at' => now(),
            'expires_at' => now()->addHours(2),
        ]);

        $response = $this->actingAs($graph->character->user)
            ->call('GET', '/api/map/gem-world/'.$graph->character->id.'/progress');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayNotHasKey('active_scroll_rows', $jsonData);
        $this->assertSame(1, $jsonData['active_scrolls']['count']);
    }

    public function test_current_profile_active_scrolls_are_paginated(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $scrollItem = $this->createGemXpScrollItem(0.15, 120, ['name' => 'Scroll of Testing']);

        $this->createCharacterGameMapGemScroll([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'item_id' => $scrollItem->id,
            'started_at' => now(),
            'expires_at' => now()->addHours(2),
        ]);

        $response = $this->actingAs($graph->character->user)
            ->call('GET', '/api/map/gem-world/'.$graph->character->id.'/active-scrolls', ['per_page' => 10, 'page' => 1]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $jsonData['data']);
        $this->assertSame('Scroll of Testing', $jsonData['data'][0]['item_name']);
        $this->assertSame('xp', $jsonData['data'][0]['gem_scroll_type']);
        $this->assertTrue($jsonData['data'][0]['is_map_scroll']);
        $this->assertTrue($jsonData['data'][0]['is_current_profile']);
        $this->assertFalse($jsonData['meta']['can_load_more']);
    }

    public function test_all_active_scrolls_are_paginated_across_profiles(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $scrollItem = $this->createGemXpScrollItem(0.15, 120, ['name' => 'Scroll of Testing']);

        $this->createCharacterGameMapGemScroll([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'item_id' => $scrollItem->id,
            'started_at' => now(),
            'expires_at' => now()->addHours(2),
        ]);

        $response = $this->actingAs($graph->character->user)
            ->call('GET', '/api/map/gem-world/'.$graph->character->id.'/all-active-scrolls', ['per_page' => 10, 'page' => 1]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $jsonData['data']);
        $this->assertSame('Scroll of Testing', $jsonData['data'][0]['item_name']);
    }

    public function test_all_profile_participation_is_paginated_and_flags_the_current_profile(): void
    {
        $graph = $this->gemWorldRewardTestFactory->buildGeneratedMapGemWorldCharacter();

        $this->createCharacterGameMapGemProgression([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 150,
            'xp' => 200,
        ]);

        $response = $this->actingAs($graph->character->user)
            ->call('GET', '/api/map/gem-world/'.$graph->character->id.'/all-profile-participation', ['per_page' => 10, 'page' => 1]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $jsonData['data']);
        $this->assertTrue($jsonData['data'][0]['is_map_profile']);
        $this->assertSame($graph->mapProfile->id, $jsonData['data'][0]['profile_id']);
        $this->assertSame(150, $jsonData['data'][0]['personal_level']);
        $this->assertTrue($jsonData['data'][0]['is_current_profile']);
        $this->assertFalse($jsonData['meta']['can_load_more']);
    }
}
