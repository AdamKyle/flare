<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\Character;
use App\Flare\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterTopsPageTest extends TestCase
{
    use RefreshDatabase;

    public function testUnauthenticatedUsersCannotViewCharacterTops(): void
    {
        $response = $this->call('GET', '/game/tops');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testAuthenticatedUsersCanViewCharacterTopsMount(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="character-tops"', $response->getContent());
    }

    public function testCharacterProfileRouteRendersSelectedCharacterMountData(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/characters/'.$character->id);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('data-selected-character-id="'.$character->id.'"', $response->getContent());
    }

    public function testOldNumericRouteRedirectsToCharacterProfileRoute(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/'.$character->id);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/game/tops/characters/'.$character->id, $response->headers->get('location'));
    }

    public function testAuthenticatedSidebarContainsCharacterProgressionLink(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops');

        $this->assertStringContainsString('Character Progression', $response->getContent());
        $this->assertStringContainsString('/game/tops', $response->getContent());
    }

    public function testAuthenticatedSidebarContainsExplorationLink(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops');

        $this->assertStringContainsString('Exploration', $response->getContent());
        $this->assertStringContainsString('/game/tops/exploration', $response->getContent());
    }

    public function testAuthenticatedSidebarContainsDelveLink(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops');

        $this->assertStringContainsString('Delve', $response->getContent());
        $this->assertStringContainsString('/game/tops/delve', $response->getContent());
    }

    public function testAuthenticatedSidebarContainsFactionLoyaltyLink(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops');

        $this->assertStringContainsString('Faction Loyalty', $response->getContent());
        $this->assertStringContainsString('/game/tops/faction-loyalty', $response->getContent());
    }

    public function testAuthenticatedSidebarContainsKingdomsLink(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops');

        $this->assertStringContainsString('Kingdoms', $response->getContent());
        $this->assertStringContainsString('/game/tops/kingdoms', $response->getContent());
    }

    public function testExplorationTopsRouteIsNotCapturedByLegacyNumericRoute(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/exploration');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="exploration-tops"', $response->getContent());
    }

    public function testDelveTopsRouteIsNotCapturedByLegacyNumericRoute(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/delve');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="delve-tops"', $response->getContent());
    }

    public function testFactionLoyaltyTopsRouteIsNotCapturedByLegacyNumericRoute(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/faction-loyalty');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="faction-loyalty-tops"', $response->getContent());
    }

    public function testKingdomTopsRouteIsNotCapturedByLegacyNumericRoute(): void
    {
        $user = User::factory()->create();
        Character::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->call('GET', '/game/tops/kingdoms');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertStringContainsString('id="kingdom-tops"', $response->getContent());
    }
}
