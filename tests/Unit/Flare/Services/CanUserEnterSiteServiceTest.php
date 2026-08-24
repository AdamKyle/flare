<?php

namespace Tests\Unit\Flare\Services;

use App\Flare\Services\CanUserEnterSiteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class CanUserEnterSiteServiceTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_returns_true_when_registration_and_login_are_not_disabled(): void
    {
        config(['app.disabled_reg_and_login' => false]);

        $this->assertTrue((new CanUserEnterSiteService())->canUserEnterSite('nobody@example.com'));
    }

    public function test_returns_false_when_disabled_and_no_user_exists_for_the_email(): void
    {
        config(['app.disabled_reg_and_login' => true]);

        $this->assertFalse((new CanUserEnterSiteService())->canUserEnterSite('nobody@example.com'));
    }

    public function test_returns_true_when_disabled_and_the_user_is_an_admin_without_a_character(): void
    {
        config(['app.disabled_reg_and_login' => true]);
        $this->createAdminRole();
        $user = $this->createUser();
        $user->assignRole('Admin');

        $this->assertTrue((new CanUserEnterSiteService())->canUserEnterSite($user->email));
    }

    public function test_returns_false_when_disabled_and_the_user_has_no_character_and_is_not_an_admin(): void
    {
        config(['app.disabled_reg_and_login' => true]);
        $user = $this->createUser();

        $this->assertFalse((new CanUserEnterSiteService())->canUserEnterSite($user->email));
    }

    public function test_returns_true_when_disabled_and_the_user_matches_the_allowed_email(): void
    {
        config(['app.disabled_reg_and_login' => true, 'app.allowed_email' => 'allowed@example.com']);
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $character->user->update(['email' => 'allowed@example.com']);

        $this->assertTrue((new CanUserEnterSiteService())->canUserEnterSite('allowed@example.com'));
    }

    public function test_returns_false_when_disabled_and_the_user_has_a_character_but_does_not_match_the_allowed_email(): void
    {
        config(['app.disabled_reg_and_login' => true, 'app.allowed_email' => 'allowed@example.com']);
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();
        $character->user->update(['email' => 'someone-else@example.com']);

        $this->assertFalse((new CanUserEnterSiteService())->canUserEnterSite('someone-else@example.com'));
    }
}
