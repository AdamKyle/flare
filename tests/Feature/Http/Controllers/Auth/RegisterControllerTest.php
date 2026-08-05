<?php

use App\Flare\Models\GameMap;
use App\Flare\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreatePassiveSkill;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateUsersWithIp;

uses(
    CreateClass::class,
    CreateGameSkill::class,
    CreateItem::class,
    CreatePassiveSkill::class,
    CreateRace::class,
    CreateUser::class,
    CreateUsersWithIp::class,
);

beforeEach(function () {
    Hash::setRounds(4);

    $this->createItem([
        'name' => 'Rusty blade',
        'type' => 'sword',
        'base_damage' => 3,
        'skill_level_required' => 1,
    ]);

    $this->createPassiveSkill();

    $this->createGameSkill([
        'name' => 'General A',
        'game_class_id' => null,
    ]);
});

test('registration form is shown with expected fields', function () {
    $response = $this->get('/register');

    $response->assertSee('E-Mail Address');
    $response->assertSee('Choose a Race');
    $response->assertSee('Choose a Class');
    $response->assertSee('Password');
    $response->assertSee('Confirm Password');
});

test('can register a new user and character', function () {
    GameMap::create([
        'name' => 'Surface',
        'path' => 'test path',
        'default' => true,
        'kingdom_color' => '#ffffff',
    ]);

    $race = $this->createRace(['dex_mod' => 2]);
    $class = $this->createClass(['str_mod' => 2, 'damage_stat' => 'str']);

    $response = $this->post('/register', [
        'email' => 'a@example.net',
        'password' => 'TestExamplePassword',
        'password_confirmation' => 'TestExamplePassword',
        'name' => 'bobtest',
        'race' => $race->id,
        'class' => $class->id,
    ]);

    $user = User::where('email', 'a@example.net')->first();

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
    expect($user->character->name)->toBe('bobtest');
    expect($user->character->race->name)->toBe($race->name);
    expect($user->character->class->name)->toBe($class->name);
    expect($user->character->skills()->count())->toBeGreaterThanOrEqual(1);
});

test('cannot register when banned', function () {
    GameMap::create([
        'name' => 'Surface',
        'path' => 'test path',
        'default' => true,
        'kingdom_color' => '#ffffff',
    ]);

    $this->createUser(['is_banned' => true]);

    $race = $this->createRace(['dex_mod' => 2]);
    $class = $this->createClass(['str_mod' => 2, 'damage_stat' => 'str']);

    $response = $this->post('/register', [
        'email' => 'a@example.net',
        'password' => 'TestExamplePassword',
        'password_confirmation' => 'TestExamplePassword',
        'name' => 'bobtest',
        'race' => $race->id,
        'class' => $class->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('You have been banned until: ');
    $this->assertGuest();
});

test('cannot register while registration and login is disabled for an unrecognized email', function () {
    GameMap::create([
        'name' => 'Surface',
        'path' => 'test path',
        'default' => true,
        'kingdom_color' => '#ffffff',
    ]);
    config(['app.disabled_reg_and_login' => true]);

    $race = $this->createRace(['dex_mod' => 2]);
    $class = $this->createClass(['str_mod' => 2, 'damage_stat' => 'str']);

    $response = $this->post('/register', [
        'email' => 'unrecognized-user@example.com',
        'password' => 'TestExamplePassword',
        'password_confirmation' => 'TestExamplePassword',
        'name' => 'bobtest',
        'race' => $race->id,
        'class' => $class->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'I am sorry, right now the Registration and Login has been disabled while server maintenance and stability testing is taking place. We will be back up and running soon!');
    $this->assertGuest();
});

test('cannot register when no default game map exists', function () {
    $race = $this->createRace(['dex_mod' => 2]);
    $class = $this->createClass(['str_mod' => 2, 'damage_stat' => 'str']);

    $response = $this->post('/register', [
        'email' => 'a@example.net',
        'password' => 'TestExamplePassword',
        'password_confirmation' => 'TestExamplePassword',
        'name' => 'TestExample',
        'race' => $race->id,
        'class' => $class->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'No game map has been set as default or created. Registration is disabled.');
    $this->assertGuest();
});

test('cannot register when character name already exists', function () {
    GameMap::create([
        'name' => 'Surface',
        'path' => 'test path',
        'default' => true,
        'kingdom_color' => '#ffffff',
    ]);

    $race = $this->createRace(['dex_mod' => 2]);
    $class = $this->createClass(['str_mod' => 2, 'damage_stat' => 'str']);

    $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

    $response = $this->post('/register', [
        'email' => 'apples@apples.com',
        'password' => 'ReallyLongPassword',
        'password_confirmation' => 'ReallyLongPassword',
        'name' => $character->name,
        'race' => $race->id,
        'class' => $class->id,
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertGuest();
});

test('cannot register more than ten accounts from the same ip', function () {
    GameMap::create([
        'name' => 'Surface',
        'path' => 'test path',
        'default' => true,
        'kingdom_color' => '#ffffff',
    ]);

    $this->createUsersWithIp(10, '127.0.0.1');

    $race = $this->createRace(['dex_mod' => 2]);
    $class = $this->createClass(['str_mod' => 2, 'damage_stat' => 'str']);

    $response = $this->post('/register', [
        'email' => 'a@example.net',
        'password' => 'TestExamplePassword',
        'password_confirmation' => 'TestExamplePassword',
        'name' => 'bobtest',
        'race' => $race->id,
        'class' => $class->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'You cannot register anymore characters.');
    $this->assertGuest();
});
