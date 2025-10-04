<?php

namespace Tests\Feature\Auth;

use App\Flare\Models\GameMap;
use App\Flare\Models\User;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterCreation\Events\CreateCharacterEvent;
use App\Http\Middleware\GameAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreatePassiveSkill;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateUser;

class RegistrationControllerTest extends TestCase
{
    use CreateCharacter,
        CreateClass,
        CreateGameSkill,
        CreateItem,
        CreatePassiveSkill,
        CreateRace,
        CreateUser,
        RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(GameAuthentication::class);

        Hash::setRounds(4);

        Queue::fake();

        Event::fakeExcept([
            CreateCharacterEvent::class,
        ]);

        $this->app->instance(BuildCharacterAttackTypes::class, tap(Mockery::mock(BuildCharacterAttackTypes::class), function ($m) {
            $m->shouldReceive('buildCache')->andReturn([]);
        }));

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
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_see_registration(): void
    {
        $this->visit('/login')
            ->click('Register')
            ->see('E-Mail Address')
            ->see('Character Name')
            ->see('Password')
            ->see('Confirm Password')
            ->see('Character Creation')
            ->see('Character Name')
            ->see('Choose a Race')
            ->see('Choose a class')
            ->see('Register');
    }

    public function test_can_register(): void
    {
        GameMap::create([
            'name' => 'Surface',
            'path' => 'test path',
            'default' => true,
            'kingdom_color' => '#ffffff',
        ]);

        $race = $this->createRace([
            'dex_mod' => 2,
        ]);

        $class = $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $this->visit('/login')
            ->click('Register')
            ->submitForm('Register', [
                'email' => 'a@example.net',
                'password' => 'TestExamplePassword',
                'password_confirmation' => 'TestExamplePassword',
                'name' => 'bobtest',
                'race' => $race->id,
                'class' => $class->id,
            ])->dontSee('The name has already been taken.');

        $user = User::where('email', 'a@example.net')->first();

        $this->assertEquals('bobtest', $user->character->name);
        $this->assertEquals($race->name, $user->character->race->name);
        $this->assertEquals($class->name, $user->character->class->name);
        $this->assertGreaterThanOrEqual(1, $user->character->skills()->count());
    }

    public function test_cannot_register_when_banned(): void
    {
        GameMap::create([
            'name' => 'Surface',
            'path' => 'test path',
            'default' => true,
            'kingdom_color' => '#ffffff',
        ]);

        $this->createUser(['is_banned' => true]);

        $race = $this->createRace([
            'dex_mod' => 2,
        ]);

        $class = $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $this->visit('/login')
            ->click('Register')
            ->submitForm('Register', [
                'email' => 'a@example.net',
                'password' => 'TestExamplePassword',
                'password_confirmation' => 'TestExamplePassword',
                'name' => 'bobtest',
                'race' => $race->id,
                'class' => $class->id,
            ])->see('You have been banned until: ');
    }

    public function test_cannot_register_when_no_map(): void
    {
        $race = $this->createRace([
            'dex_mod' => 2,
        ]);

        $class = $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $this->visit('/login')
            ->click('Register')
            ->submitForm('Register', [
                'email' => 'a@example.net',
                'password' => 'TestExamplePassword',
                'password_confirmation' => 'TestExamplePassword',
                'name' => 'TestExample',
                'race' => $race->id,
                'class' => $class->id,
            ])->see('No game map has been set as default or created. Registration is disabled.');
    }

    public function test_cannot_register_when_character_exists(): void
    {
        GameMap::create([
            'name' => 'Surface',
            'path' => 'test path',
            'default' => true,
            'kingdom_color' => '#ffffff',
        ]);

        $race = $this->createRace([
            'dex_mod' => 2,
        ]);

        $class = $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter(false);

        $this->visit('/login')
            ->click('Register')
            ->submitForm('Register', [
                'email' => 'apples@apples.com',
                'password' => 'ReallyLongPassword',
                'password_confirmation' => 'ReallyLongPassword',
                'name' => $character->name,
                'race' => $race->id,
                'class' => $class->id,
            ])->see('The name has already been taken.');
    }

    public function test_cannot_register_any_more(): void
    {
        GameMap::create([
            'name' => 'Surface',
            'path' => 'test path',
            'default' => true,
            'kingdom_color' => '#ffffff',
        ]);

        $this->createUsersWithSameIp(10, '127.0.0.1');

        $race = $this->createRace([
            'dex_mod' => 2,
        ]);

        $class = $this->createClass([
            'str_mod' => 2,
            'damage_stat' => 'str',
        ]);

        $this->visit('/login')
            ->click('Register')
            ->submitForm('Register', [
                'email' => 'a@example.net',
                'password' => 'TestExamplePassword',
                'password_confirmation' => 'TestExamplePassword',
                'name' => 'bobtest',
                'race' => $race->id,
                'class' => $class->id,
            ])->see('You cannot register anymore characters.');
    }

    private function createUsersWithSameIp(int $count, string $ip): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->createUser(['ip_address' => $ip]);
        }
    }
}
