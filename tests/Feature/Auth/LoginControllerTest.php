<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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

    public function test_user_without_character_does_not_have_deletion_flag_cleared_on_login(): void
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
        $this->assertTrue($user->refresh()->will_be_deleted);
    }

    public function test_user_with_character_without_inventory_is_logged_out_and_retains_deletion_flag(): void
    {
        Queue::fake();
        $character = (new CharacterFactory)
            ->setAttributesOnUserForCreation([
                'email' => 'missing-inventory-login-test@example.com',
                'will_be_deleted' => false,
            ])
            ->createBaseCharacter()
            ->getCharacter();
        $character->inventory()->delete();

        $response = $this->call('POST', route('login'), [
            'email' => 'missing-inventory-login-test@example.com',
            'password' => 'ReallyLongPassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'Your previous character no longer exists. Please create a new character.');
        $this->dontSeeIsAuthenticated();
        $this->assertTrue($character->user->refresh()->will_be_deleted);
        Queue::assertNothingPushed();
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
