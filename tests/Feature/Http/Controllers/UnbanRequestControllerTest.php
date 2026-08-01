<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateUser;

class UnbanRequestControllerTest extends TestCase
{
    use CreateUser, RefreshDatabase;

    public function test_public_email_lookup_does_not_reveal_account_or_ban_state(): void
    {
        $user = $this->createUser();
        $tempBannedUser = (new CharacterFactory)->createBaseCharacter(
            assignBaseSkill: false,
            assignPassiveSkills: false,
            createClassRanks: false,
        )->banCharacter('Reason', null, now()->addDay())->getCharacter()->user;
        $permanentlyBannedUser = (new CharacterFactory)->createBaseCharacter(
            assignBaseSkill: false,
            assignPassiveSkills: false,
            createClassRanks: false,
        )->banCharacter('Reason')->getCharacter()->user;

        $responses = [
            $this->call('POST', route('un.ban.request.email'), ['email' => 'unknown@example.com']),
            $this->call('POST', route('un.ban.request.email'), ['email' => $user->email]),
            $this->call('POST', route('un.ban.request.email'), ['email' => $tempBannedUser->email]),
            $this->call('POST', route('un.ban.request.email'), ['email' => $permanentlyBannedUser->email]),
        ];

        foreach ($responses as $response) {
            $response->assertRedirect();
            $response->assertSessionHas('success', 'If this account is eligible, you may continue with an unban request.');
            $this->assertSame($responses[0]->getStatusCode(), $response->getStatusCode());
            $this->assertSame($responses[0]->headers->get('Location'), $response->headers->get('Location'));
        }
    }

    public function test_submit_rejects_missing_or_invalid_find_user_token(): void
    {
        $user = (new CharacterFactory)->createBaseCharacter(
            assignBaseSkill: false,
            assignPassiveSkills: false,
            createClassRanks: false,
        )->banCharacter('Reason')->getCharacter()->user;

        $missingTokenResponse = $this->call('POST', route('un.ban.request.submit'), [
            'unban_message' => 'Please review.',
        ]);
        $invalidTokenResponse = $this->call('POST', route('un.ban.request.submit'), [
            'unban_message' => 'Please review.',
            'token' => 'invalid',
        ]);

        $missingTokenResponse->assertSessionHas('error', 'Unable to submit that request.');
        $invalidTokenResponse->assertSessionHas('error', 'Unable to submit that request.');
        $this->assertNull($user->refresh()->un_ban_request);
    }

    public function test_submit_accepts_valid_one_time_find_user_token(): void
    {
        Role::create(['name' => 'Admin']);

        $user = (new CharacterFactory)->createBaseCharacter(
            assignBaseSkill: false,
            assignPassiveSkills: false,
            createClassRanks: false,
        )->banCharacter('Reason')->getCharacter()->user;

        $lookupResponse = $this->call('POST', route('un.ban.request.email'), ['email' => $user->email]);
        $lookupResponse->assertRedirect(route('un.ban.request'));
        $lookupResponse->assertSessionHas('unban_request_token');
        $token = session('unban_request_token');
        $response = $this->call('POST', route('un.ban.request.submit'), [
            'unban_message' => 'Please review.',
            'token' => $token,
        ]);

        $response->assertSessionHas('success', 'Request submitted. We will contact you in the next 72 hours.');
        $this->assertSame('Please review.', $user->refresh()->un_ban_request);
    }

    public function test_valid_find_user_token_can_only_be_used_once(): void
    {
        Role::create(['name' => 'Admin']);

        $user = (new CharacterFactory)->createBaseCharacter(
            assignBaseSkill: false,
            assignPassiveSkills: false,
            createClassRanks: false,
        )->banCharacter('Reason')->getCharacter()->user;

        $lookupResponse = $this->call('POST', route('un.ban.request.email'), ['email' => $user->email]);
        $lookupResponse->assertRedirect(route('un.ban.request'));
        $lookupResponse->assertSessionHas('unban_request_token');
        $token = session('unban_request_token');
        $this->call('POST', route('un.ban.request.submit'), [
            'unban_message' => 'Please review.',
            'token' => $token,
        ]);

        $response = $this->call('POST', route('un.ban.request.submit'), [
            'unban_message' => 'Second review.',
            'token' => $token,
        ]);

        $response->assertSessionHas('error', 'Unable to submit that request.');
        $this->assertSame('Please review.', $user->refresh()->un_ban_request);
    }

    public function test_issued_ineligible_token_does_not_reveal_account_state(): void
    {
        $lookupResponse = $this->call('POST', route('un.ban.request.email'), ['email' => 'unknown@example.com']);
        $lookupResponse->assertRedirect(route('un.ban.request'));
        $lookupResponse->assertSessionHas('unban_request_token');
        $token = session('unban_request_token');

        $response = $this->call('POST', route('un.ban.request.submit'), [
            'unban_message' => 'Please review.',
            'token' => $token,
        ]);

        $response->assertSessionHas('success', 'Request submitted. We will contact you in the next 72 hours.');
    }
}
