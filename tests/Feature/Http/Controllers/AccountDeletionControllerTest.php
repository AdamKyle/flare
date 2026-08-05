<?php

use App\Flare\Models\GameMap;
use App\Flare\Models\User;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreatePassiveSkill;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

uses(CreateClass::class, CreateGameSkill::class, CreateItem::class, CreatePassiveSkill::class, CreateRace::class, CreateUser::class);

test('user cannot delete another users account', function () {
    $owner = (new CharacterFactory)->createBaseCharacter()->getCharacter();
    $otherUser = $this->createUser();

    $response = $this->actingAs($otherUser)->post(route('delete.account', $owner->user));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'You cannot do that.');
    expect($owner->user->refresh()->will_be_deleted)->toBeFalse();
});

test('user can delete their own account', function () {
    $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
    $userId = $character->user->id;

    $response = $this->actingAs($character->user)->post(route('delete.account', $character->user));

    $response->assertRedirect('/');
    $response->assertSessionHas('success');
    $this->assertGuest();
    expect(User::find($userId))->toBeNull();
});

test('user cannot reset another users character', function () {
    $owner = (new CharacterFactory)->createBaseCharacter()->getCharacter();
    $otherUser = $this->createUser();

    $response = $this->actingAs($otherUser)->post(route('reset.account', $owner->user));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'You cannot do that.');
});

test('user can reset their own character with a new race and class', function () {
    GameMap::create([
        'name' => 'Surface',
        'path' => 'test path',
        'default' => true,
        'kingdom_color' => '#ffffff',
    ]);
    $this->createItem([
        'name' => 'Rusty blade',
        'type' => 'sword',
        'base_damage' => 3,
        'skill_level_required' => 1,
    ]);
    $this->createPassiveSkill();
    $this->createGameSkill(['name' => 'General A', 'game_class_id' => null]);

    $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
    $originalName = $character->name;
    $newRace = $this->createRace();
    $newClass = $this->createClass();

    $response = $this->actingAs($character->user)->post(route('reset.account', $character->user), [
        'race' => $newRace->id,
        'class' => $newClass->id,
    ]);

    $response->assertRedirect(route('game'));
    $response->assertSessionHas('success', 'Character has been re-rolled!');
    $refreshedCharacter = $character->user->refresh()->character;
    expect($refreshedCharacter)->not->toBeNull();
    expect($refreshedCharacter->name)->toBe($originalName);
    expect($refreshedCharacter->game_race_id)->toBe($newRace->id);
    expect($refreshedCharacter->game_class_id)->toBe($newClass->id);
});
