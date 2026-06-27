<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateUser;

class LoginControllerTest extends TestCase
{
    use CreateGuideQuest, CreateUser, RefreshDatabase;

    public function test_login_password_input_uses_current_password_autocomplete(): void
    {
        $response = $this->call('GET', '/login');

        $response->assertSee('autocomplete="current-password"', false);
        $response->assertDontSee('autocomplete="new-password"', false);
    }

    public function test_existing_user_can_login_with_correct_password(): void
    {
        $user = $this->createUser([
            'email' => 'login-test@example.com',
        ]);

        $response = $this->call('POST', route('login'), [
            'email' => 'login-test@example.com',
            'password' => 'ReallyLongPassword',
        ]);

        $response->assertRedirectedTo('/');
        $this->seeIsAuthenticatedAs($user);
    }

    public function test_user_can_login_when_guide_quest_requires_delve_pack_size_without_delve_data(): void
    {
        $this->createGuideQuest([
            'required_delve_pack_size' => 5,
        ]);

        $character = (new CharacterFactory)
            ->setAttributesOnUserForCreation([
                'email' => 'delve-login-test@example.com',
                'guide_enabled' => true,
            ])
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $response = $this->call('POST', route('login'), [
            'email' => 'delve-login-test@example.com',
            'password' => 'ReallyLongPassword',
        ]);

        $response->assertRedirectedTo('/');
        $this->seeIsAuthenticatedAs($character->user);
    }

    public function test_user_marked_for_deletion_can_login_and_deletion_flag_is_cleared(): void
    {
        $user = $this->createUser([
            'email' => 'login-test@example.com',
            'will_be_deleted' => true,
        ]);

        $response = $this->call('POST', route('login'), [
            'email' => 'login-test@example.com',
            'password' => 'ReallyLongPassword',
        ]);

        $response->assertRedirectedTo('/');
        $this->seeIsAuthenticatedAs($user);
        $this->assertFalse($user->refresh()->will_be_deleted);
    }

    public function test_existing_user_cannot_login_with_wrong_password(): void
    {
        $this->createUser([
            'email' => 'login-test@example.com',
        ]);

        $this->call('POST', route('login'), [
            'email' => 'login-test@example.com',
            'password' => 'WrongPassword',
        ]);

        $this->dontSeeIsAuthenticated();
        $this->assertSessionHasErrors('email');
    }
}
