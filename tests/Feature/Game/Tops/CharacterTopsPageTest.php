<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterTopsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_cannot_view_character_tops(): void
    {
        $response = $this->call('GET', '/game/tops');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_authenticated_users_can_view_character_tops_mount(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="character-tops"', $response->getContent());
    }

    public function test_character_profile_route_renders_selected_character_mount_data(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/characters/'.$character->id);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('data-selected-character-id="'.$character->id.'"', $response->getContent());
    }

    public function test_character_profile_route_wrapper_is_full_width_and_mounts_character_tops(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/characters/'.$character->id);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="character-tops"', $response->getContent());
        $this->assertStringContainsString('w-full px-4 sm:px-6 lg:px-8 pb-10', $response->getContent());
        $this->assertStringContainsString('data-selected-character-id="'.$character->id.'"', $response->getContent());
        $this->assertStringNotContainsString('lg:w-3/4', $response->getContent());
        $this->assertStringNotContainsString('max-w-7xl', $response->getContent());
    }

    public function test_old_numeric_route_redirects_to_character_profile_route(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/'.$character->id);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/game/tops/characters/'.$character->id, $response->headers->get('location'));
    }

    public function test_authenticated_sidebar_contains_character_progression_link(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops');

        $this->assertStringContainsString('Character Progression', $response->getContent());
        $this->assertStringContainsString('/game/tops', $response->getContent());
    }

    public function test_authenticated_sidebar_contains_exploration_link(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops');

        $this->assertStringContainsString('Exploration', $response->getContent());
        $this->assertStringContainsString('/game/tops/exploration', $response->getContent());
    }

    public function test_authenticated_sidebar_contains_delve_link(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops');

        $this->assertStringContainsString('Delve', $response->getContent());
        $this->assertStringContainsString('/game/tops/delve', $response->getContent());
    }

    public function test_authenticated_sidebar_contains_faction_loyalty_link(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops');

        $this->assertStringContainsString('Faction Loyalty', $response->getContent());
        $this->assertStringContainsString('/game/tops/faction-loyalty', $response->getContent());
    }

    public function test_authenticated_sidebar_contains_kingdoms_link(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops');

        $this->assertStringContainsString('Kingdoms', $response->getContent());
        $this->assertStringContainsString('/game/tops/kingdoms', $response->getContent());
    }

    public function test_exploration_tops_route_is_not_captured_by_legacy_numeric_route(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/exploration');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="exploration-tops"', $response->getContent());
    }

    public function test_delve_tops_route_is_not_captured_by_legacy_numeric_route(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/delve');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="delve-tops"', $response->getContent());
    }

    public function test_faction_loyalty_tops_route_is_not_captured_by_legacy_numeric_route(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/faction-loyalty');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="faction-loyalty-tops"', $response->getContent());
    }

    public function test_kingdom_tops_route_is_not_captured_by_legacy_numeric_route(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/kingdoms');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="kingdom-tops"', $response->getContent());
    }
}
