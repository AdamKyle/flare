<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class LoginControllerTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_login_password_input_uses_current_password_autocomplete(): void
    {
        $response = $this->call('GET', '/login');

        $response->assertSee('<input id="password" type="password" class="form-control" name="password" required autocomplete="current-password" autofocus>', false);
        $response->assertDontSee('<input id="password" type="password" class="form-control" name="password" required autocomplete="new-password" autofocus>', false);
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
