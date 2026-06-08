<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class UnbanRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_email_lookup_does_not_reveal_account_or_ban_state(): void
    {
        $user = (new CharacterFactory)->createBaseCharacter()->getCharacter()->user;
        $tempBannedUser = (new CharacterFactory)->createBaseCharacter()->banCharacter('Reason', null, now()->addDay())->getCharacter()->user;
        $permanentlyBannedUser = (new CharacterFactory)->createBaseCharacter()->banCharacter('Reason')->getCharacter()->user;

        $responses = [
            $this->lookupUnbanEmail('unknown@example.com'),
            $this->lookupUnbanEmail($user->email),
            $this->lookupUnbanEmail($tempBannedUser->email),
            $this->lookupUnbanEmail($permanentlyBannedUser->email),
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
        $user = (new CharacterFactory)->createBaseCharacter()->banCharacter('Reason')->getCharacter()->user;

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

        $user = (new CharacterFactory)->createBaseCharacter()->banCharacter('Reason')->getCharacter()->user;

        $token = $this->getContinuationToken($user->email);
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

        $user = (new CharacterFactory)->createBaseCharacter()->banCharacter('Reason')->getCharacter()->user;

        $token = $this->getContinuationToken($user->email);
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
        $token = $this->getContinuationToken('unknown@example.com');

        $response = $this->call('POST', route('un.ban.request.submit'), [
            'unban_message' => 'Please review.',
            'token' => $token,
        ]);

        $response->assertSessionHas('success', 'Request submitted. We will contact you in the next 72 hours.');
    }

    private function lookupUnbanEmail(string $email)
    {
        return $this->call('POST', route('un.ban.request.email'), [
            'email' => $email,
        ]);
    }

    private function getContinuationToken(string $email): string
    {
        $lookupResponse = $this->lookupUnbanEmail($email);
        $lookupResponse->assertRedirect(route('un.ban.request'));

        $landingResponse = $this->call('GET', route('un.ban.request'));
        $landingResponse->assertOk();
        $this->assertMatchesRegularExpression(
            '/href="[^"]*\/un-ban\/request-form\/[^"]+"/',
            $landingResponse->getContent()
        );

        preg_match(
            '/href="([^"]*\/un-ban\/request-form\/[^"]+)"/',
            $landingResponse->getContent(),
            $continuationMatches
        );

        $formResponse = $this->call('GET', html_entity_decode($continuationMatches[1]));
        $formResponse->assertOk();
        $formResponse->assertDontSee($email);
        $formResponse->assertDontSee('Reason you were banned');

        preg_match('/name="token" value="([^"]+)"/', $formResponse->getContent(), $tokenMatches);

        $this->assertNotEmpty($tokenMatches[1] ?? null);

        return $tokenMatches[1];
    }
}
