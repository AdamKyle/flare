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

    public function testLoginPasswordInputUsesCurrentPasswordAutocomplete(): void
    {
        $response = $this->call('GET', '/login');

        $response->assertSee('<input id="password" type="password" class="form-control" name="password" required autocomplete="current-password" autofocus>', false);
        $response->assertDontSee('<input id="password" type="password" class="form-control" name="password" required autocomplete="new-password" autofocus>', false);
    }

    public function testExistingUserCanLoginWithCorrectPassword(): void
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

    public function testUserCanLoginWhenGuideQuestRequiresDelvePackSizeWithoutDelveData(): void
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

    public function testUserWithoutCharacterDoesNotHaveDeletionFlagClearedOnLogin(): void
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

    public function testUserWithCharacterWithoutInventoryIsLoggedOutAndRetainsDeletionFlag(): void
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

    public function testExistingUserCannotLoginWithWrongPassword(): void
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
