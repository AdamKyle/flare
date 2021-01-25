<?php

namespace Tests\Feature\Game\Core;

use App\Flare\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;


class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanSeeSettingsPage() {
        $character = $this->character->getCharacter();

        $this->actingAs($character->user)->visit(route('user.settings', [
            'user' => $character->user
        ]))->see('Account Settings');
    }

    public function testCanTurnOffEmailSettings() {
        $character = $this->character->getCharacter();

        $this->assertTrue($character->user->adventure_email);
        $this->assertTrue($character->user->new_building_email);

        $this->actingAs($character->user)->post(route('user.settings.email', [
            'user' => $character->user
        ]), []);

        $character = $character->refresh();

        $this->assertFalse($character->user->adventure_email);
        $this->assertFalse($character->user->new_building_email);
    }

    public function testCanTurnOnEmailSettings() {
        $character = $this->character->getCharacter();

        $character->user()->update([
            'adventure_email'    => false,
            'new_building_email' => false
        ]);

        $character = $character->refresh();

        $this->assertFalse($character->user->adventure_email);
        $this->assertFalse($character->user->new_building_email);

        $this->actingAs($character->user)->post(route('user.settings.email', [
            'user' => $character->user
        ]), [
            'adventure_email' => true,
            'new_building_email' => true,
        ]);

        $character = $character->refresh();

        $this->assertTrue($character->user->adventure_email);
        $this->assertTrue($character->user->new_building_email);
    }

    public function testCanUpdateCharacterName() {
        $characterName = $this->character->getCharacter()->name;

        $user = $this->character->getUser();

        $this->actingAs($user)->post(route('user.settings.character', [
            'user' => $user
        ]), ['name' => 'JacksAttack']);

        $this->assertNotEquals($characterName, $user->refresh()->character->name);
        $this->assertEquals('JacksAttack', $user->refresh()->character->name);
    }

    public function testCannotUpdateCharacterName() {
        $characterName = $this->character->getCharacter()->name;

        $user = $this->character->getUser();

        $this->actingAs($user)->post(route('user.settings.character', [
            'user' => $user
        ]), ['name' => 'JacksAttack'.$characterName]); // Too long.

        $this->assertEquals($characterName, $user->refresh()->character->name);
        $this->assertNotEquals('JacksAttack'.$characterName, $user->refresh()->character->name);
    }

    public function testCanUpdateSecurityQuestions() {
        $character = $this->character->getCharacter();

        $this->actingAs($character->user)->visit(route('user.settings', [
            'user' => $character->user
        ]))->submitForm('Update Security Questions', [
            'question_one' => 'Whats your favourite movie?',
            'question_two' => 'Whats the name of your mothers father?',
            'answer_one'   => 'apples',
            'answer_two'   => 'apples two',
            'password'     => 'ReallyLongPassword',
        ])->see('Security Question supdated. Do not forget these answers. We cannot reset them for you.');
    }

    public function testCannotUpdateSecurityQuestionsMissingFields() {
        $character = $this->character->getCharacter();

        $this->actingAs($character->user)->visit(route('user.settings', [
            'user' => $character->user
        ]))->submitForm('Update Security Questions', [])->see('The password field is required.');
    }

    public function testCanUpdateSecurityQuestionsInvalidPassword() {
        $character = $this->character->getCharacter();

        $this->actingAs($character->user)->visit(route('user.settings', [
            'user' => $character->user
        ]))->submitForm('Update Security Questions', [
            'question_one' => 'Whats your favourite movie?',
            'question_two' => 'Whats the name of your mothers father?',
            'answer_one'   => 'apples',
            'answer_two'   => 'apples two',
            'password'     => 'ReallyLongPassword3875687345',
        ])->see('Invalid password.');
    }

    public function testCanUpdateSecurityQuestionsUnique() {
        $character = $this->character->getCharacter();

        $this->actingAs($character->user)->visit(route('user.settings', [
            'user' => $character->user
        ]))->submitForm('Update Security Questions', [
            'question_one' => 'Whats the name of your mothers father?',
            'question_two' => 'Whats the name of your mothers father?',
            'answer_one'   => 'apples',
            'answer_two'   => 'apples two',
            'password'     => 'ReallyLongPassword',
        ])->see('Security questions need to be unique.');
    }

    public function testCanUpdateSecurityQuestionAnswersUnique() {
        $character = $this->character->getCharacter();

        $this->actingAs($character->user)->visit(route('user.settings', [
            'user' => $character->user
        ]))->submitForm('Update Security Questions', [
            'question_one' => 'Whats your favourite movie?',
            'question_two' => 'Whats the name of your mothers father?',
            'answer_one'   => 'apples',
            'answer_two'   => 'apples',
            'password'     => 'ReallyLongPassword',
        ])->see('Security questions answers need to be unique.');
    }
}